<?php

/*
 * Override of FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Sonata\UserBundle\Form\Type;

use Application\Sonata\UserBundle\Entity\User;

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;

class ProfileFormType extends BaseType
{
	public function buildUserForm(FormBuilder $builder, array $options)
    {
        //parent::buildUserForm($builder, $options);

        // add your custom field
        $builder
            ->add('username',null, array('max_length' => 30))
            ->add('email', 'email')
			->add('address',null,array('label'=>'Dirección','required'=>false))
			->add('firstname',null,array('label'=>'Nombre','required'=>true))
			->add('lastname',null,array('label'=>'Apellido','required'=>true))
			->add('sex','choice',array('label'=>'Sexo','required'=>false, 'choices' => array(User::SEX_MALE => 'Hombre', User::SEX_FEMALE => 'Mujer')))
            ->add('birthday', 'date', array ('required' => false, 'attr' => array('class' => 'datepicker'), 'widget' => 'single_text',
               	'format' => 'dd/MM/yyyy'), array ())
            ->add('country',null,array('label'=>'País','required'=>false))
			->add('city',null,array('label'=>'Ciudad','required'=>false))
			->add('phone',null,array('label'=>'Teléfono','required'=>false))
			
			/*
			->add('mobile',null,array('label'=>'Móvil','required'=>false))
			->add('skype',null,array('label'=>'Skype','required'=>false))
			->add('msn',null,array('label'=>'MSN','required'=>false))
			->add('twitter',null,array('label'=>'Twitter','required'=>false))
			->add('yahoo',null,array('label'=>'Yahoo','required'=>false))
			->add('gmail',null,array('label'=>'Gmail','required'=>false))
			*/
            
			->add('content',null,array('label'=>'Sobre Mí','required'=>false))
			->add('restricted',null,array('label'=>'Modo Restringido','required'=>false))	
        ;
        
        $builder->setAttribute('label', 'Modificar mis datos:');
        
    }

    public function getName()
    {
        return 'app_user_profile';
    }

}
