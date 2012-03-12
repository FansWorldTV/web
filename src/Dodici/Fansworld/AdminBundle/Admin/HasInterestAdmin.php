<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class HasInterestAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('career', 'boolean', array ())
            ->add('position', 'text', array ())
            ->add('dateFrom', 'datetime', array ())
            ->add('dateTo', 'datetime', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('interest', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('career', NULL, array (), array ())
            ->add('position', NULL, array (), array ())
            ->add('dateFrom', 'date', array ('required' => false, 'attr' => array('class' => 'datepicker'), 'widget' => 'single_text',
               	'format' => 'dd/MM/yyyy'), array ())	
            ->add('dateTo', 'date', array ('required' => false, 'attr' => array('class' => 'datepicker'), 'widget' => 'single_text',
               	'format' => 'dd/MM/yyyy'), array ())	
            ->add('author', NULL, array (), array ())
            ->add('interest', NULL, array (), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('career', 'boolean', array ())
            ->add('position', 'text', array ())
            ->add('dateFrom', 'datetime', array ())
            ->add('dateTo', 'datetime', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('interest', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('career', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'career',))
            ->add('position', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'position',))
            ->add('author', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'author',  'mapping_type' => 2,))
            ->add('interest', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Interest',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'interest',  'mapping_type' => 2,))
        ;
    }
}