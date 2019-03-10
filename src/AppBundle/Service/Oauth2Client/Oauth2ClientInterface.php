<?php 
namespace AppBundle\Service\Oauth2Client;

interface Oauth2ClientInterface{
	/**
    * Effectue une requete sur un serveur distant
    * pour determiner la validité d'un token.
    *
    * @param string $token
    *
    * @return Oauth2ClientResponseInterface
    */
	public function verifyToken($token);
}

