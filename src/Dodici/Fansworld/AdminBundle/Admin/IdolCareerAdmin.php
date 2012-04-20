<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class IdolCareerAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('teamname', 'text', array ())
            ->add('position', 'text', array ())
            ->add('content', 'textarea', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('dateFrom', 'datetime', array ())
            ->add('dateTo', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('idol', 'orm_many_to_one', array ())
            ->add('team', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('idol', NULL, array (), array ())
            ->add('team', NULL, array (), array ())
            ->add('teamname', NULL, array (), array ())
            ->add('position', NULL, array (), array ())
            ->add('content', NULL, array (), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('dateFrom', 'date', array ('required' => false, 'attr' => array('class' => 'datepicker'), 'widget' => 'single_text',
               	'format' => 'dd/MM/yyyy'), array ())	
            ->add('dateTo', 'date', array ('required' => false, 'attr' => array('class' => 'datepicker'), 'widget' => 'single_text',
               	'format' => 'dd/MM/yyyy'), array ())	
            ->add('active', NULL, array ('required' => false), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
        	->add('idol', 'orm_many_to_one', array ())
            ->add('team', 'orm_many_to_one', array ())
            ->add('teamname', 'text', array ())
            ->addIdentifier('position', 'text', array ())
            ->add('dateFrom', 'datetime', array ())
            ->add('dateTo', 'datetime', array ())
            ->add('active', 'boolean', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('teamname', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'teamname',))
            ->add('position', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'position',))
            ->add('content', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'content',))
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
            ->add('idol', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'idol',  'mapping_type' => 2,))
            ->add('team', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Team',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'team',  'mapping_type' => 2,))
        ;
    }
}