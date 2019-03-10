<?php 
namespace AppBundle\Service\Exception;

class ApiException extends Oauth2Exception{

	/**
	* @var int
	*/
	private $errorCode;

	public function __construct($error_code,$error_type,$error_description,$status_code){
		parent::__construct($error_type,$status_code,$error_description);
		$this->errorCode = $error_code;
	}

	public function getErrorCode(){
		return $this->errorCode;
	}

}