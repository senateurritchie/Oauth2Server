<?php 
	namespace AppBundle\Service\GrantType;
	use AppBundle\Entity\Application;
	use AppBundle\Entity\ApiUserAccessToken;
	use AppBundle\Entity\User;

	class Password extends GrantType{


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
			$em = $this->em;
			$request = $this->request;
			$encoder = $this->container->get('security.password_encoder');

			// required
	        $username = $request->request->get("username");
            $password = $request->request->get("password");
	        $client_id = $request->request->get("client_id");

	        //optional
	        $scope = $request->request->get("scope");	       
            
            if(!$client_id || !$username || !$password){
            	throw new Oauth2Exception(
	                "invalid_request",400,
	                '"client_id", "username", "password" in request parameter are required'
	            );
            }

            $client = $this->getClient();
			/*$grant_types = json_decode($client->getGrantTypes(),true);
	        if(!in_array($grant_type, $grant_types)) {
        		$args["error"] = "unauthorized_client";
	        	$args["error_description"] = "The client is not authorized to request an authorization code using this method";
	        	$args["status_code"] = 400; 
	            call_user_func($onError,$args);
	        	return null;
			}*/
	     
            // on cherche l'utilisateur dans la base de donnée
	        $rep = $em->getRepository(User::class);
	        if(!($user = $rep->findByEmailOrUsername($username))) {
	        	throw new Oauth2Exception(
	                "access_denied",400,
	                'user can not be authenticate'
	            );
	        }

	        if(!$encoder->isPasswordValid($user,$password)) {
            	throw new Oauth2Exception(
	                "access_denied",400,
	                'resource owner credentials are invalid'
	            );
        	}

            $entity = new ApiUserAccessToken();
            $entity->setApplication($client);
            $entity->setUser($user);
            $entity->setGrantType("password");
            $this->em->persist($entity);

            // pris en charge des scopes
            $granted_scopes = $this->loadGrantedScopes($scope);
            if(count($granted_scopes)){
                $granted_scopes = $this->saveGrantedScopes($entity,$granted_scopes);
            }

            $this->em->flush();

            $access_token = $entity->getToken();
            $refresh_token = $entity->getRefreshToken();

	       	$data = array(
	   			"access_token"=>$access_token,
	   			"refresh_token"=>$refresh_token,
	   			"token_type"=>"bearer",
	   			"expires_in"=>$entity->getExpires()->getTimestamp(),
	   			"username"=>$entity->getUser()->getUsername(),
                "scope"=>implode(' ', $granted_scopes)
	   		);	

	   		if(in_array("email", $granted_scopes)){
	       		$data["email"] = $entity->getUser()->getEmail();
	       	}

	   		return $data;    		   	
		}

		
	}
?>