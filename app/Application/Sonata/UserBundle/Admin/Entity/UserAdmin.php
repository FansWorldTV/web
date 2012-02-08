<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Sonata\UserBundle\Admin\Entity;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;

use FOS\UserBundle\Model\UserManagerInterface;
use Application\Sonata\UserBundle\Entity\User;

class UserAdmin extends Admin
{
    protected $formOptions = array(
        'validation_groups' => 'admin'
    );

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('username')
            ->add('email')
            ->add('enabled')
            ->add('locked')
            ->add('createdAt')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('username')
            ->add('locked')
            ->add('email')
            ->add('id')
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('username')
                ->add('email')
                ->add('plainPassword', 'text', array('required' => false))
                ->add('type', 'choice', array ('choices' => array(User::TYPE_FAN => 'Fan', User::TYPE_IDOL => 'Idolo', User::TYPE_STAFF => 'Staff')), array ())
            ->end()
            ->with('Personal')
            	->add('sex','choice',array('label'=>'Sexo','required'=>false, 'choices' => array(User::SEX_MALE => 'Hombre', User::SEX_FEMALE => 'Mujer')))
            	->add('birthday', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                	'format' => 'dd/MM/yyyy HH:mm'), array ())	
            	->add('address',null,array('label'=>'Dirección','required'=>false))
				->add('firstname',null,array('label'=>'Nombre','required'=>false))
				->add('lastname',null,array('label'=>'Apellido','required'=>false))
				->add('phone',null,array('label'=>'Teléfono','required'=>false))
				->add('mobile',null,array('label'=>'Móvil','required'=>false))
            ->end()
            ->with('Social')
            	->add('country',null,array('label'=>'País','required'=>false))
				->add('city',null,array('label'=>'Ciudad','required'=>false))
				->add('image', 'sonata_type_model', array(), array('edit' => 'list', 'link_parameters' => array('context' => 'default', 'provider' => 'sonata.media.provider.image')))
				->add('friendships', 'sonata_type_collection', array ('label'=>'Amistades', 'required' => false), 
            	array(
                      'edit' => 'inline',
                	  'inline' => 'table', 
                    )
            	)
            	->add('idols', null, array ('label'=>'Ídolos', 'required' => false), 
            	array(
                      
                    )
            	)
            ->end()
            ->with('Groups')
                ->add('groups', 'sonata_type_model', array('required' => false))
            ->end()
            ->with('Management')
                ->add('roles', 'sonata_security_roles', array( 'multiple' => true, 'required' => false))
                ->add('locked', null, array('required' => false))
                ->add('expired', null, array('required' => false))
                ->add('enabled', null, array('required' => false))
                ->add('credentialsExpired', null, array('required' => false))
            ->end()
        ;
    }

    public function preUpdate($user)
    {
        $this->getUserManager()->updateCanonicalFields($user);
        $this->getUserManager()->updatePassword($user);
    }

    public function setUserManager(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    public function getUserManager()
    {
        return $this->userManager;
    }
}