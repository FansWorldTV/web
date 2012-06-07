<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class BadgeStepAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('minimum', 'integer', array ())
            ->add('image', 'orm_many_to_one', array ())
            ->add('badge', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('minimum', NULL, array (), array ())
            ->add('image', 'sonata_type_model', array(), array('edit' => 'list', 'link_parameters' => array('context' => 'default', 'provider' => 'sonata.media.provider.image')))
            ->add('badge', NULL, array (), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('minimum', 'integer', array ())
            ->add('image', 'orm_many_to_one', array ())
            ->add('badge', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('badge', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Badge',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'badge',  'mapping_type' => 2,))
        ;
    }
}