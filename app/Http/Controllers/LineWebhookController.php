<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Line_Message;
use App\Models\Line_Trial_Users;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use App\Helpers\TimeExtractor;

class LineWebhookController extends Controller
{
    protected $bot;

    public function __construct()
    {
        $httpClient = new CurlHTTPClient(config('services.line.message.channel_token'));
        $this->bot = new LINEBot($httpClient, [
            'channelSecret' => config('services.line.message.channel_secret'),
        ]);
    }

    public function webhook(Request $request)
    {
        $events = $request->input('events', []);

        foreach ($events as $event) {
            switch ($event['type']) {
                case 'follow':
                    $this->handleFollowEvent($event);
                    break;

                case 'message':
                    $this->handleMessageEvent($event);
                    break;

                default:
                    Log::info('Unhandled event type: ' . $event['type']);
                    break;
            }
        }

        return response()->json(['status' => 'success']);
    }

    // 共通：reply
    protected function replyMessage(string $replyToken, string $text): bool
    {
        $response = $this->bot->replyMessage(
            $replyToken,
            new TextMessageBuilder($text)
        );

        if (!$response->isSucceeded()) {
            Log::warning("Reply failed: " . $response->getRawBody());
            return false;
        }
        return true;
    }

    // 共通：push
    protected function pushMessage(string $userId, string $text): bool
    {
        $response = $this->bot->pushMessage(
            $userId,
            new TextMessageBuilder($text)
        );

        if (!$response->isSucceeded()) {
            Log::error("Push failed: " . $response->getRawBody());
            return false;
        }
        return true;
    }

    // フォローイベント処理
    private function handleFollowEvent(array $event)
    {
        $userId = $event['source']['userId'];
        Log::info("Follow event received for userId = $userId");

        // 友だち追加ありがとうございます！
        $message = "① お子様のフルネーム\n\n"
                ."メッセージを確認して担当者から順次返信します。";
        $this->pushMessage($userId, $message);
    }

    // 2025/11/05
    // ✅ 仕様フロー
    // 初回メッセージ（＝初回登録）
    // 　→ 「体験会ご予約承りました...」を送信
    // 　→ ユーザーは「12時希望」など送信
    // 「12時希望」など初回以外の最初のメッセージ時
    // 　→ 「体験後に、案内メール等をお送りしますがよろしいですか。はい、いいえでお答えください。」を送信
    // ユーザーが「はい」または「いいえ」を送信
    // 　→ 「かしこまりました」を返信
    // 　→ 以降のメッセージはすべて「メッセージを受け取りました: (内容)」で返信

    // メッセージイベント処理
    private function handleMessageEvent(array $event)
    {
        $replyToken   = $event['replyToken'] ?? null;
        $userId       = $event['source']['userId'] ?? null;
        $userMessage  = trim($event['message']['text'] ?? '');

        Log::info("Message event received from userId = $userId, message = $userMessage");

        // DB 保存
        $line_message = new Line_Message();
        $line_message->line_user_id    = $userId;
        $line_message->line_message_id = $event['message']['id'];
        $line_message->text            = $userMessage;
        $line_message->save();

        // ユーザー状態確認
        $trial_user = Line_Trial_Users::where('line_user_id', $userId)->first();

        // --- 初回登録 ---
        if (!$trial_user) {
            $trial_user = new Line_Trial_Users();
            $trial_user->line_user_id = $userId;
            $trial_user->users_name   = $userMessage;
            $trial_user->status       = 'registered'; // 状態を記録
            $trial_user->save();

            $msg = "体験会のご予約ありがとうございます！\n"
                . "ご希望の時間を教えてください。\n\n"
                . "※ご希望に添えない場合もありますので、\n"
                . "あらかじめご了承ください。";

            if (!$this->replyMessage($replyToken, $msg)) {
                $this->pushMessage($userId, $msg);
            }
            return;
        }

        // 処理内容
        // ステップ	説明
        // ①	ユーザーのメッセージから「時刻らしき部分」を正規表現で抽出
        // ②	例：「12時希望」→ 12:00:00、「13:30」→ 13:30:00 に変換
        // ③	$trial_user->reservationed_at に保存
        // ④	返信メッセージに「12:00ですね。」などを含めて送信
        // （自然文対応）
        // ✅ 対応例
        // ユーザーの入力	保存される reservationed_at	返信内容
        // 12時希望	12:00:00	12時ですね。
        // 午後1時半がいいです	13:30:00	13時30分ですね。
        // 14:45希望	14:45:00	14時45分ですね。
        // 3時ごろ	03:00:00	3時ですね。
        // 午後7時	19:00:00	19時ですね。
        // --- 2段階目: 登録済だが案内許可未確認 ---
        if ($trial_user->status === 'registered') {
            $timeStr = TimeExtractor::extractTime($userMessage);

            if ($timeStr) {
                $trial_user->reservationed_at = $timeStr;
            }

            $trial_user->status = 'waiting_consent';
            $trial_user->save();

            $timeDisplay = TimeExtractor::formatTimeJa($timeStr);
            $msg = ($timeDisplay ? "{$timeDisplay}ですね。\n" : "")
                . "体験後に、案内メール等をお送りしますがよろしいですか。\n\n"
                . "はい、いいえでお答えください。";

            if (!$this->replyMessage($replyToken, $msg)) {
                $this->pushMessage($userId, $msg);
            }
            return;
        }
        // --- 3段階目: 同意確認中 ---
        if ($trial_user->status === 'waiting_consent') {
            if (in_array($userMessage, ['はい', 'いいえ'])) {
                $trial_user->status = 'finished';
                $trial_user->save();

                $msg = "かしこまりました。";
            } else {
                // 「はい」「いいえ」以外なら再度促す
                $msg = "「はい」または「いいえ」でお答えください。";
            }

            if (!$this->replyMessage($replyToken, $msg)) {
                $this->pushMessage($userId, $msg);
            }
            return;
        }

        // --- 4段階目: 完了後は通常返信 ---
        if ($trial_user->status === 'finished') {
            $msg = "メッセージを受け取りました:\n\n{$userMessage}";
            if (!$this->replyMessage($replyToken, $msg)) {
                $this->pushMessage($userId, $msg);
            }
            return;
        }

        Log::info("Message event processing finished for userId = $userId");
    }

}
