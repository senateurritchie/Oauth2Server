<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\ApiAccessScope;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class ApiAccessScopeFixture extends Fixture{
    const REFERENCE = "API_SCOPE";
    public function load(ObjectManager $manager){

        $scopes = array(
            array(
                "name"=>"public_profile",
                "is_default"=>true,
                "description"=>"les informations public tel, le nom d'utilisateur..."
            ),
            array(
                "name"=>"email",
                "is_default"=>true,
                "description"=>"votre adresse email"
            ),
            array(
                "name"=>"phone",
                "is_default"=>true,
                "description"=>"votre numero de telephone"
            ),
            array(
                "name"=>"balance",
                "is_default"=>false,
                "description"=>"votre solde bancaire"
            ),
            array(
                "name"=>"transaction",
                "is_default"=>false,
                "description"=>"vos transactions"
            )
        );

        foreach ($scopes as $key => $el) {
            $scope = new ApiAccessScope();
            $scope->setName($el["name"]);
            $scope->setIsDefault($el["is_default"]);
            $scope->setDescription($el["description"]);
            $manager->persist($scope);
            if($key == 0){
                $this->addReference(self::REFERENCE,$scope);
            }
        } 
        $manager->flush();
    }
}