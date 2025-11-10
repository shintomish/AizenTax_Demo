<?php

// 2025/11/10 11/09体験会 10時45分→10時ですね対応
namespace App\Helpers;

class TimeExtractor
{
    /**
     * ユーザー入力文字列から時間を抽出し、"HH:MM:00" 形式で返す
     * 
     * @param string $text 入力メッセージ
     * @return string|null 時刻（例: "13:30:00"）または null
     */
    public static function extractTime(?string $text): ?string
    {
        if (!$text) return null;

        $isPM = (mb_strpos($text, '午後') !== false || mb_stripos($text, 'PM') !== false);
        $isAM = (mb_strpos($text, '午前') !== false || mb_stripos($text, 'AM') !== false);

        // 「10時45分」「13:30」「午後3時半」など対応
        if (preg_match('/([0-9]{1,2})\s*(?:時|[:：])\s*([0-9]{1,2})?\s*(?:分|半)?/u', $text, $m)) {
            $hour = intval($m[1]);
            $minute = 0;

            // 分指定
            if (!empty($m[2])) {
                $minute = intval($m[2]);
            } elseif (preg_match('/半/u', $text)) {
                $minute = 30;
            }

            // 午後補正（ただし12時台は補正しない）
            if ($isPM && $hour < 12) {
                $hour += 12;
            }

            // 午前で12時の場合は0時に補正
            if ($isAM && $hour == 12) {
                $hour = 0;
            }

            return sprintf('%02d:%02d:00', $hour, $minute);
        }

        return null;
    }

    /**
     * 時刻を日本語表記で返す（例：10時45分 / 15時 / null → null）
     */
    public static function formatTimeJa(?string $timeStr): ?string
    {
        if (!$timeStr) return null;

        $hour = intval(substr($timeStr, 0, 2));
        $minute = intval(substr($timeStr, 3, 2));

        return $minute == 0 ? "{$hour}時" : "{$hour}時{$minute}分";
    }
}
