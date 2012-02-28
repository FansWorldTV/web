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
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;

class ProfileFormType extends BaseType
{
	public function buildUserForm(FormBuilder $builder, array $options)
    {
        parent::buildUserForm($builder, $options);

        // add your custom field
        $builder
			->add('address')
			->add('firstname')
			->add('lastname')
			->add('phone')
			->add('mobile')
        ;
        
        $builder->setAttribute('label', 'Modificar mis datos:');
    }

    public function getName()
    {
        return 'app_user_profile';
    }

}
