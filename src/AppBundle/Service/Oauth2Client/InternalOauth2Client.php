<?php 
namespace AppBundle\Service\Oauth2Client;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use AppBundle\Service\Oauth2Client\Response\InternalOauth2ClientResponse;
class InternalOauth2Client extends AbstractOauthClient{

	/**
	* @var HttpKernelInterface
	*/
	protected $kernel;

	public function __construct(HttpKernelInterface $kernel){
		$this->kernel = $kernel;
	}

	/**
    * {@inheritdoc}
    */
	public function verifyToken($token){
		$subRequest = Request::create("/oauth2/verify");
        $subRequest->headers->set('Authorization','Bearer '.$token);
        return new InternalOauth2ClientResponse($this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST));
	}
}

