<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserLoginType extends AbstractType{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options){
        
       $builder->remove('username');
        $builder->remove('password');
        $builder->remove('save');

        $builder->add('password',PasswordType::class,array(
            'label' => 'Mot de passe'
        ))
        ->add('save',SubmitType::class,array('label'=>"Se connecter"));
        
    }
    
    public function getParent(){
        return UserType::class;
    }
}
