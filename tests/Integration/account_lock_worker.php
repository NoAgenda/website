<?php

declare(strict_types=1);

use App\Entity\MastodonAnnouncement;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
require dirname(__DIR__, 2).'/config/bootstrap.php';

$marker = $argv[1] ?? throw new RuntimeException('Missing marker path.');
$kernel = new Kernel('test', true);
$kernel->boot();
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$repository = $entityManager->getRepository(MastodonAnnouncement::class);
$repository->withAccountLock('primary', static function () use ($marker): void {
    file_put_contents($marker, 'entered');
    usleep(1000000);
});
$kernel->shutdown();
