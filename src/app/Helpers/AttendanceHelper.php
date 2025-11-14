<?php

if (!function_exists('formatMinutesToHour')) {
    /**
     * 分単位の時間を H:i 形式に変換
     *
     * @param int|null $minutes
     * @return string
     */
    function formatMinutesToHour(?int $minutes): string
    {
        if ($minutes === null) {
            return '';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}
