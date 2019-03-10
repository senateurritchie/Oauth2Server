<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use AppBundle\Service\Oauth2Server;
use AppBundle\Entity\User;
use AppBundle\Entity\Application;
use AppBundle\Entity\ApiUserAccessCode;
use AppBundle\Entity\ApiAccessScope;
use AppBundle\Entity\AppAccessScope;
use AppBundle\Form\UserLoginType;

/**
* @Route("/oauth2", name="oauth2_")
*/
class OAuth2Controller extends Controller
{

    /**
     * @Route("/verify", name="verify")
     */
    public function verifyAction(Oauth2Server $server){
        $ev = $server->handleVerifyRequest();
        return $this->json($ev,200,array("Cache-Control: no-store","Pragma: no-cache"));
    }

    /**
     * @Route("/authorize", name="authorize")
     */
    public function authorizeAction(Oauth2Server $server){
        $ev = $server->handleAuthorizeRequest();
        return $this->json($ev);
    }

    /**
     * @Route("/authorize/login", name="authorize_login")
     */
    public function authorizeLoginAction(Request $request){

        $client_id = $request->query->get('client_id');
        $response_type = $request->query->get('response_type');

        if(!$client_id || !$response_type){
            throw new Exception("Error Processing Request", 1);
        }

        // si on est pas connecté 
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) { 
            return $this->redirectToRoute("oauth2_authorize_prompt",$request->query->all());
        }

        if($request->isMethod("post")){
            $subRequest = Request::create($this->generateUrl("aaz_login"),"POST");
            $subRequest->request->replace($request->request->all());

            $response = $this->get('http_kernel')->handle($subRequest);

            if($this->isGranted('IS_AUTHENTICATED_FULLY')){
                return $this->redirectToRoute("oauth2_authorize_prompt",$request->query->all());
            }
        }

        $em = $this->getDoctrine()->getManager();

        $rep = $em->getRepository(Application::class);

        if(!($client = $rep->findOneByToken($client_id))){
            throw new Exception("Error Processing Request", 1);
        }


        return $this->render("oauth2/login.html.twig",array(
            "client"=>$client
        ));
    }

    /**
     * @Route("/authorize/prompt", name="authorize_prompt")
     */
    public function authorizePromptAction(Oauth2Server $server,Request $request){

        $client_id = $request->query->get('client_id');
        $response_type = $request->query->get('response_type');
        $scope = $request->query->get('scope');

        if(!$client_id || !$response_type){
            throw new \Exception("Error Processing Request", 1);
        }

        // si on est pas connecté 
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new \Exception("Error Processing Request", 1);
        }

        list($client,$scopes) = $server->handleAuthorizePromptRequest();
        $ev = array("client"=>$client,"scopes"=>$scopes);

        return $this->render("oauth2/prompt.html.twig",$ev);
    }

    /**
     * @Route("/token", name="token", methods={"post"})
     */
    public function tokenAction(Oauth2Server $server){
        $ev = $server->handleTokenRequest();
        return $this->json($ev,200,array("Cache-Control: no-store","Pragma: no-cache"));
    }

    /**
     * @Route("/revoke", name="revoke",  methods={"post"})
     */
    public function revokeAction(Oauth2Server $server){
        $ev = $server->handleRevokeRequest();
        return $this->json($ev);
    }

    /**
     * @Route("/app/{id}", name="app_info",  methods={"get"}, requirements={"id":"\w+"})
     */
    public function appAction(Request $request,$id){
        $ev = array();

        $em = $this->getDoctrine()->getManager();
        $rep = $em->getRepository(Application::class);
        if(!($app = $rep->findOneByToken($id))){
            return $this->createNotFoundException();
        }

        $rep = $em->getRepository(AppAccessScope::class);
        if(($scopes = $rep->findByApplication($app))){
            $scopes = array_map(function($el){
                return $el->getScope()->getName();
            }, $scopes);
        }

        $scopes = implode(" ", $scopes);

        $ev = array(
            "name"=>$app->getName(),
            "redirect_uri"=>$app->getRedirectUri(),
            "website"=>$app->getWebsite(),
            "image"=>$app->getLogo(),
            "scope"=>$scopes,
        );

        return $this->json($ev);
    }

    /**
     * @Route("/app/{id}", name="app_update",  methods={"put"}, requirements={"id":"\w+"})
     */
    public function updateClientAction(Oauth2Server $server,Request $request,$id){
        $request->request->set("client_id",$id);
        $app = $server->handleClientUpdateRequest();
        $rsrcURL = $this->generateUrl("oauth2_app_info",array("id"=>$app->getToken()),0);
        $ev = array(
            "resourceURL"=>$rsrcURL
        );
        return $this->json($ev);
    }

    /**
     * @Route("/register", name="register",  methods={"post"})
     */
    public function registrationClientAction(Oauth2Server $server){
        $app = $server->handleClientRegistrationRequest();
        $rsrcURL = $this->generateUrl("oauth2_app_info",array("id"=>$app->getToken()),0);

        $code = base64_encode($app->getToken().":".$app->getSecret());
        $ev = array(
            "client_id"=>$app->getToken(),
            "client_secret"=>$app->getSecret(),
            "authorization"=>"Basic $code",
            "resourceURL"=>$rsrcURL
        );
        return $this->json($ev,201);
    }

}
