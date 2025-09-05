<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Line_Message;
use App\Models\Line_Trial_Users;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

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

    // メッセージイベント処理
    private function handleMessageEvent(array $event)
    {
        $replyToken   = $event['replyToken'] ?? null;
        $userId       = $event['source']['userId'] ?? null;
        $userMessage  = $event['message']['text'] ?? '';

        Log::info("Message event received from userId = $userId, message = $userMessage");

        // DB 保存
        $line_message = new Line_Message();
        $line_message->line_user_id    = $userId;
        $line_message->line_message_id = $event['message']['id'];
        $line_message->text            = $userMessage;
        $line_message->save();

        // 新規ユーザーなら登録
        $userCount = Line_Trial_Users::where('line_user_id', $userId)->count();
        if ($userCount == 0) {
            $trial_user = new Line_Trial_Users();
            $trial_user->line_user_id = $userId;
            $trial_user->users_name   = $userMessage;
            $trial_user->save();

            $msg = "体験会ご予約承りました。\n\n"
                . "体験会ブースにお越し頂いてから、\n"
                . "ご希望の予約時間を登録致します。";

            if (!$this->replyMessage($replyToken, $msg)) {
                // reply が失敗した場合は push に切り替える
                $this->pushMessage($userId, $msg);
            }
        } else {
            $msg = "メッセージを受け取りました: \n\n $userMessage";

            if (!$this->replyMessage($replyToken, $msg)) {
                $this->pushMessage($userId, $msg);
            }
        }

        Log::info("Message event processing finished for userId = $userId");
    }
}
