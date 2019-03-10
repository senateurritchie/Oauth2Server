<?php 
	namespace AppBundle\Service\GrantType;

	use Symfony\Component\HttpFoundation\Request;
	use Doctrine\ORM\EntityManager;
	use Symfony\Component\DependencyInjection\ContainerInterface;

	use AppBundle\Entity\Application;
	use AppBundle\Entity\ApiAccessScope;
	use AppBundle\Entity\AppAccessScope;
	use AppBundle\Entity\ApiUserAccessToken;
	use AppBundle\Entity\ApiUserAccessTokenScope;
	use AppBundle\Entity\AccessToken;

    use AppBundle\Service\Exception\Oauth2Exception;

	abstract class GrantType{

		/**
		* @var Symfony\Component\HttpFoundation\Request
		*/
		protected $request;
		/**
		* @var Doctrine\ORM\EntityManager
		*/
		protected $em;
		/**
		* @var Symfony\Component\DependencyInjection\ContainerInterface
		*/
		protected $container;

		/**
		* @var AppBundle\Entity\Application
		*/
		protected $client;
		

		public function __construct(Application $client,ContainerInterface $container){
			$this->client 		= $client;
			$this->request 		= $container->get('request_stack')->getCurrentRequest();
			$this->em 			= $container->get('doctrine.orm.default_entity_manager');
			$this->container 	= $container;
		}

		public function setClient(Application &$i){
			$this->client = $i;
		}
		public function &getClient(){
			return $this->client;
		}

		public function getUser(){
	        if (!$this->container->has('security.token_storage')) {
	            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
	        }

	        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
	            return;
	        }

	        if (!is_object($user = $token->getUser())) {
	            // e.g. anonymous authentication
	            return;
	        }
	        return $user;
	    }

	    /**
	    * @param string $source
	    */
	    public function loadGrantedScopes($source){
	    	$source = strip_tags(trim($source));
	    	$granted_scopes = [];
	    	$scopes = explode(" ",$source);

            $rep_ = $this->em->getRepository(ApiAccessScope::class);
            // sauvegarde des scopes demandés
            if(count($scopes)){
                $rep = $this->em->getRepository(AppAccessScope::class);
                foreach ($scopes as $key => $el) {
                	if(!trim($el)) continue;

                    if(($scope = $rep_->findOneByName(trim($el)))){
                        if(($e = $rep->findOneBy(array("application"=>$this->client,"scope"=>$scope)))){
                            $granted_scopes[] = $e;
                        }
                        else{
                        	throw new Oauth2Exception(
				    			"invalid_scope",400,
				    			"scope '$el' is invalid"
				    		);
                        }
                    }
                    else{
	    				throw new Oauth2Exception(
			    			"invalid_scope",400,
			    			"scope '$el' is unknow"
			    		);
	        		}
                }
            }

            if(!count($granted_scopes)) {
                // load defaults scope
                $defaults = $rep_->findBy(array("is_default"=>1));
                foreach ($defaults as $key => $scope) {
                    if(($e = $rep->findOneBy(array("application"=>$this->client,"scope"=>$scope)))) {
                        $granted_scopes[] = $e;
                    }
                }
            }

            return $granted_scopes;
	    }

	    /**
	    * @param AccessToken $token
	   	* @param AppAccessScope[] $appScopes
	    */
	   	public function saveGrantedScopes(AccessToken $token, array $appScopes){
	   		$data = [];
	   		foreach ($appScopes as $key => $appScope) {
	   			$token_scope = new ApiUserAccessTokenScope();
                $token_scope->setToken($token);
                $token_scope->setScope($appScope);
                $token_scope->setDate(new \Datetime());
                $this->em->persist($token_scope);
                $data[] = $appScope->getScope()->getName();
	   		}
	   		return $data;
	   	}

	   	/**
	   	* demande d'autorisation pour les authorization_code, implicit
	   	* grant type
	   	*
	   	* @return string
	   	*/
		abstract public function authorize();
		/**
	   	* affiche les permissions requises à l'utilisateur pour acceptation
	   	* ou refus de sa part
	   	*/
		abstract public function authorizePrompt();
		/**
	   	* methode qui produit les access_token et optionnelement les refresh_token
	   	*/
		abstract public function token();
	}
?>