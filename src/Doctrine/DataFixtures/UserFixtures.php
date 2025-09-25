<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use function array_fill_callback;

final class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var User[] $users */
        $users = [];

        for ($i = 0; $i < 10; $i++) {
            $user = (new User())
                ->setEmail(sprintf('user+%d@email.com', $i))
                ->setPlainPassword('password')
                ->setUsername(sprintf('user+%d', $i));

            $users[] = $user;
        }

        foreach ($users as $user) {
            $manager->persist($user);
        }

        $manager->flush();
    }
}
