<?php 
namespace AppBundle\Service;

use AppBundle\Service\Exception\Oauth2Exception;
use AppBundle\Service\Exception\Oauth2RedirectionException;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use AppBundle\Entity\Application;
use AppBundle\Entity\ApiAccessScope;
use AppBundle\Entity\ApiUserAccessToken;
use AppBundle\Entity\ApiUserAccessCode;
use AppBundle\Entity\AppAccessScope;
use AppBundle\Entity\ApiUserAccessTokenScope;

use AppBundle\Entity\User;
use AppBundle\Service\GrantType\AuthorizationCode;
use AppBundle\Service\GrantType\ClientCredentials;
use AppBundle\Service\GrantType\Implicit;
use AppBundle\Service\GrantType\Password;
use AppBundle\Service\GrantType\RefreshToken;


class Oauth2Server{
	/**
	* @var Symfony\Component\HttpFoundation\Request
	*/
	private $request;
	/**
	* @var Doctrine\Common\Persistence\ObjectManager
	*/
	private $em;
	/**
	* @var Symfony\Component\DependencyInjection\ContainerInterface
	*/
	private $container;

	/**
	* @var AppBundle\Service\GrantType\GrantType
	*/
	private $grant;

	public function __construct(ContainerInterface $container){
		$this->request = $container->get('request_stack')->getCurrentRequest();
		$this->em = $container->get('doctrine.orm.default_entity_manager');
		$this->container = $container;
	}


	public function setGrant($i){
		$this->grant = $i;
	}
	public function getGrant(){
		return $this->grant;
	}

	public function isClientExists($client_id){
		 // on verifie que le client existe
        $rep = $this->em->getRepository(Application::class);
        return $rep->findOneByToken($client_id);
	}

	public function loadScopes(){
		$rep = $this->em->getRepository(ApiAccessScope::class);
		return $rep->findAll();
	}

	

	public function checkDomainRestriction(Application $client){
		$referer = $this->request->server->get('HTTP_REFERER');

    	/*$origins = json_decode($client->getOrigins(),true);
    	$referer = trim($referer," /");

    	if(!empty($origins)){
    		$origins = array_filter($origins,function($el){
    			return filter_var($el,FILTER_VALIDATE_URL);
    		});

    		$origins = array_map(function($el){
    			return filter_var(trim($el," /"),FILTER_VALIDATE_URL);
    		}, $origins);


    		if(!in_array($referer, $origins)){
    			return false;
    		}
    	}*/

    	return true;
	}

	public function checkForScopes($client,$scope){
		if(!($scopes = $this->loadScopes())){
			return;
		}

		$scopes = array_map(function($el){
			return $el->getName();
		},$scopes);


		$e = explode(" ",strip_tags(trim($scope)));
    	$e = array_filter($e,function($el){
    		return strlen(trim($el));
    	});

    	$rep = $this->em->getRepository(AppAccessScope::class);
    	if(!($client_scopes = $rep->findBy(array("application"=>$client)))) {
    		$client_scopes = [];
    	}
    	$client_scopes = array_map(function($el){
    		return $el->getScope()->getName();
    	},$client_scopes);

    	foreach ($e as $val) {
    		$val = strip_tags($val);

    		if(!in_array($val, $scopes)){
    			throw new Oauth2Exception(
	    			"invalid_scope",400,
	    			"scope '$val' is unknow"
	    		);
    		}

    		if(!in_array($val, $client_scopes)){
    			throw new Oauth2Exception(
	    			"invalid_scope",400,
	    			"scope '$val' is invalid"
	    		);
    		}
    	}
	}


