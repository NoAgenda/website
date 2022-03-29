<?php

namespace App\Crawling;

class Crawlers
{
    public static array $crawlers = [
        'bat_signal' => BatSignalCrawler::class,
        'chat_archive' => EpisodeChatArchiveMatcher::class,
        'cover' => EpisodeCoverCrawler::class,
        'duration' => EpisodeDurationCrawler::class,
        'feed' => FeedCrawler::class,
        'recording_time' => EpisodeRecordingTimeMatcher::class,
        'shownotes' => EpisodeShownotesCrawler::class,
        'transcript' => EpisodeTranscriptCrawler::class,
        'youtube' => YoutubeCrawler::class,
    ];
}
