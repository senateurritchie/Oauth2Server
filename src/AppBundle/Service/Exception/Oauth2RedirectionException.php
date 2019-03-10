<?php 
namespace AppBundle\Service\Exception;

class Oauth2RedirectionException extends \Exception{

	/**
	* @var int
	*/
	private $statusCode;
	/**
	* @var array
	*/
	private $headers;

	public function __construct($status_code,$headers,$message){
		parent::__construct($message);
		$this->statusCode = $status_code;
		$this->headers = $headers;
	}

	public function getStatusCode(){
		return $this->statusCode;
	}
	public function getHeaders(){
		return $this->headers;
	}

}