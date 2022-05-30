<?php

namespace App\Twig;

use App\Entity\Episode;
use App\Repository\ScheduledFileDownloadRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminExtension extends AbstractExtension
{
    public function __construct(
        private readonly ScheduledFileDownloadRepository $downloadRepository,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('adminFileDownload', [$this, 'adminFileDownload'], ['is_safe' => ['html' => true]]),
        ];
    }

    public function adminFileDownload(string $data, Episode $episode): string
    {
        if (!$fileDownload = $this->downloadRepository->findDownload($data, $episode)) {
            return '';
        }

        return sprintf('<span>Download queued.<br>Last modified at %s</span>', $fileDownload->getLastModifiedAt()->format('Y-m-d H:i:s'));
    }
}
