<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    const REFERENCE = "USER";

    public function load(ObjectManager $manager)
    {

        $roles = array("ROLE_AUTHOR","ROLE_ADMIN","ROLE_MODERATOR","ROLE_DEVELOPER");

        $user = new User();
        $user->setUsername("zakeszako");
        $user->setEmail("zakeszako@aaz.dev");
        $user->setPassword("zakeszako284520");
        $user->setRoles(array("ROLE_SUPER_ADMIN"));
        $manager->persist($user);
        $this->addReference(self::REFERENCE,$user);

        // create 10 users! Bam!
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setUsername('username '.$i);
            $user->setEmail("user$i@aaz.dev");
            $user->setPassword($user->getUsername());
            $user->setRoles(array($roles[array_rand($roles)],"ROLE_API"));
            $manager->persist($user);
        }

        $manager->flush();
    }
}