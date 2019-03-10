<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\AppAccessScope;
use AppBundle\Service\Exception\ApiException;

/**
* @Route("/api/{version}/sms", name="api_sms_", defaults={"version":"v1"}, requirements={"version":"v[1](\.[0])?"})
*/
class SmsApiController extends Controller{

	public function getUserScopes(){
		$token = $this->container->get('security.token_storage')->getToken();
    	$authorization = $token->getAttributes("access_token")["access_token"];
    	return explode(" ", $authorization["scope"]);
	}

	public function chechPermission(array $requiredScopes){
    	$granted_scopes = $this->getUserScopes();

    	$user_scopes = array_filter($requiredScopes,function($el)use(&$granted_scopes){
    		return in_array($el, $granted_scopes);
    	});
    	$user_scopes = array_values($user_scopes);

    	if(count($user_scopes) != count($requiredScopes)){
    		// le client ne dispose pas les autorisations
    		// suffisantes pour effectuer cette requete.
    		// code: 20
    		throw new ApiException($code = 20,
    			$error = "access_denied",
    			$description = "you does not have permission to access this endpoint",
    			$status_code = 403
    		);
    	}

    	return $granted_scopes;
	}
    
    /**
     * @Route("/{id}", name="read", methods={"get"}, requirements={"id":"\w+"})
     */
    public function smsreadAction(Request $request,$version,$id=null){
    	$requiredScopes = ["readsms"];
    	$this->chechPermission($requiredScopes);
    	$ev = array("version"=>$version);
    	if($id){
    		$ev["sms"] = $id;
    	}
        return $this->json($ev);
    }

    /**
     * @Route("", name="write", methods={"post"})
     */
    public function smswriteAction(Request $request,$version){
    	$requiredScopes = ["writesms"];
    	$rsrcURL = $this->generateUrl("api_sms_read",array("id"=>uniqid()),0);
    	$ev = array("version"=>$version,"resourceURL"=>$rsrcURL);
    	$this->chechPermission($requiredScopes);
    	$headers = array("Location"=>$rsrcURL);
        return $this->json($ev,201,$headers);
    }

}
