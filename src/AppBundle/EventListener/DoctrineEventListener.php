<?php
namespace AppBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use AppBundle\Entity\User;
use AppBundle\Entity\ApiUserAccessCode;
use AppBundle\Entity\ApiUserAccessToken;
use AppBundle\Entity\Application;

class DoctrineEventListener{

	private $encoder;

	public function __construct(UserPasswordEncoderInterface $encoder){
		$this->encoder = $encoder;
	}

	public static function generateToken($length = 8){
		return substr(trim(base64_encode(bin2hex(openssl_random_pseudo_bytes(64,$ok))),"="),0,$length);
	}

	public function prePersist(LifecycleEventArgs $args){
		$entity = $args->getEntity();

		
		if ($entity instanceof ApiUserAccessCode) {

			$timestamp = time()+(60*3); // 3 minutes
        	$expires = new \Datetime();
        	$expires->setTimestamp($timestamp);
        	$entity->setExpires($expires);

        	$entity->setDate(new \Datetime());
        	$entity->setCode(self::generateToken(32));
        }
        
        else if ($entity instanceof Application) {
        	$entity->setDate(new \Datetime());
        	$entity->setToken(self::generateToken(30));
        	$entity->setSecret(self::generateToken(15));
        }
        else if ($entity instanceof ApiUserAccessToken) {
			$timestamp = time()+(86400*90); // 90 jours
        	$expires = new \Datetime();
        	$expires->setTimestamp($timestamp);
        	$entity->setExpires($expires);
        	$entity->setDate(new \Datetime());
        	$entity->setToken(self::generateToken(64));
        	$entity->setRefreshToken(self::generateToken(32));
        	$entity->setIsTokenRevoke(0);
        	$entity->setIsRefreshTokenRevoke(0);
        }
		else if ($entity instanceof User) {
            $password = $this->encoder->encodePassword($entity, $entity->getPassword());
        	$entity->setPassword($password);
        }

       
	}
}