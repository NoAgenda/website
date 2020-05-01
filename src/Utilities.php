<?php
/*
 * (c) Tim Goudriaan <tim@codedmonkey.com>
 */

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
}
