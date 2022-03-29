<?php

namespace App\Crawling;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class LivestreamRecorder implements RecorderInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function record(): void
    {
        $time = (new \DateTime())->format('YmdHis');
        $path = sprintf('%s/livestream_recordings/recording_%s', $_SERVER['APP_STORAGE_PATH'], $time);

        (new Filesystem())->mkdir(dirname($path));

        $this->logger->debug(sprintf('Starting livestream recording: %s', $time));

        $command = 'bin/scripts/record-livestream.bash "$DUMP_PATH"';
        $process = Process::fromShellCommandline($command)
            ->setTimeout(180)
        ;

        $process->mustRun(null, [
            'DUMP_PATH' => $path,
        ]);

        $this->logger->debug(sprintf('Finished livestream recording: %s', $time));
    }
}
