<?php

namespace App\Crawling;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class LivestreamRecorder
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function record(): void
    {
        $time = (new \DateTime())->format('YmdHis');
        $recordingPath = sprintf('%s/livestream_recordings/recording_%s', $_SERVER['APP_STORAGE_PATH'], $time);

        if (!is_dir(dirname($recordingPath))) {
            $filesystem = new Filesystem();
            $filesystem->mkdir(dirname($recordingPath));
        }

        $this->logger->debug('Starting recording...');

        $command = 'bin/scripts/record-livestream.bash "$DUMP_PATH"';
        $process = Process::fromShellCommandline($command);

        $process->setTimeout(180);

        $process->run(null, [
            'DUMP_PATH' => $recordingPath,
        ]);

        if (!$process->isSuccessful()) {
            throw new \Exception('An error occurred while creating the recording.');
        }

        $this->logger->debug('Finished recording, starting timeout');

        sleep(14 * 60);
    }
}
