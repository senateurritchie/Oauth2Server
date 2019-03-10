<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\Application;
use AppBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ApplicationFixture extends Fixture implements DependentFixtureInterface
{
    const REFERENCE = "APP";

    public function load(ObjectManager $manager){
        $app = new Application();

        $user = new User();
        $user->setUsername("Afrimarket");
        $user->setEmail("info@afrimarket.ci");
        $user->setPassword("zakeszako284520");
        $user->setRoles(array("ROLE_APP"));
        $manager->persist($user);

        $app->setUser($user);
        $app->setName("Afrimarket");
        $app->setDescription("Afrimarket est une entreprise specialisée dans le e-commerce");
        $app->setWebsite("http://afrimarket.ci");
        $manager->persist($app);
        $manager->flush();
        $this->addReference(self::REFERENCE,$app);
    }

    public function getDependencies(){
        return array(
            UserFixture::class
        );
    }
}