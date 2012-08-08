<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class VideoCategorySubscriptionAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('createdAt', 'datetime', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('videocategory', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('createdAt', 'date', array ('required' => true, 'attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('author', NULL, array ('required' => true), array ())
            ->add('videocategory', NULL, array ('required' => true), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('createdAt', 'datetime', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('videocategory', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('author', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'author',  'mapping_type' => 2,))
            ->add('videocategory', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\VideoCategory',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'videocategory',  'mapping_type' => 2,))
        ;
    }
}