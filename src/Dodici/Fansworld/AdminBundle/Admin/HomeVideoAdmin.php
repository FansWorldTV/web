<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class HomeVideoAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('position', 'integer', array ())
            ->add('video', 'orm_many_to_one', array ())
            ->add('videocategory', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('position', NULL, array ('required' => false), array ())
            ->add('video', NULL, array (), array ())
            ->add('videocategory', NULL, array ('required' => false), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('position', 'integer', array ())
            ->add('video', 'orm_many_to_one', array ())
            ->add('videocategory', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('position', 'doctrine_orm_number', array (  'field_type' => 'number',  'field_options' =>   array (    'csrf_protection' => false,  ),  'options' =>   array (  ),  'field_name' => 'position',))
            ->add('video', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Video',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'video',  'mapping_type' => 2,))
            ->add('videocategory', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\VideoCategory',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'videocategory',  'mapping_type' => 2,))
        ;
    }
}