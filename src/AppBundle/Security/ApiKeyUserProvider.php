<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;

use AppBundle\Entity\ApiUserAccessToken;
use AppBundle\Entity\User;

class ApiKeyUserProvider implements UserProviderInterface{

    /**
    * @var EntityManager
    */
    protected $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    public function getUsernameForApiKey($apiKey){
    
        $repository = $this->em->getRepository(ApiUserAccessToken::class);

        if (!($apiAccess = $repository->findOneByToken($apiKey))) {
            return null;
        }
        return $apiAccess->getUser()->getUsername();
    }

    public function loadUserByUsername($username){
        $repository = $this->em->getRepository(User::class);
        return $repository->findByEmailOrUsername($username);
    }

    public function refreshUser(UserInterface $user){
        throw new UnsupportedUserException();
    }

    public function supportsClass($class){
        return User::class === $class;
    }
}