<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
* @Route("/account", name="account_")
*/
class AccountController extends Controller
{
    /**
     * @Route("/", name="index")
     */
    public function indexAction(Request $request){

    	//var_dump($request);
        return $this->render('account/index.html.twig');
    }

}