	public function handleVerifyRequest(){
		$request = $this->request;
		$em = $this->em;

        $access_token = $request->request->get("token");
        $authorization = $request->headers->get("authorization");

        $args = array('valid'=>false);

        if($authorization){
        	$auth = explode(" ", $authorization);
        	$type = @$auth[0];
        	$access_token = @$auth[1];

        	if(strtolower($type) != "bearer"){
        		return $args;
        	}
        }

        $rep = $em->getRepository(ApiUserAccessToken::class);
       	
       	// on verify que le token existe
        if(!($userToken = $rep->findOneByToken($access_token))){
    		throw new Oauth2Exception(
    			"access_denied",404,
    			"the token given is invalid $access_token" 
    		);
    	}
    	
    	$app = $userToken->getApplication();

    	// on controle le hash
        if(!hash_equals($userToken->getToken(),$access_token)) {
        	return $args;
        }

        // on verifie que le token est valide
        $p_expires = $userToken->getExpires()->getTimestamp();
        $now = time();

        if($now >  $p_expires){
        	throw new Oauth2Exception(
    			"invalid_token_error",401,
    			"expired token" 
    		);
        }

        if($userToken->getIsTokenRevoke()){
        	throw new Oauth2Exception(
    			"access_denied",401,
    			"the token has been revoked" 
    		);
        }

        $rep = $em->getRepository(ApiUserAccessTokenScope::class);
        $granted_scopes = [];
        if(($appScopes = $rep->findByToken($userToken))){
        	$granted_scopes = array_map(function($el){
        		return $el->getScope()->getScope()->getName();
        	}, $appScopes);
        }

       	$data =array(
       		"active"=>true,
       		"client_id"=>$app->getToken(),
       		"scope"=>implode(" ", $granted_scopes),
       		"expires_in"=>$userToken->getExpires()->getTimestamp()-time(),
       		"username"=>$userToken->getUser()->getUsername(),
       		"user_id"=>$userToken->getUser()->getId(),
       	);

       	if(in_array("email", $granted_scopes)){
       		$data["email"] = $userToken->getUser()->getEmail();
       	}

       	return $data;
	}


	public function handleAuthorizeRequest(){
		$request = $this->request;

		// required
        $client_id = $request->query->get("client_id");
        $response_type = $request->query->get("response_type");

        // optional
        $scope = $request->query->get("scope");
        $state = $request->query->get("state");
        $redirect_uri = $request->query->get("redirect_uri");

		if(!$client_id || !$response_type){
			throw new Oauth2Exception(
    			"invalid_request",400,
    			'"client_id" and "response_type" are required for this request'
    		);
        }

        if(!($client = $this->isClientExists($client_id))){
        	throw new Oauth2Exception(
    			"access_denied",400,
    			'"client_id" is invalid'
    		);
    	}

    	// controle des scopes
    	if($scope){
    		$this->checkForScopes($client,$scope);
        }
    	
		switch ($response_type) {
            case 'code':
               	$this->setGrant(new AuthorizationCode($client,$this->container));
            break;

            case 'token':
            	// protection de domains
	        	if(!$this->checkDomainRestriction($client)){
	        		throw new Oauth2Exception(
		    			"access_denied",400,
		    			'the client has defined domain restriction '
		    		);
	        	}


            	$this->setGrant(new Implicit($client,$this->container));
            break;

            default:
            	throw new Oauth2Exception(
	    			"unsupported_response_type",400,
	    			'the server only support "code" and "token" response type'
	    		);
            break;
        }
       	
       	if(($location = $this->getGrant()->authorize())){
       		throw new Oauth2RedirectionException(
	        	302,
	        	$request->headers->all(),
	        	$location
			);
       	}
	}


	public function handleAuthorizePromptRequest(){

		$request = $this->request;
        $user_action = $request->request->get("user_action");
        $client_id = $request->query->get("client_id");
        $response_type = $request->query->get("response_type");
        $scope = $request->query->get("scope");

		if(!$client_id){
			throw new Oauth2Exception(
    			"invalid_request",400,
    			'"client_id" and "response_type" are required for this request'
    		);
        }

        if(!($client = $this->isClientExists($client_id))){
        	throw new Oauth2Exception(
    			"access_denied",400,
    			'"client_id" is invalid'
    		);
    	}

        $rep = $this->em->getRepository(ApiUserAccessToken::class);

	    if(!in_array($response_type, array('code','token'))) {
        	throw new Oauth2Exception(
    			"unsupported_response_type",400,
    			'the server only support "code" and "token" response type'
    		);
        }

        $getUser = function (){
	        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
	            return;
	        }

	        if (!is_object($user = $token->getUser())) {
	            // e.g. anonymous authentication
	            return;
	        }
	        return $user;
	    };

	    $user = $getUser();
	    
	    // on doit rechercher les scopes non encore autorisés
	    // a cette application pour la demande de permission


	    // [DEBUT on les autorisations requise de l'application]
	    if($response_type == "code"){
        	$grant = new AuthorizationCode($client,$this->container);
        }
        else if ($response_type == "token") {
        	$grant = new Implicit($client,$this->container);
        }
        $app_scopes = $grant->loadGrantedScopes($scope);
        $app_scopes = array_map(function($el){
    		return $el->getScope();
    	}, $app_scopes);
    	// [FIN des autorisations requise de l'application]

