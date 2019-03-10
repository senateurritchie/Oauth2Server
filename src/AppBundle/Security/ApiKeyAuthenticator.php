<?php
namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;


use AppBundle\Security\ApiKeyUserProvider;
use AppBundle\Service\Oauth2Client\Oauth2ClientInterface;
use AppBundle\Service\Exception\Oauth2Exception;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface, AuthenticationSuccessHandlerInterface{

    /**
    * @var Oauth2ClientInterface
    */
    private $oauth2;

    public function __construct(Oauth2ClientInterface $oauth2){
        $this->oauth2 = $oauth2;
    }

    public function createToken(Request $request, $providerKey){
        
        $apiKey = $request->headers->get('Authorization');

        if (!$apiKey) {            
            throw new BadCredentialsException();
            // or to just skip api key authentication
            // return null;
        }

        $apiKey = explode(" ", $apiKey);

        if(count($apiKey) != 2){
            if (!$apiKey) {            
                throw new BadCredentialsException();
            }
        }
        else{
            $type = $apiKey[0];
            $apiKey = $apiKey[1];

            if (strtolower($type) != "bearer") {            
                throw new BadCredentialsException();
            }
        }

        return new PreAuthenticatedToken(
            'anon.',
            $apiKey,
            $providerKey
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey){
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey){
        if (!$userProvider instanceof ApiKeyUserProvider) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The user provider must be an instance of ApiKeyUserProvider (%s was given).',
                    get_class($userProvider)
                )
            );
        }

        $apiKey = $token->getCredentials();
        /*$username = $userProvider->getUsernameForApiKey($apiKey);

        if (!$username) {
            // CAUTION: this message will be returned to the client
            // (so don't put any un-trusted messages / error strings here)
            throw new CustomUserMessageAuthenticationException(
                sprintf('API Key "%s" does not exist.',strip_tags($apiKey))
            );
        }*/

        // on verifie la validité du token
        if(($r = $this->oauth2->verifyToken($apiKey))){

            if(!($result = $r->getResult())){
                throw new CustomUserMessageAuthenticationException(
                    sprintf('Sorry can not validate the token "%s".',strip_tags($apiKey))
                );
            }

            if(!isset($result["active"]) || !@$result["active"]){
                throw new CustomUserMessageAuthenticationException(
                    sprintf('Sorry can not validate the token "%s".',strip_tags($apiKey))
                );
            }
        }

        $username = $result["email"];
        $user = $userProvider->loadUserByUsername($username);

        if (!$user) {
            // CAUTION: this message will be returned to the client
            // (so don't put any un-trusted messages / error strings here)
            throw new CustomUserMessageAuthenticationException(
                sprintf('API Key "%s" does not exist.',strip_tags($apiKey))
            );
        }

        $token = new PreAuthenticatedToken(
            $user,
            $apiKey,
            $providerKey,
            $user->getRoles()
        );
        $token->setAttribute("access_token",$result);
        return $token;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception){
        $data = array(
            // you might translate this message
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        );
        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token){
        return null;
    }
}