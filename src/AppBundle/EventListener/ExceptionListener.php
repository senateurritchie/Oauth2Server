<?php
namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Service\Exception\Oauth2Exception;
use AppBundle\Service\Exception\ApiException;
use AppBundle\Service\Exception\Oauth2RedirectionException;


class ExceptionListener{

    public function onKernelException(GetResponseForExceptionEvent $event){
        $exception = $event->getException();
       
        if ($exception instanceof Oauth2Exception) {
            $data = array(
                "error"=>$exception->getErrorType(),
                "error_description"=>$exception->getMessage(),
            );

            if($exception->getErrorUri()){
                $data['error_uri'] = $exception->getErrorUri();
            }
            if($exception->getState()){
                $data['state'] = $exception->getState();
            }

            if ($exception instanceof ApiException) {
                $data['code'] = $exception->getErrorCode();
            }

            $response = new JsonResponse($data);
            $response->setStatusCode($exception->getStatusCode());
            //$response->headers->replace($exception->getHeaders());
            $event->setResponse($response);
        }
        else if ($exception instanceof Oauth2RedirectionException) {
            $response = new RedirectResponse($exception->getMessage());
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
            $event->setResponse($response);
        }

    }
}