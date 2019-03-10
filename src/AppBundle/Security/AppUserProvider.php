<?php
namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;


use AppBundle\Entity\User;

class AppUserProvider implements UserProviderInterface{

    /**
    * @var EntityManager
    */
    protected $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    public function loadUserByUsername($username){

        $repository = $this->em->getRepository(User::class);

        if (!($user = $repository->findByEmailOrUsername($username))) {
            throw new UsernameNotFoundException(
                sprintf('Username or Email "%s" does not exist.', $username)
            );
        }

        return $user;
    }

    public function refreshUser(UserInterface $user){
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getEmail());
    }

    public function supportsClass($class){
        return User::class === $class;
    }
}
