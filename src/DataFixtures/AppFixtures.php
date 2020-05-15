<?php

namespace App\DataFixtures;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        foreach ($this->loadUsers() as list($username, $email, $plainPassword, $roles)) {
            $user = (new User)
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
            $episode = (new Episode)
                ->setCode($data['code'])
                ->setName($data['name'])
                ->setAuthor('Adam Curry & John C. Dvorak')
                ->setPublishedAt(new \DateTime($data['publishedAt'] . ' 11AM'))
                ->setCoverUri('http://placehold.it/512x512')
                ->setRecordingUri($data['recordingUri'])
            ;

            $part = (new EpisodeChapter)
                ->setEpisode($episode)
                ->setCreator($this->getReference('user-Woodstock'))
                ->setName('Start of Show')
                ->setStartsAt(0)
            ;

            $manager->persist($episode);
            $manager->persist($part);
        }

        $manager->flush();
    }

    public function loadEpisodes()
    {
        return [];
    }

    public function loadUsers()
    {
        yield ['Woodstock', 'admin@noagendaexperience.com', 'test', ['ROLE_SUPER_ADMIN']];
    }
}
