<?php

namespace App\DataFixtures;

use App\Entity\Episode;
use App\Entity\NetworkSite;
use App\Entity\User;
use App\Entity\UserAccount;
use App\Entity\Video;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->loadUsers() as list($username, $email, $plainPassword, $roles)) {
            $user = (new User())
                ->setReviewed(true);

            $account = (new UserAccount())
                ->setUsername($username)
                ->setUser($user)
                ->setEmail($email)
                ->setPlainPassword($plainPassword);

            foreach ($roles as $role) {
                $account->addRole($role);
            }

            $manager->persist($account);
        }

        $manager->flush();

        foreach ($this->loadEpisodes() as $data) {
            $episode = (new Episode())
                ->setCode($data['code'])
                ->setName($data['name'])
                ->setAuthor('Adam Curry & John C. Dvorak')
                ->setPublishedAt(new \DateTime($data['publishedAt']))
                ->setPublished(true)
                ->setDuration($data['duration'])
                ->setRecordingUri($data['recordingUri'])
                ->setCoverUri($data['coverUri'])
                ->setCoverPath($data['coverPath'])
                ->setPublicShownotesUri($data['publicShownotesUri'])
                ->setShownotesUri($data['shownotesUri'])
                ->setShownotesPath($data['shownotesPath'])
                ->setTranscriptUri($data['transcriptUri'])
                ->setTranscriptPath($data['transcriptPath'])
                ->setChatArchivePath($data['chatArchivePath'] ?? null)
                ->setChaptersUri($data['chaptersUri'] ?? null)
                ->setChaptersPath($data['chaptersPath'] ?? null);

            $manager->persist($episode);
        }

        foreach ($this->loadNetworkSites() as $data) {
            $site = (new NetworkSite())
                ->setName($data['name'])
                ->setIcon($data['icon'])
                ->setDescription($data['description'])
                ->setUri($data['uri'])
                ->setDisplayUri($data['displayUri'])
                ->setPriority($data['priority']);

            $manager->persist($site);
        }

        foreach ($this->loadVideos() as $data) {
            $video = (new Video($data['youtubeId']))
                ->setTitle($data['title'])
                ->setPublishedAt(new \DateTime($data['publishedAt']));

            $manager->persist($video);
        }

        $manager->flush();
    }

    public function loadEpisodes(): iterable
    {
        yield [
            'code' => '1240',
            'name' => 'Meat Must Flow',
            'author' => 'Adam Curry & John C. Dvorak',
            'publishedAt' => '2020-05-07',
            'duration' => 11010,
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1240-2020-05-07-Final.mp3',
            'coverUri' => 'https://noagendaassets.com/enc/1588880905.893_na-1240-art-feed.png',
            'coverPath' => '/srv/app/docker/storage/covers/1240.png',
            'publicShownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1240M-F99GP9GcZq67jjGxnmDW106bKW3pr4.html',
            'shownotesUri' => 'http://adam.curry.com/opml/NoAgendaEpisode1240M-F99GP9GcZq67jjGxnmDW106bKW3pr4.opml',
            'shownotesPath' => '/srv/app/docker/storage/shownotes/1240.xml',
            'transcriptUri' => 'https://www.noagendashow.net/media/transcripts/NA-1240-2020-05-07-Final.srt',
            'transcriptPath' => '/srv/app/docker/storage/transcripts/1240.srt',
            'chatArchivePath' => '/srv/app/docker/storage/chat_archives/1240.json',
        ];

        yield [
            'code' => '1241',
            'name' => 'Curtain Wranglers',
            'author' => 'Adam Curry & John C. Dvorak',
            'publishedAt' => '2020-05-10',
            'duration' => 12507,
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1241-2020-05-10-Final.mp3',
            'coverUri' => 'https://noagendaassets.com/enc/1589141237.285_na-1241-art-feed.png',
            'coverPath' => '/srv/app/docker/storage/covers/1241.png',
            'publicShownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1241C-XqBF4nm8G2R4WRK2hHftm0Pw1PBrgg.html',
            'shownotesUri' => 'http://adam.curry.com/opml/NoAgendaEpisode1241C-XqBF4nm8G2R4WRK2hHftm0Pw1PBrgg.opml',
            'shownotesPath' => '/srv/app/docker/storage/shownotes/1241.xml',
            'transcriptUri' => 'https://www.noagendashow.net/media/transcripts/NA-1241-2020-05-10-Final.srt',
            'transcriptPath' => '/srv/app/docker/storage/transcripts/1241.srt',
            'chatArchivePath' => '/srv/app/docker/storage/chat_archives/1241.json',
        ];

        yield [
            'code' => '1242',
            'name' => 'Smokin\' Hot',
            'author' => 'Adam Curry & John C. Dvorak',
            'publishedAt' => '2020-05-14',
            'duration' => 11790,
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1242-2020-05-14-Final.mp3',
            'coverUri' => 'https://noagendaassets.com/enc/1589486598.491_na-1242-art-feed.png',
            'coverPath' => '/srv/app/docker/storage/covers/1242.png',
            'publicShownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1242S-dHMsL7fj385gtjDwVNn7422WDQblG4.html',
            'shownotesUri' => 'http://adam.curry.com/opml/NoAgendaEpisode1242S-dHMsL7fj385gtjDwVNn7422WDQblG4.opml',
            'shownotesPath' => '/srv/app/docker/storage/shownotes/1242.xml',
            'transcriptUri' => 'https://www.noagendashow.net/media/transcripts/NA-1242-2020-05-14-Final.srt',
            'transcriptPath' => '/srv/app/docker/storage/transcripts/1242.srt',
            'chatArchivePath' => '/srv/app/docker/storage/chat_archives/1242.json',
        ];

        yield [
            'code' => '1243',
            'name' => 'Obamable',
            'author' => 'Adam Curry & John C. Dvorak',
            'publishedAt' => '2020-05-17',
            'duration' => 11085,
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1243-2020-05-17-Final.mp3',
            'coverUri' => 'https://noagendaassets.com/enc/1589744564.723_na-1243-art-feed.png',
            'coverPath' => '/srv/app/docker/storage/covers/1243.png',
            'publicShownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1243O-XVFr4Sf7Jhh9ddD5WsZQM3C70ZCgZb.html',
            'shownotesUri' => 'http://adam.curry.com/opml/NoAgendaEpisode1243O-XVFr4Sf7Jhh9ddD5WsZQM3C70ZCgZb.opml',
            'shownotesPath' => '/srv/app/docker/storage/shownotes/1243.xml',
            'transcriptUri' => 'https://www.noagendashow.net/media/transcripts/NA-1243-2020-05-17-Final.srt',
            'transcriptPath' => '/srv/app/docker/storage/transcripts/1243.srt',
            'chatArchivePath' => '/srv/app/docker/storage/chat_archives/1243.json',
        ];

        yield [
            'code' => '1598',
            'name' => 'Guardrails',
            'author' => 'Adam Curry & John C. Dvorak',
            'publishedAt' => '2023-10-12',
            'duration' => 10246,
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1598-2023-10-12-Final.mp3',
            'coverUri' => 'https://noagendaassets.com/enc/1697145462.234_na-1598-art-feed.jpg',
            'coverPath' => '/srv/app/docker/storage/covers/1598.png',
            'publicShownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1598G-F1JvMfM7fC3P2fLs1tjRQPTGtVBzdH.html',
            'shownotesUri' => 'http://adam.curry.com/opml/NoAgendaEpisode1598G-F1JvMfM7fC3P2fLs1tjRQPTGtVBzdH.opml',
            'shownotesPath' => '/srv/app/docker/storage/shownotes/1598.xml',
            'transcriptUri' => 'https://mp3s.nashownotes.com/NA-1598-Captions.srt',
            'transcriptPath' => '/srv/app/docker/storage/transcripts/1598.srt',
            'chaptersUri' => 'https://chapters.hypercatcher.com/http:feed.nashownotes.comrss.xml/http:1598.noagendanotes.com',
            'chaptersPath' => '/srv/app/docker/storage/chapters/1598.json',
        ];

        yield [
            'code' => '1599',
            'name' => 'Drop the Op',
            'author' => 'Adam Curry & John C. Dvorak',
            'publishedAt' => '2023-10-15',
            'duration' => 11245,
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1599-2023-10-15-Final.mp3',
            'coverUri' => 'https://noagendaassets.com/enc/1697405313.159_na-1599-art-feed.jpg',
            'coverPath' => '/srv/app/docker/storage/covers/1599.png',
            'publicShownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1599D-FC3bRtKpGHQbhw2gRSpJ9THr6T8P4g.html',
            'shownotesUri' => 'http://adam.curry.com/opml/NoAgendaEpisode1599D-FC3bRtKpGHQbhw2gRSpJ9THr6T8P4g.opml',
            'shownotesPath' => '/srv/app/docker/storage/shownotes/1599.xml',
            'transcriptUri' => 'https://mp3s.nashownotes.com/NA-1599-Captions.srt',
            'transcriptPath' => '/srv/app/docker/storage/transcripts/1599.srt',
            'chaptersUri' => 'https://chapters.hypercatcher.com/http:feed.nashownotes.comrss.xml/http:1599.noagendanotes.com',
            'chaptersPath' => '/srv/app/docker/storage/chapters/1599.json',
        ];

        yield [
            'code' => '1600',
            'name' => 'Unpack It',
            'author' => 'Adam Curry & John C. Dvorak',
            'publishedAt' => '2023-10-19',
            'duration' => 13117,
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1600-2023-10-19-Final.mp3',
            'coverUri' => 'https://noagendaassets.com/enc/1697753376.704_na-1600-art-feed.jpg',
            'coverPath' => '/srv/app/docker/storage/covers/1600.png',
            'publicShownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1600U-rm9njDsr92wSxFKqwgrX29LZnhTxM7.html',
            'shownotesUri' => 'http://adam.curry.com/opml/NoAgendaEpisode1600U-rm9njDsr92wSxFKqwgrX29LZnhTxM7.opml',
            'shownotesPath' => '/srv/app/docker/storage/shownotes/1600.xml',
            'transcriptUri' => 'https://mp3s.nashownotes.com/NA-1600-Captions.srt',
            'transcriptPath' => '/srv/app/docker/storage/transcripts/1600.srt',
            'chaptersUri' => 'https://chapters.hypercatcher.com/http:feed.nashownotes.comrss.xml/http:1600.noagendanotes.com',
            'chaptersPath' => '/srv/app/docker/storage/chapters/1600.json',
        ];

        yield [
            'code' => '1601',
            'name' => 'Unkool & The Gang',
            'author' => 'Adam Curry & John C. Dvorak',
            'publishedAt' => '2023-10-22',
            'duration' => 11958,
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1601-2023-10-22-Final.mp3',
            'coverUri' => 'https://noagendaassets.com/enc/1698011763.8_na-1601-art-feed.jpg',
            'coverPath' => '/srv/app/docker/storage/covers/1601.png',
            'publicShownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1601U-mRMHnxjHgmwrG00jpGZpMnp1FDFMdT.html',
            'shownotesUri' => 'http://adam.curry.com/opml/NoAgendaEpisode1601U-mRMHnxjHgmwrG00jpGZpMnp1FDFMdT.opml',
            'shownotesPath' => '/srv/app/docker/storage/shownotes/1601.xml',
            'transcriptUri' => 'https://mp3s.nashownotes.com/NA-1601-Captions.srt',
            'transcriptPath' => '/srv/app/docker/storage/transcripts/1601.srt',
            'chaptersUri' => 'https://chapters.hypercatcher.com/http:feed.nashownotes.comrss.xml/http:1601.noagendanotes.com',
            'chaptersPath' => '/srv/app/docker/storage/chapters/1601.json',
        ];
    }

    public function loadNetworkSites(): iterable
    {
        yield [
            'name' => 'Landing Page',
            'icon' => null,
            'description' => 'The official landing page is the primary source of all things No Agenda.',
            'uri' => 'http://noagendashow.com',
            'displayUri' => 'noagendashow.com',
            'priority' => 1,
        ];

        yield [
            'name' => 'Livestream',
            'icon' => null,
            'description' => 'The official No Agenda livestream and chatroom.',
            'uri' => 'http://noagendastream.com',
            'displayUri' => 'noagendastream.com',
            'priority' => 2,
        ];

        yield [
            'name' => 'NA Social',
            'icon' => 'fab fa-mastodon',
            'description' => 'The official No Agenda social network.',
            'uri' => 'https://noagendasocial.com',
            'displayUri' => 'noagendasocial.com',
            'priority' => 3,
        ];
    }

    public function loadUsers(): iterable
    {
        yield [$_SERVER['APP_ADMIN_USER'], $_SERVER['APP_ADMIN_EMAIL'] ?? 'admin@noagendaexperience.com', 'test', ['ROLE_SUPER_ADMIN']];
    }

    public function loadVideos(): iterable
    {
        yield [
            'title' => 'Helicopter Money',
            'publishedAt' => '2020-05-07',
            'youtubeId' => '8fKAN5A2CLo',
        ];

        yield [
            'title' => 'How To Infiltrate the MSM (AKA M5M)',
            'publishedAt' => '2020-05-06',
            'youtubeId' => 'sGp11kDwTY8',
        ];

        yield [
            'title' => 'Multiple Billions Annually',
            'publishedAt' => '2020-05-05',
            'youtubeId' => '81D4uwwh41s',
        ];
    }
}
