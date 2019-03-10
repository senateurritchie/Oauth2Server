<?php 
namespace AppBundle\Service\Exception;

class Oauth2Exception extends \Exception{

	/**
	* @var string
	*/
	private $errorType;
	/**
	* @var int
	*/
	private $statusCode;
	/**
	* @var string
	*/
	private $errorUri;
	/**
	* @var string
	*/
	private $state;

	public function __construct($error_type,$status_code,$message){
		parent::__construct($message);
		$this->errorType = $error_type;
		$this->statusCode = $status_code;
	}

	public function getStatusCode(){
		return $this->statusCode;
	}
	public function getErrorType(){
		return $this->errorType;
	}
	public function getErrorUri(){
		return $this->errorUri;
	}
	public function getState(){
		return $this->state;
	}

	public function setErrorUri($errorUri){
		$this->errorUri = $errorUri;
		return $this;
	}
	public function setState($state){
		$this->state = $state;
		return $this;
	}
}