<?php
namespace AppBundle\Service\Oauth2Client\Response;

abstract class AbstractOauth2ClientResponse{

	/**
	* @var mixed
	*/
	protected $response;

	public function __construct($response){
		$this->response = $response;
	}

	/**
	* Traite et retourne le resultat d'une requete effectuée
	* depuis un client Oauth2
	*
	* @return mixed
	*/
	abstract public function getResult();
}