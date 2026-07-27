<?php

namespace App\Scheduler;

use App\Message\Crawl;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('crawler')]
class CrawlingScheduleProvider implements ScheduleProviderInterface
{
    private Schedule $schedule;

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        if (isset($this->schedule)) {
            return $this->schedule;
        }

        return $this->schedule = (new Schedule())
            ->with(
                // Crawl feed and live item
                RecurringMessage::every(
                    '5 minutes',
                    new Envelope(new Crawl('feed', null), [new BusNameStamp('crawling.bus')])
                ),

                // Crawl Animated No Agenda
                RecurringMessage::every(
                    '2 hours',
                    new Envelope(new Crawl('youtube', null), [new BusNameStamp('crawling.bus')])
                ),
            )
            ->stateful($this->cache);
    }
}
