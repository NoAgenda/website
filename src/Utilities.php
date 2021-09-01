<?php

namespace App;

class Utilities
{
    public static function parsePrettyTimestamp(string $prettyTimestamp): int
    {
        if (strpos($prettyTimestamp, ':')) {
            $components = explode(':', $prettyTimestamp);

            if (count($components) >= 3) {
                list($hours, $minutes, $seconds) = $components;
            } else {
                $hours = 0;
                list($minutes, $seconds) = $components;
            }

            $timestamp = (int) $seconds;
            $timestamp += (int) $minutes * 60;
            $timestamp += (int) $hours * 60 * 60;

            return $timestamp;
        }

        return (int) $prettyTimestamp;
    }

    public static function prettyTimestamp($value): string
    {
        $value = (int) $value;

        $hours = floor($value / 60 / 60);
        $value = $value - ($hours * 60 * 60);

        $minutes = floor($value / 60);
        $value = $value - ($minutes * 60);

        $seconds = (string) $value;
        $seconds = strlen($seconds) === 1 ? '0' . $seconds : $seconds;

        if ($hours == 0) {
            return implode(':', [$minutes, $seconds]);
        }

        $minutes = strlen($minutes) == 1 ? '0' . $minutes : $minutes;

        return implode(':', [$hours, $minutes, $seconds]);
    }
}
