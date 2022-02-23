<?php

namespace App\DataFixtures;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\NetworkSite;
use App\Entity\User;
use App\Entity\Video;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        foreach ($this->loadUsers() as list($username, $email, $plainPassword, $roles)) {
            $user = (new User())
                ->setUsername($username)
                ->setEmail($email)
                ->setPlainPassword($plainPassword)
            ;

            foreach ($roles as $role) {
                $user->addRole($role);
            }

            $this->setReference('user-' . $username, $user);

            $manager->persist($user);
        }

        foreach ($this->loadEpisodes() as $data) {
            $episode = (new Episode())
                ->setCode($data['code'])
                ->setName($data['name'])
                ->setAuthor('Adam Curry & John C. Dvorak')
                ->setPublishedAt(new \DateTime($data['publishedAt'] . ' 11AM'))
                ->setCoverUri($data['coverUri'])
                ->setRecordingUri($data['recordingUri'])
                ->setShownotesUri($data['shownotesUri'])
                ->setTranscriptUri($data['transcriptUri'])
                ->setChatMessages(true)
                ->setTranscript(true)
                ->setTranscriptType('json')
            ;

            $chapter = (new EpisodeChapter())
                ->setEpisode($episode)
                ->setCreator($this->getReference('user-Woodstock'))
                ->setName('Start of Show')
                ->setStartsAt(0)
            ;

            $manager->persist($episode);
            $manager->persist($chapter);
        }

        foreach ($this->loadNetworkSites() as $data) {
            $site = (new NetworkSite())
                ->setName($data['name'])
                ->setIcon($data['icon'])
                ->setDescription($data['description'])
                ->setUri($data['uri'])
                ->setDisplayUri($data['displayUri'])
                ->setPriority($data['priority'])
            ;

            $manager->persist($site);
        }

        foreach ($this->loadVideos() as $data) {
            $video = (new Video())
                ->setTitle($data['title'])
                ->setPublishedAt(new \DateTime($data['publishedAt']))
                ->setYoutubeId($data['youtubeId'])
            ;

            $manager->persist($video);
        }

        $manager->flush();
    }

    public function loadEpisodes()
    {
        yield [
            'code' => '1243',
            'name' => 'Obamable',
            'publishedAt' => '2020-05-17',
            'coverUri' => 'http://adam.curry.com/enc/1589744564.723_na-1243-art-feed.png',
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1243-2020-05-17-Final.mp3',
            'shownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1243O-XVFr4Sf7Jhh9ddD5WsZQM3C70ZCgZb.html',
            'transcriptUri' => 'https://natranscript.online/tr/wp-content/uploads/2020/05/1243-transcript.opml',
        ];

        yield [
            'code' => '1242',
            'name' => 'Smokin\' Hot',
            'publishedAt' => '2020-05-14',
            'coverUri' => 'http://adam.curry.com/enc/1589486598.491_na-1242-art-feed.png',
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1242-2020-05-14-Final.mp3',
            'shownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1242S-dHMsL7fj385gtjDwVNn7422WDQblG4.html',
            'transcriptUri' => 'https://natranscript.online/tr/wp-content/uploads/2020/05/1242-transcript.opml',
        ];

        yield [
            'code' => '1241',
            'name' => 'Curtain Wranglers',
            'publishedAt' => '2020-05-10',
            'coverUri' => 'http://adam.curry.com/enc/1589141237.285_na-1241-art-feed.png',
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1241-2020-05-10-Final.mp3',
            'shownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1241C-XqBF4nm8G2R4WRK2hHftm0Pw1PBrgg.html',
            'transcriptUri' => 'https://natranscript.online/tr/wp-content/uploads/2020/05/1241-transcript.opml',
        ];

        yield [
            'code' => '1240',
            'name' => 'Meat Must Flow',
            'publishedAt' => '2020-05-07',
            'coverUri' => 'http://adam.curry.com/enc/1588880905.893_na-1240-art-feed.png',
            'recordingUri' => 'https://mp3s.nashownotes.com/NA-1240-2020-05-07-Final.mp3',
            'shownotesUri' => 'http://adam.curry.com/html/NoAgendaEpisode1240M-F99GP9GcZq67jjGxnmDW106bKW3pr4.html',
            'transcriptUri' => 'https://natranscript.online/tr/wp-content/uploads/2020/05/1240-transcript.opml',
        ];
    }

    public function loadNetworkSites()
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

    public function loadUsers()
    {
        yield ['Woodstock', 'admin@noagendaexperience.com', 'test', ['ROLE_SUPER_ADMIN']];
    }

    public function loadVideos()
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
