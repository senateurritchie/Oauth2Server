<?php
namespace AppBundle\Service\Oauth2Client\Response;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Service\Exception\Oauth2Exception;

class InternalOauth2ClientResponse implements Oauth2ClientResponseInterface{

	/**
	* @var Response
	*/
	protected $response;

	public function __construct(Response $response){
		$this->response = $response;
	}

	/**
	* {@inheritdoc}
	*/
	public function getResult(){
		if($this->response){
			$r = $this->response;
            if($r->headers->get('content-type') == "application/json"){
            	$data = json_decode($r->getContent(),true);
            	if($r->getStatusCode() != 200){
            		throw new Oauth2Exception(
		    			@$data['error'],
		    			$r->getStatusCode(),
		    			@$data['error_description']
		    		);
            	}
                return $data;
            }
            else{
            	return $r->getContent();
            }
		}
	}
}