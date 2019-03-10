<?php
// src/AppBundle/Security/ApiTokenAuthenticator.php
namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;


class AppUserFormAuthenticator extends AbstractFormLoginAuthenticator /*AbstractGuardAuthenticator*/{

    private $encoder;
    private $urlGenerator;

    public function __construct(UserPasswordEncoderInterface $encoder,UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager){
        parent::__construct();
        $this->encoder = $encoder;
        $this->urlGenerator = $urlGenerator;
         $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser(). Returning null will cause this authenticator
     * to be skipped.
     */
    public function getCredentials(Request $request){
        $username = $request->request->get('_username');
        $password = $request->request->get('_password');
        $csrf_token = $request->request->get('_csrf_token');

        if(!$username || !$password || !$csrf_token) {
            return null;
        }

        $csrfToken = $request->request->get('_csrf_token');

        if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $csrfToken))) {
            throw new InvalidCsrfTokenException('Invalid CSRF token.');
        }

        // What you return here will be passed to getUser() as $credentials
        return array(
            'username' => $username,
            'password' => $password,
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider){
        $username = $credentials['username'];
        $password = $credentials['password'];

        if (null === $username || $password === null) {
            return;
        }
        
        // if a User object, checkCredentials() is called
        return $userProvider->loadUserByUsername($username);
    }

    public function checkCredentials($credentials, UserInterface $user){
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        $username = $credentials['username'];
        $password = $credentials['password'];

        if(!$this->encoder->isPasswordValid($user,$password)) {
            throw new AuthenticationException("le mot de passe est incorrecte");
        }
        // return true to cause authentication success
        return true;
    }

    public function getLoginUrl(){
        return $url = $this->urlGenerator->generate("aaz_login",$request->query->all(), UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function supportsRememberMe(){
        return false;
    }
}