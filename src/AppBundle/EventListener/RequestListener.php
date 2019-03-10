<?php 
namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RequestListener{
	private $generator;

	public function __construct(UrlGeneratorInterface $generator){
		$this->generator = $generator;
	}

	public function onKernelRequest(GetResponseEvent $event){
		$request = $event->getRequest();


	    if($request->attributes->get('_route') == "aaz_login"){

	    	if($request->query->has("client_id") && $request->query->has('response_type')){

	    		$url = $this->generator->generate("oauth2_authorize_login",$request->query->all(), UrlGeneratorInterface::ABSOLUTE_URL);
	    		$response = new RedirectResponse($url);

	    		$event->setResponse($response);
		    	/*$request->attributes->set('_route',"authorize_login");
		    	$request->attributes->set('_controller',"AppBundle\Controller\OAuth2Controller::authorizeLoginAction");

		    	\\$event->stopPropagation();*/
	    	}
	    }
	}

}
