<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class FriendshipAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('target', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('active', NULL, array ('required' => false), array ())
            ->add('author', NULL, array (), array ())
            ->add('target', NULL, array (), array ())
            ->add('friendgroups', NULL, array(), array())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('target', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
            ->add('author', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'author',  'mapping_type' => 2,))
            ->add('target', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'target',  'mapping_type' => 2,))
        ;
    }
}