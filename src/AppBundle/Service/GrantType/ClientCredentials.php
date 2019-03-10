<?php 
	namespace AppBundle\Service\GrantType;
    use AppBundle\Entity\Application;
    use AppBundle\Entity\ApiUserAccessToken;
    use AppBundle\Entity\ApiAccessScope;
    use AppBundle\Entity\ApiUserAccessTokenScope;
    use AppBundle\Service\Exception\Oauth2Exception;

	class ClientCredentials extends GrantType{

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
	        $grant_type = $request->request->get("grant_type");
            $client_id = $request->request->get("client_id");
            $client_secret = $request->request->get("client_secret");
            $authorization = $request->headers->get("AUTHORIZATION");

            // other
            $scope = $request->request->get("scope");
			$client = $this->getClient();

			/*$grant_types = $client->getGrantTypes();
	        if(!in_array($grant_type, $grant_types)) {
                throw new Oauth2Exception(
                    "unauthorized_client",400,
                    "The client is not authorized to request an authorization code using this method"
                );
			}*/

		    // le corps de la requete contitent 
            // "client_id" et "client_secret"
            if(!$authorization){
                $client_id = $request->request->get("client_id");
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
                        "invalid_request",401,
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
		    
		    // on controle le mot de passe
            if(!hash_equals($client->getSecret(),$client_secret)) {
                throw new Oauth2Exception(
                    "invalid_grant",400,
                    'client credentials is invalid'
                );
            }

            $entity = new ApiUserAccessToken();
            $entity->setApplication($client);
            $entity->setUser($client->getUser());
            $entity->setGrantType("client_credentials");

            $this->em->persist($entity);

            // pris en charge des scopes
            $granted_scopes = $this->loadGrantedScopes($scope);
            if(count($granted_scopes)){
                $granted_scopes = $this->saveGrantedScopes($entity,$granted_scopes);
            }

            $this->em->flush();

	       	return array(
                "access_token"=>$entity->getToken(),
                "refresh_token"=>$entity->getRefreshToken(),
	   			"token_type"=>"bearer",
	   			"expires_in"=>$entity->getExpires()->getTimestamp(),
                "scope"=>implode(' ', $granted_scopes)
	   		);	        
		}

        public function verify(){

            $request = $this->request;

            $authorization = $request->headers->get("AUTHORIZATION");

            $client = $this->getClient();           

            // le corps de la requete contitent 
            // "client_id" et "client_secrete"
            if(!$authorization){
                $client_id = $request->request->get("client_id");
                $client_secret = $request->request->get("client_secret");

                if(!$client_id || !$client_secret){
                    throw new Oauth2Exception(
                        "invalid_client",400,
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
            
            // on controle le mot de passe
            if(!hash_equals($client->getClient_secret(),$client_secret)) {
                throw new Oauth2Exception(
                    "invalid_grant",400,
                    'client credentials is invalid'
                );
            }
            return true;          
        }

		
	}
?>