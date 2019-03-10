<?php
namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;


use AppBundle\Entity\User;
use AppBundle\Entity\ApiUserAccessToken;

class ApiTokenUserProvider implements UserProviderInterface{

    /**
    * @var EntityManager
    */
    protected $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    public function loadUserByUsername($username){

        if($username == "anonymous"){
            return new User();
        }

        $repository = $this->em->getRepository(ApiUserAccessToken::class);

        if (!($apiAccess = $repository->findOneByToken($username))) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $username)
            );
        }

        return $apiAccess->getUser();
    }

    public function refreshUser(UserInterface $user){
       throw new UnsupportedUserException();
    }

    public function supportsClass($class){
        return ApiUserAccessToken::class === $class;
    }
}
