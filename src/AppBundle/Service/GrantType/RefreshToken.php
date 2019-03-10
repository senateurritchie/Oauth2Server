<?php 
	namespace AppBundle\Service\GrantType;
	use AppBundle\Entity\Application;
	use AppBundle\Entity\ApiUserAccessToken;
	use AppBundle\Entity\ApiUserAccessTokenScope;
    use AppBundle\Service\Exception\Oauth2Exception;

	class RefreshToken extends GrantType{

		public function authorize(){
			throw new Oauth2Exception(
                "unauthorized_client",400,
                "The client is not authorized to request an authorization code using this method"
            );
		}

		public function authorizePrompt(){
			throw new Oauth2Exception(
                "unauthorized_client",400,
                "The client is not authorized to request an authorization code using this method"
            );
		}

		public function token(){
			$request = $this->request;
			// required
	        $client_id = $request->request->get("client_id");
	        $refresh_token = $request->request->get("refresh_token");

	        $client = $this->getClient();

			if(!$client_id || !$refresh_token){
				throw new Oauth2Exception(
	    			"invalid_request",400,
	    			'"client_id" and "refresh_token" are required for this request'
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
		            	"access_denied",401,
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
	            	"access_denied",400,
	               	'client credentials is invalid'
	            );
	        }

	        // on verifie que le token existe
	    	$rep = $this->em->getRepository(ApiUserAccessToken::class);
	    	// refresh_token
			if(!($userToken = $rep->findOneBy(array("refresh_token"=>$refresh_token,"isTokenRevoke"=>0,"isRefreshTokenRevoke"=>0)))) {
	        	throw new Oauth2Exception(
	            	"access_denied",400,
	               	"this refresh_token is invalid"
	            );
	        }

	        $user = $userToken->getUser();

	    	// on verifie le token a été delivré au client donné
	        if($userToken->getApplication()->getId() != $client->getId()){
	        	throw new Oauth2Exception(
	            	"access_denied",400,
	               	"the token provided was issued to another client."
	            );
	        }

	        $entity = new ApiUserAccessToken();
            $entity->setApplication($client);
            $entity->setUser($user);
            $entity->setGrantType("refresh_token");
            $this->em->persist($entity);

            $access_token = $entity->getToken();
            $refresh_token = $entity->getRefreshToken();

            // copie des authorizations
            $granted_scopes = [];
	        $rep = $this->em->getRepository(ApiUserAccessTokenScope::class);
	        if(($scopes = $rep->findByToken($userToken))){
	        	foreach ($scopes as $key => $tokenScope) {
	        		$token_scope = new ApiUserAccessTokenScope();
                	$token_scope->setToken($entity);
	                $token_scope->setScope($tokenScope->getScope());
	                $token_scope->setDate(new \Datetime());
	                $this->em->persist($token_scope);
	                $granted_scopes[] = $tokenScope->getScope()->getScope()->getName();
	        	}
	        }

	        // on revoke le refresh_token
	        $userToken->setIsRefreshTokenRevoke(1);
            $this->em->persist($userToken);

            $this->em->flush();

	       	$data = array(
	   			"access_token"=>$access_token,
	   			"refresh_token"=>$refresh_token,
	   			"token_type"=>"bearer",
	   			"expires_in"=>$entity->getExpires()->getTimestamp(),
	   			"username"=>$user->getUsername(),
	   			"scope"=>implode(" ", $granted_scopes)
	   		);

	   		if(in_array("email", $granted_scopes)){
	       		$data["email"] = $userToken->getUser()->getEmail();
	       	}

	   		return $data;
		}
	}
?>