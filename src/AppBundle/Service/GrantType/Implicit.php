<?php 
	namespace AppBundle\Service\GrantType;
	use AppBundle\Entity\User;
	use AppBundle\Entity\Application;
	use AppBundle\Entity\ApiUserAccessToken;
    use AppBundle\Service\Exception\Oauth2Exception;

	class Implicit extends GrantType{

		public function authorize(){
	        $em = $this->em;
	        $request = $this->request;
	        // required
	       	$client_id = $request->query->get("client_id");
	        $response_type = $request->query->get("response_type");

	        // optional
	        $redirect_uri = $request->query->get("redirect_uri");
	        $scope = $request->query->get("scope");

	        // recommended
	        $state = $request->query->get("state");

	        // other
	        $args = array();

	        if(!$request->isMethod("GET")){
	        	$method = $request->getMethod();
	        	throw new Oauth2Exception(
	    			"access_denied",400,
	    			"the request method must be 'GET', '$method' was given"
	    		);
	        }

	       	$client = $this->getClient();
	       	
	       /*	$grant_types = $client->getGrantTypes();
	        if(!in_array("implicit", $grant_types)) {
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
	        	throw new Oauth2Exception(
	                "access_denied",400,
	                "this redirect_uri i not registred to the client"
	            );
	        }


	       	$args["client_id"] = $client_id;
           	$args["response_type"] = $response_type;

           	if($state){
	        	$args["state"] = $state;
	        }

	        if($redirect_uri){
	        	$args["redirect_uri"] = $redirect_uri;
	        }

	        // chargement des scopes
            $granted_scopes = $this->loadGrantedScopes($scope);
            if(count($granted_scopes)){
            	$granted_scopes = array_map(function($el){
            		return $el->getScope()->getName();
            	}, $granted_scopes);
            	$args['scope'] = implode(" ", $granted_scopes);
            }

	        $location = "/oauth2/authorize/login";
			$params = http_build_query($args);
	        return $location."?".$params;
		}

		public function authorizePrompt(){

			$em = $this->em;
			$request = $this->request;

			$state = $request->query->get('state');
	        $redirect_uri = $request->query->get('redirect_uri');
	       	$scope = $request->query->get('scope');
	        

	        $client = $this->getClient();
	        $user = $this->getUser();
	        $args = array();

            // [DEBUT creation du access_token]
	        $entity = new ApiUserAccessToken();
            $entity->setApplication($client);
            $entity->setUser($user);
            $entity->setGrantType("implicit");
            $this->em->persist($entity);

            // pris en charge des scopes
            $granted_scopes = $this->loadGrantedScopes($scope);
            if(count($granted_scopes)){
                $granted_scopes = $this->saveGrantedScopes($entity,$granted_scopes);
            }

            $this->em->flush();
 			// [FIN creation du access_token]

            $access_token = $entity->getToken();
            $args["access_token"] = $access_token;
            $args["expires_in"] = $entity->getExpires()->getTimestamp();
            $args["token_type"] = "bearer";

            if($state){
                $args['state'] = $state;
            }

            if(count($granted_scopes)){
                $args['scope'] = implode(" ", $granted_scopes);
            }

            $location = $redirect_uri;

            if(!$redirect_uri){
                $location = $client->getRedirectUri();
            }

            if($client->getRedirectUri() != $redirect_uri){
                $location = $client->getRedirectUri();
            }

            $args['redirect_uri'] =  $location;
            $args['username'] = $user->getUsername();
            $args['email'] = $user->getEmail();

            $params = http_build_query($args);

            return $location."#".$params;
		}

		public function token(){
			throw new Oauth2Exception(
                "unauthorized_client",400,
                "The authenticated client is not authorized to use this authorization grant type"
            );
		}

		public function revoke(){
			throw new Oauth2Exception(
                "unauthorized_client",400,
                "The authenticated client is not authorized to use this authorization grant type"
            );
		}
	}
?>