<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Dodici\Fansworld\WebBundle\Entity\Eventship;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class EventshipAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('type', 'integer', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('event', 'orm_many_to_one', array ())
            ->add('team', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $types = Eventship::getTypes();
    	
    	$formMapper
            ->add('type', 'choice', array ('choices' => $types), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('author', NULL, array (), array ())
            ->add('event', NULL, array (), array ())
            ->add('team', NULL, array ('required' => false), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('type', 'integer', array ())
            ->addIdentifier('createdAt', 'datetime', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('event', 'orm_many_to_one', array ())
            ->add('team', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('type', 'doctrine_orm_number', array (  'field_type' => 'number',  'field_options' =>   array (    'csrf_protection' => false,  ),  'options' =>   array (  ),  'field_name' => 'type',))
            ->add('author', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'author',  'mapping_type' => 2,))
            ->add('event', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Event',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'event',  'mapping_type' => 2,))
            ->add('team', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Team',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'team',  'mapping_type' => 2,))
        ;
    }
}