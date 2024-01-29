<?php

namespace App\Scheduler;

use App\Message\Crawl;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\CallbackTrigger;
use Symfony\Component\Scheduler\Trigger\PeriodicalTrigger;
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
                // Crawl feed
                RecurringMessage::trigger(
                    new CallbackTrigger(function (\DateTimeImmutable $run) {
                        $hotTrigger = new PeriodicalTrigger('5 minutes');
                        $coldTrigger = new PeriodicalTrigger('1 hour');

                        $nextColdRun = $coldTrigger->getNextRunDate($run);
                        $nextHotRun = $hotTrigger->getNextRunDate($run);

                        while (!$this->isBetween($nextHotRun, [4, 7], 20, 23)) {
                            $nextHotRun = $hotTrigger->getNextRunDate($nextHotRun);
                        }

                        return $nextHotRun < $nextColdRun ? $nextHotRun : $nextColdRun;
                    }, 'Every 5 minutes (hot) or every hour (cold)'),
                    new Envelope(new Crawl('feed', null), [new BusNameStamp('crawling.bus')])
                ),

                // Crawl bat signal
                RecurringMessage::trigger(
                    new CallbackTrigger(function (\DateTimeImmutable $run) {
                        $trigger = new PeriodicalTrigger('5 minutes');
                        $nextRun = $trigger->getNextRunDate($run);

                        while (!$this->isBetween($nextRun, [4, 7], 16, 19)) {
                            $nextRun = $trigger->getNextRunDate($nextRun);
                        }

                        return $nextRun;
                    }, 'Every 5 minutes (when hot)'),
                    new Envelope(new Crawl('bat_signal', null), [new BusNameStamp('crawling.bus')])
                ),

                // Crawl Animated No Agenda
                RecurringMessage::every(
                    '2 hours',
                    new Envelope(new Crawl('youtube', null), [new BusNameStamp('crawling.bus')])
                ),
            )
            ->stateful($this->cache);
    }

    private static function isBetween(\DateTimeInterface $run, array $daysOfTheWeek, int $fromHour, int $untilHour): bool
    {
        $runDayOfTheWeek = (int) $run->format('N');
        $runHourOfTheDay = (int) $run->format('G');

        return in_array($runDayOfTheWeek, $daysOfTheWeek) && $runHourOfTheDay >= $fromHour && $runHourOfTheDay < $untilHour;
    }
}
