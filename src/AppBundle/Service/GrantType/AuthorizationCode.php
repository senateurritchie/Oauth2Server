<?php 
	namespace AppBundle\Service\GrantType;
    use AppBundle\Entity\Application;
    use AppBundle\Entity\ApiUserAccessToken;
    use AppBundle\Entity\ApiUserAccessCode;
    use AppBundle\Entity\ApiUserAccessTokenScope;

    use AppBundle\Entity\User;
    use AppBundle\Service\Exception\Oauth2Exception;

	class AuthorizationCode extends GrantType{

		public function authorize(){
			$request = $this->request;

	        $client_id = $request->query->get("client_id");
	        $response_type = $request->query->get("response_type");
	        $state = $request->query->get("state");
	        $redirect_uri = $request->query->get("redirect_uri");
	        $scope = strip_tags(trim($request->query->get("scope")));
	        $args = array();

	        $client = $this->getClient();



        	/*$grant_types = $client->getGrantTypes();
	        if(!in_array("authorization_code", $grant_types)) {
	        	throw new Oauth2Exception(
	                "unauthorized_client",400,
	                "The client is not authorized to request an authorization code using this method"
	            );
			}*/

			if(!$client->getRedirectUri() && !$redirect_uri){
        		$exception = new Oauth2Exception(
	    			"invalid_request",400,
	    			'"redirect_uri" request parameter is required'
	    		);

	    		if($state){
	    			$exception->setState($state);
	    		}
	        	throw $exception;
	    	}

	        if($redirect_uri && $redirect_uri != $client->getRedirectUri()){
	        	$exception =  new Oauth2Exception(
	                "access_denied",400,
	                "this redirect_uri is not registred for this client"
	            );

	            if($state){
	    			$exception->setState($state);
	    		}
	        	throw $exception;
	        }

	       	$args["client_id"] = $client_id;
           	$args["response_type"] = $response_type;

           	if($state){
	        	$args["state"] = $state;
	        }


	        if($scope){
	        	$args["scope"] = $scope;
	        }

	        if($redirect_uri){
	        	$args["redirect_uri"] = $redirect_uri;
	        }
	        else{
	        	$redirect_uri = $client->getRedirectUri();
	        }

	        // chargement des scopes
            $granted_scopes = $this->loadGrantedScopes($scope);
            if(count($granted_scopes)){
            	$granted_scopes = array_map(function($el){
            		return $el->getScope()->getName();
            	}, $granted_scopes);

            	$args['scope'] = implode(" ", $granted_scopes);
            }

	        $redirect_uri = "http://localhost:8000/oauth2/authorize/login";

			$params = http_build_query($args);
	        return $redirect_uri."?".$params;
		}

		public function authorizePrompt(){
			$request = $this->request;
	        $state = $request->query->get('state');
	        $redirect_uri = $request->query->get('redirect_uri');
	       	$scope = $request->query->get('scope');
	        $args = array();

	        $user = $this->getUser();
	       	$client = $this->getClient();

	       	$location = $redirect_uri;

            if(!$redirect_uri){
                $location = $client->getRedirectUri();
            }

            if($client->getRedirectUri() != $redirect_uri){
                $location = $client->getRedirectUri();
            }

            

        	$timestamp = time()+(60*3); // 3 minutes
            $expires = new \Datetime();
            $expires->setTimestamp($timestamp);

            $entity = new ApiUserAccessCode();
            $entity->setApplication($client);
            $entity->setUser($user);
            $entity->setRedirectUri($location);
            $entity->setScope($scope);
            
            $this->em->persist($entity);
            $this->em->flush();

            $code = $entity->getCode();
            $args['code'] = $code;

            if($state){
                $args['state'] = $state;
            }

            $args['user_id'] = $user->getId();

            $params = http_build_query($args);
            $c = explode("?", $location);
            if(count($c) == 1){
            	return $location."?".$params;
            }
            else{
            	return $location."&".$params;
            }
		}

		public function token(){
			$request = $this->request;

			//required
            $grant_type = $request->request->get("grant_type");
	        $code = $request->request->get("code");
	        // if included on authorization_code
        	$redirect_uri = $request->request->get("redirect_uri"); 
	        $client_id = $request->request->get("client_id");

	        // other
	        $content_type = $request->headers->get("content_type");
	        $user_id = $request->request->get("user_id");

            $client = $this->getClient();

	        if(!$code || !$grant_type){
        		throw new Oauth2Exception(
	    			"invalid_request",400,
	    			'"code" and "grant_type" are required in request parameter'
	    		);
	        }

	        if($grant_type != "authorization_code"){
        		throw new Oauth2Exception(
	    			"unsupported_response_type",400,
	    			'only "authorization_code" grant type is required'
	    		);
	        }

        	if(!$client_id){
        		throw new Oauth2Exception(
	                "invalid_request",400,
	               'for this request "client_id" parameter is required'
	            );
            }

			/*$grant_types = $client->getGrantTypes();
	        if(!in_array($grant_type, $grant_types)) {
	        	throw new Oauth2Exception(
	                "unauthorized_client",400,
	               "The client is not authorized to request an authorization code using this method"
	            );
			}*/


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
	            	"invalid_grant",401,
	               	'client credentials is invalid'
	            );
            }

        	// on verifie que le code existe
	        $rep = $this->em->getRepository(ApiUserAccessCode::class);

	        if(!($oauthcode = $rep->findOneByCode($code))){
	        	throw new Oauth2Exception(
	            	"invalid_grant",400,
	               	"this authorization_code is invalid"
	            );
	        }

	        if($oauthcode->getToken()){
	        	throw new Oauth2Exception(
	            	"access_denied",400,
	               	"the code provided is already used"
	            );
	        }

	        $user = $oauthcode->getUser();
	        $scope = $oauthcode->getScope();
	        $application = $oauthcode->getApplication();

	        $p_user_id = $user->getId();
	        $p_client_id = $application->getToken();
	        $p_redirect_uri = $oauthcode->getRedirectUri();
	        $p_expires = $oauthcode->getExpires()->getTimestamp();
	        $now = time();

	        // on verifie que le code est valide
	        if($now >  $p_expires){
	        	throw new Oauth2Exception(
	            	"invalid_grant",400,
	               	"the authorization_code is expired"
	            );
	        }

	        // on verifie que le client_id de la requete precedente est le meme
	        // que le requete encours.
	        if($p_client_id != $client_id){
	        	throw new Oauth2Exception(
	            	"invalid_grant",400,
	               	"the authoriation_code provided was issued to another client. $p_client_id"
	            );
	        }

	        // on verifie que le user_id de la requete precedente est le meme
	        // que le requete encours.
	        if($p_user_id != $user_id){
	        	throw new Oauth2Exception(
	            	"invalid_grant",400,
	               	'the authoriation_code provided was issued to another user.'
	            );
	        }

	        // on verifie si le redirect_uri est un parametre obligatoire
	        if($p_redirect_uri && !$redirect_uri){
	        	if($p_redirect_uri != $client->getRedirectUri()){
	        		throw new Oauth2Exception(
		            	"invalid_request",400,
		               	'for this request "redirect_uri" parameter is required'
		            );
		        }
		        $redirect_uri = $client->getRedirectUri();
	        }

	       	// on verifie que le redirect_uri de la requete precedente est le meme
	        // que le requete encours.
	        if($p_redirect_uri && $p_redirect_uri != $redirect_uri){
	        	throw new Oauth2Exception(
	            	"invalid_grant",400,
	               	'access_token request does not match the redirection URI used in the authorization request'
	            );
	        }

            $entity = new ApiUserAccessToken();
            $entity->setApplication($client);
            $entity->setUser($user);
            $entity->setGrantType("authorization_code");
            $this->em->persist($entity);

            $access_token = $entity->getToken();
            $refresh_token = $entity->getRefreshToken();

            $oauthcode->setToken($access_token);
            $this->em->persist($oauthcode);

            // pris en charge des scopes
            $granted_scopes = $this->loadGrantedScopes($scope);
            if(count($granted_scopes)){
                $granted_scopes = $this->saveGrantedScopes($entity,$granted_scopes);
            }

            $this->em->flush();

	       	return array(
	   			"scope"=>$scope,
	   			"access_token"=>$access_token,
	   			"refresh_token"=>$refresh_token,
	   			"token_type"=>"bearer",
	   			"expires_in"=>$entity->getExpires()->getTimestamp(),
	   			"username"=>$user->getUsername(),
	   			"email"=>$user->getEmail(),
	   			"scope"=>$granted_scopes,
	   		);
		}
		
	}
?>