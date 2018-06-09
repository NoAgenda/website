<?php

namespace App\DataFixtures;

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

            $manager->persist($user);
        }

        $manager->flush();
    }

    public function loadUsers()
    {
        yield ['codedmonkey', 'tim@codedmonkey.com', 'test', ['ROLE_SUPER_ADMIN']];
    }
}
