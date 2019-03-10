<?php
namespace AppBundle\Service\Oauth2Client\Response;

interface Oauth2ClientResponseInterface{
	/**
	* Traite et retourne le resultat d'une requete effectuée
	* depuis un client Oauth2
	*
	* @return mixed
	*/
	public function getResult();
}