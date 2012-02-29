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

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

class RegistrationFormType extends BaseType
{
	public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // add your custom field
        $builder
			//->add('address',null,array('label'=>'Dirección','required'=>false))
			->add('firstname',null,array('label'=>'Nombre','required'=>true))
			->add('lastname',null,array('label'=>'Apellido','required'=>true))
			//->add('phone',null,array('label'=>'Teléfono','required'=>false))
			//->add('mobile',null,array('label'=>'Móvil','required'=>false))
        ;
    }

    public function getName()
    {
        return 'app_user_registration';
    }

}