        // [DEBUT les autorisations non encore octroyées par l'utilisateur]
        $rep_ = $this->em->getRepository(ApiUserAccessTokenScope::class);
        $qb = $rep_->findAllTokenScope();
        $rep_->whereApplication($qb,$client->getId());
        $rep_->whereUser($qb,$user->getId())
        ->groupBy('app.id')
        ->addGroupBy('u.id')
        ->addGroupBy('s.id');

        $user_scopes = $qb->getQuery()->getResult();
        $user_scopes = $user_scopes?$user_scopes:array();


        $ungranted = array_filter($app_scopes,function($el)use($user_scopes){
    		$item = array_filter($user_scopes,function($el2)use($el){
    			return ($el2->getScope()->getScope()->getId() == $el->getId());
    		});

    		return (count($item) == 0);
    	});
    	$ungranted = array_values($ungranted);

        // [DEBUT les autorisations non encore octroyées par l'utilisateur]
	   
	    if(count($ungranted)) {
	    	if(!$request->isMethod('post')){
	    		return array($client,$ungranted);
	    	}
	    }
	    else{
	    	$user_action = "grant";
	    }

        switch ($user_action) {
            case 'grant':


                if($response_type == "code"){
                	$this->setGrant(new AuthorizationCode($client,$this->container));
                }
                else if ($response_type == "token") {
                	$this->setGrant(new Implicit($client,$this->container));
                }
                else{
                	throw new Oauth2Exception(
		    			"unsupported_response_type",400,
		    			'the server only support "code" and "token" response type'
		    		);
                }
                $location = $this->getGrant()->authorizePrompt();

                throw new Oauth2RedirectionException(
                	302,
                	array(),
                	$location
	    		);
            break;

            case 'deny':
            	$redirect_uri = $request->query->get('redirect_uri');

                $location = $redirect_uri;
                if(!$redirect_uri){
                    $location = $client->getRedirectUri();
                }
                
                $args = array(
                    "error"=>"access_denied",
                    "error_description"=>"The resource owner denied the request"
                );

                if ($response_type == "token") {
                	$args["redirect_uri"] = $client->getRedirectUri();
                	$location = "/oauth2/redirection#".http_build_query($args);
                }
                else{
                	$location .= "?".http_build_query($args);
                }

                throw new Oauth2RedirectionException(
                	302,
                	array(),
                	$location
	    		);
            break;
        }
	}

	public function handleTokenRequest(){
		$request = $this->request;

        $grant_type = $request->request->get("grant_type");
        $content_type = $request->headers->get("content_type");
        $client_id = $request->request->get("client_id");
        $scope = $request->request->get("scope");
        $authorization = $request->headers->get("authorization");

        if(!$request->isMethod("POST")){
        	$method = $request->getMethod();
        	throw new Oauth2Exception(
    			"access_denied",400,
    			"the request method must be 'POST', '$method' was given"
    		);
        }


        // le content-type doit avoir la valeur "application/x-www-form-urlencoded"
        if( $content_type != "application/x-www-form-urlencoded"){
        	throw new Oauth2Exception(
    			"access_denied",400,
    			'content_type header must be "application/x-www-form-urlencoded"'
    		);
        }

		if(!$grant_type){
			throw new Oauth2Exception(
    			"invalid_request",400,
    			'"grant_type" is required in parameter for this request'
    		);
        }

        if($authorization && !$client_id){
        	$a = explode(" ", $authorization);
            $type = @$a[0];
            $credential = @$a[1];
            $credential_decoded = base64_decode($credential);
            $credential_decoded = explode(":", $credential_decoded);
            $client_id = @$credential_decoded[0];
        }

	    if (!($client = $this->isClientExists($client_id))){
	    	throw new Oauth2Exception(
    			"access_denied",400,
    			'"client_id" is invalid'
    		);
    	}


    	// protection de domains
    	if(!$this->checkDomainRestriction($client)){
    		throw new Oauth2Exception(
    			"access_denied",400,
    			'the client has defined domain restriction'
    		);
    	}

    	// controle des scopes
    	if($scope){
    		$this->checkForScopes($client,$scope);
        }

		switch ($grant_type) {
            case 'authorization_code':
               	$this->setGrant(new AuthorizationCode($client,$this->container));
            break;

            case 'implicit':
               	$this->setGrant(new Implicit($client,$this->container));
            break;

            case 'client_credentials':
               	$this->setGrant(new ClientCredentials($client,$this->container));
            break;

            case 'password':
               	$this->setGrant(new Password($client,$this->container));
            break;

            case 'refresh_token':
               	$this->setGrant(new RefreshToken($client,$this->container));
            break;
            

            default:
            	throw new Oauth2Exception(
	    			"unsupported_grant_type",400,
	    			'the server only support "authorization_code", "implicit", "client_credentials", "password" grant type'
	    		);
            break;
        }
       	return $this->getGrant()->token();
	}

	public function handleClientRegistrationRequest(){

        $em = $this->em;
        $request = $this->request;

    	$name = strip_tags(trim($request->request->get("name")));
        $description = strip_tags(trim($request->request->get("description")));
        $redirect_uri = strip_tags(trim($request->request->get("redirect_uri")));
        $website = strip_tags(trim($request->request->get("website")));
        $scope = strip_tags(trim($request->request->get("scope")));
        $logo = strip_tags(trim($request->request->get("image")));
        $redirect_uri = filter_var($redirect_uri,FILTER_VALIDATE_URL);
        $website = filter_var($website,FILTER_VALIDATE_URL);
        $scopes = explode(" ", $scope);

        

        if(!$name){
        	throw new Oauth2Exception(
    			"invalid_request",400,
    			"app name is required"
    		);        	
        }

        if(!$description){
        	throw new Oauth2Exception(
    			"invalid_request",400,
    			"app description is required"
    		); 
        }

        if(!$redirect_uri){
        	throw new Oauth2Exception(
    			"invalid_request",400,
    			"app redirection url is required"
    		); 
        }

        if(!$website){
        	throw new Oauth2Exception(
    			"invalid_request",400,
    			"app website is required"
    		); 
        }

        if($logo){
            $logo = filter_var($logo,FILTER_VALIDATE_URL);
            $has_error = false;

            if(!$logo){
            	$has_error = true;
            }
            else{
                if(!($content = @file_get_contents($logo))){
                	$has_error = true;
                	goto has_error_label;
                }
               
                $img_info = @getimagesizefromstring($content);
                if(!$img_info){
                	$has_error = true;
                }
                else{
                    $mime = explode("/", $img_info["mime"]);
                    if($mime[0] != "image"){
                    	$has_error = true;
                    };
                }
            }

            has_error_label:

            if($has_error){
            	throw new Oauth2Exception(
	    			"invalid_request",400,
	    			"veuillez entrer un lien d'image valide"
	    		); 
            }
        }

        $rep = $this->em->getRepository(User::class);

        if($rep->findOneByUsername($name)){
        	throw new Oauth2Exception(
    			"invalid_request",403,
    			"app name is already used"
    		);    
        }

		// application
		$user = new User();
        $user->setUsername($name);
        $user->setRoles(array("ROLE_APP"));
        $this->em->persist($user);

        $app = new Application();
        $app->setUser($user);
        $app->setName($name);
        $app->setDescription($description);
        $app->setRedirectUri($redirect_uri);
        $app->setWebsite($redirect_uri);
        $app->setLogo($logo);
        $this->em->persist($app);

        // creation des scopes
        
        // on cree les scopes par defaut
        $rep_ = $this->em->getRepository(ApiAccessScope::class);
        $defaults = $rep_->findBy(array("is_default"=>1));
        foreach ($defaults as $key => $item) {
            $appScope = new AppAccessScope();
            $appScope->setApplication($app);
            $appScope->setScope($item);
            $appScope->setDate(new \Datetime());
            $this->em->persist($appScope);
        }

        // on cree les scopes specifiques
        $rep = $this->em->getRepository(AppAccessScope::class);
        foreach ($scopes as $key => $el) {
        	if(!trim($el)) continue;

            if(($item = $rep_->findOneByName(trim($el)))){
                if($rep->findOneBy(array("application"=>$app,"scope"=>$item))) continue;
                
                $appScope = new AppAccessScope();
	            $appScope->setApplication($app);
	            $appScope->setScope($item);
	            $appScope->setDate(new \Datetime());
	            $this->em->persist($appScope);
            }
            else{
				throw new Oauth2Exception(
	    			"invalid_scope",400,
	    			"scope '$el' is unknow"
	    		);
    		}
        }

        $this->em->flush();

        return $app;
	}

	public function handleClientUpdateRequest(){

        $em = $this->em;
        $request = $this->request;

    	$authorization = $request->headers->get("authorization");
    	$name = strip_tags(trim($request->request->get("name")));
        $description = strip_tags(trim($request->request->get("description")));
        $website = strip_tags(trim($request->request->get("website")));
        $logo = strip_tags(trim($request->request->get("image")));
        $redirect_uri = strip_tags(trim($request->request->get("redirect_uri")));
        $scope = $request->request->get("scope");
        $redirect_uri = filter_var($redirect_uri,FILTER_VALIDATE_URL);
        $website = filter_var($website,FILTER_VALIDATE_URL);


        $client_id = strip_tags(trim($request->request->get("client_id")));
        $client_secret = strip_tags(trim($request->request->get("client_secret")));
       
       // [DEBUT authentification du client]
        $authorization = $request->headers->get("authorization");
	    // le corps de la requete contitent "client_id" et "client_secrete"
        if(!$authorization){
            $client_secret = $request->request->get("client_secret");

            if(!$client_id || !$client_secret){
            	throw new Oauth2Exception(
	            	"invalid_request",400,
	               	'"client_id" and "client_secret" params are required'
	            );
            }
        }
        // la requete contient un header Authorization
        else{

            $a = explode(" ", $authorization);
            $type = @$a[0];
            $credential = @$a[1];
            // on verifie que le header Authorization est du type Basic
            if($type != "Basic"){
            	throw new Oauth2Exception(
	            	"invalid_client",401,
	               	'the Authorization header must be "Basic" type'
	            );
            }

            // on verifie que les donnée de l'entete sont saisir
            if(!$credential){
            	throw new Oauth2Exception(
	            	"invalid_client",401,
	               	'the Authorization header body is required"'
	            );
            }

            $credential_decoded = base64_decode($credential);
            $credential_decoded = explode(":", $credential_decoded);

            $client_id = @$credential_decoded[0];
            $client_secret = @$credential_decoded[1];
        }
        // [FIN authentification du client]

        // on recupere l'application
    	if(!($app = $this->isClientExists($client_id))){
    		throw new Oauth2Exception(
    			"invalid_request",404,
    			"invalid client_id"
    		); 
    	}

    	// on authentifie l'application
        if(!hash_equals($app->getSecret(),$client_secret)) {
    		throw new Oauth2Exception(
            	"invalid_credentials",401,
               	"l'authentification de votre application a échouée"
            );
        }

        if($logo && $logo != $app->getLogo()){
            $logo = filter_var($logo,FILTER_VALIDATE_URL);
            $has_error = false;

            if(!$logo){
            	$has_error = true;
            }
            else{
                if(!($content = @file_get_contents($logo))){
                	$has_error = true;
                	goto has_error_label;
                }
               
                $img_info = @getimagesizefromstring($content);
                if(!$img_info){
                	$has_error = true;
                }
                else{
                    $mime = explode("/", $img_info["mime"]);
                    if($mime[0] != "image"){
                    	$has_error = true;
                    };
                }
            }

            has_error_label:

            if($has_error){
            	throw new Oauth2Exception(
	    			"invalid_request",400,
	    			"veuillez entrer un lien d'image valide"
	    		); 
            }
        }

   		$valid = false;

        if($name && $name != $app->getName()){

        	$rep = $this->em->getRepository(User::class);
	        if($rep->findOneByUsername($name)){
	        	throw new Oauth2Exception(
	    			"invalid_request",403,
	    			"app name is already used"
	    		);    
	        }

        	$app->setName($name);
        	$valid = true;
        }
        if($description && $description != $app->getDescription()){
        	$app->setDescription($description);
        	$valid = true;
        }
        if($website != $app->getWebsite()){
        	$app->setWebsite($website);
        	$valid = true;
        }
        
        if($logo && $logo != $app->getLogo()){
        	$app->setLogo($logo);
        	$valid = true;
        }
        if($redirect_uri && $redirect_uri != $app->getRedirectUri()){
        	$app->setRedirectUri($redirect_uri);
        	$valid = true;
        }

        if($valid){
            $this->em->flush();
        }

        return $app; 
	}


	public function handleRevokeRequest(){
		$request = $this->request;

		// required
        $client_id = $request->request->get("client_id");
        $token = $request->request->get("token");
        $token_type_hint = $request->request->get("token_type_hint");

		if(!$token || !$client_id){
			throw new Oauth2Exception(
    			"invalid_request",400,
    			'"client_id" and "token" are required for this request'
    		);
        }

        if($token_type_hint && !in_array($token_type_hint, array("refresh_token","access_token"))){
    		throw new Oauth2Exception(
    			"unsupported_token_type",400,
    			'the server only support "refresh_token" and "access_token" token type'
    		);
    	}

    	if(!($client = $this->isClientExists($client_id))){
        	throw new Oauth2Exception(
    			"access_denied",400,
    			'"client_id" is invalid'
    		);
    	}

    	// [DEBUT authentification du client]
        $authorization = $request->headers->get("AUTHORIZATION");
	    // le corps de la requete contitent "client_id" et "client_secrete"
        if(!$authorization){
            $client_secret = $request->request->get("client_secret");

            if(!$client_id || !$client_secret){
            	throw new Oauth2Exception(
	            	"invalid_request",400,
	               	'"client_id" and "client_secret" params are required'
	            );
            }
        }
        // la requete contient un header Authorization
        else{

            $a = explode(" ", $authorization);
            $type = @$a[0];
            $credential = @$a[1];
            // on verifie que le header Authorization est du type Basic
            if($type != "Basic"){
            	throw new Oauth2Exception(
	            	"invalid_client",401,
	               	'the Authorization header must be "Basic" type'
	            );
            }

            // on verifie que les donnée de l'entete sont saisir
            if(!$credential){
            	throw new Oauth2Exception(
	            	"invalid_client",401,
	               	'the Authorization header body is required"'
	            );
            }

            $credential_decoded = base64_decode($credential);
            $credential_decoded = explode(":", $credential_decoded);

            if(@$credential_decoded[0] !=  $client_id){
            	throw new Oauth2Exception(
	            	"invalid_client",401,
	               	'the Authorization header is invalid"'
	            );
            }

            $client_id = @$credential_decoded[0];
            $client_secret = @$credential_decoded[1];
        }
        // [FIN authentification du client]

        // on controle le mot de passe du client
        if(!hash_equals($client->getSecret(),$client_secret)) {
        	throw new Oauth2Exception(
            	"invalid_grant",400,
               	'client credentials is invalid'
            );
        }

        // on verifie que le token existe
    	$rep = $this->em->getRepository(ApiUserAccessToken::class);
    	// refresh_token
    	if($token_type_hint == "refresh_token"){ 
    		if(!($userToken = $rep->findOneBy(array("refresh_token"=>$token,"isTokenRevoke"=>0)))) {
	        	throw new Oauth2Exception(
	            	"invalid_grant",400,
	               	"this refresh_token is invalid"
	            );
	        }
    	}
    	// access_token
    	else{ 
    		if(!($userToken = $rep->findOneBy(array("token"=>$token,"isTokenRevoke"=>0)))) {
	        	throw new Oauth2Exception(
	            	"invalid_grant",400,
	               	"this access_token is invalid"
	            );
	        }
    	}

    	// on verifie le token a été delivré au client donné
        if($userToken->getApplication()->getId() != $client->getId()){
        	throw new Oauth2Exception(
            	"access_denied",400,
               	"the token provided was issued to another client."
            );
        }

        //
        $application = $userToken->getApplication();
        $p_expires = $userToken->getExpires()->getTimestamp();
        $now = time();

        // on verifie que le code est valide
        if($now >  $p_expires){
        	throw new Oauth2Exception(
            	"invalid_grant",400,
               	"the token is already expired"
            );
        }

        // on supprime desactive le token
        if($token_type_hint == "refresh_token"){
        	$userToken->setIsRefreshTokenRevoke(1);
        }
        else{
        	$userToken->setIsTokenRevoke(1);
        	$userToken->setIsRefreshTokenRevoke(1);

        	// on supprime les autorisations
	        $rep = $this->em->getRepository(ApiUserAccessTokenScope::class);
	        if(($scopes = $rep->findByToken($userToken))){
	        	foreach ($scopes as $key => $scope) {
	        		$this->em->remove($scope);
	        	}
	        }
        }

        $this->em->flush();

       	return array(
   			"status"=>true,
   		);
	}
}

