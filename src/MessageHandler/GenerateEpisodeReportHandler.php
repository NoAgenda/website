<?php

namespace App\MessageHandler;

use App\Message\GenerateEpisodeReport;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GenerateEpisodeReportHandler implements MessageHandlerInterface
{
    public function __construct(
    ) {}

    public function __invoke(GenerateEpisodeReport $message): void
    {
    }
}
