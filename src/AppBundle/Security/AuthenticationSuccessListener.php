<?php 
namespace AppBundle\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessListener implements AuthenticationSuccessHandlerInterface{

	public function onAuthenticationSuccess(Request $request, TokenInterface $token){
		var_dump($token);
	}
}