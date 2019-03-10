<?php
    namespace AppBundle\Controller;

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

    use AppBundle\Entity\User;
    use AppBundle\Form\UserType;

    /**
    * @Route("/aaz", name="aaz_")
    */
    class SecurityController extends Controller{
       
       /**
        * @Route("/login", name="login")
        */
        public function login(Request $req, AuthenticationUtils $utils){
            $error = $utils->getLastAuthenticationError();
            $lastusername  = $utils->getLastUsername();


            if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) { 
               return $this->redirectToRoute('account_index',$req->query->all());
            }

            return $this->render("security/login.html.twig",array(
                "error"=>$error,
                "last_username "=>$lastusername,
            ));
        }

        /**
        * @Route("/login2", name="login2")
        */
        public function login2(Request $req, AuthenticationUtils $utils){
            $error = $utils->getLastAuthenticationError();
            $lastusername  = $utils->getLastUsername();

            return $this->render("security/login.html.twig",array(
                "error"=>$error,
                "last_username "=>$lastusername,
            ));
        }

        /**
        * @Route("/logout", name="logout")
        */
        public function logout(Request $req){
            return null;
        }


        /**
        * @Route("/register", name="registration")
        */
        public function registration(Request $req){

            if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) { 
               return $this->redirectToRoute('account_index');
            }

            $user = new User();
            $form = $this->createForm(UserType::class,$user);
            
            $form->handleRequest($req);

            if($form->isSubmitted() && $form->isValid()){

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('aaz_login');
            }

            return $this->render('security/registration.html.twig',[
                "form"=>$form->createView()
            ]);
        }
    }
