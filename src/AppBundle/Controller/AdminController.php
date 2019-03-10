<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;


/**
* @Route("/admin", name="admin_")
*/
class AdminController extends Controller{

	/**
    * @Route("/", name="index")
    */
    public function indexAction(){
        return $this->render('admin/index.html.twig');
    }

    /**
    * @Route("/users", name="users")
    */
    public function usersAction(){
        return $this->render('admin/users.html.twig');
    }
}
