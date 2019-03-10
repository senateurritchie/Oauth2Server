<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\AppAccessScope;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class AppAccessScopeFixture extends Fixture implements DependentFixtureInterface
{
    const REFERENCE = "APP_SCOPE";

    public function load(ObjectManager $manager){
        $appscope = new AppAccessScope();
        $appscope->setApplication($this->getReference(ApplicationFixture::REFERENCE));
        $appscope->setScope($this->getReference(ApiAccessScopeFixture::REFERENCE));
        $appscope->setDate(new \Datetime());
        $manager->persist($appscope);
        $manager->flush();
    }

    public function getDependencies(){
        return array(
            ApplicationFixture::class,
            ApiAccessScopeFixture::class
        );
    }
}