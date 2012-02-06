<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class ContestAdmin extends Admin
{
public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('title', 'text', array ())
            ->add('content', 'textarea', array ())
            ->add('data', 'textarea', array ())
            ->add('active', 'checkbox', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('image', 'orm_many_to_one', array ())
            ->add('participants', 'orm_one_to_many', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title', NULL, array (), array ())
            ->add('image', 'sonata_type_model', array(), array('edit' => 'list', 'link_parameters' => array('context' => 'default', 'provider' => 'sonata.media.provider.image')))
            ->add('content', NULL, array ('attr' => array('class' => 'tinymce')), array ())
            ->add('data', NULL, array ('attr' => array('class' => 'tinymce')), array ())
            ->add('active', NULL, array ('required' => false), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('endDate', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('participants', 'sonata_type_collection', array ('required' => false), 
            	array(
                      'edit' => 'inline',
                	  'inline' => 'table', 
                    )
            )
            ->add('comments', 'sonata_type_collection', array ('required' => false), 
            	array(
                      'edit' => 'inline',
                	  'inline' => 'table', 
                    )
            )
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title', 'text', array ())
            ->add('active', 'boolean', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('endDate', 'datetime', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'title',))
            ->add('content', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'content',))
            ->add('data', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'data',))
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
        ;
    }
    
	public function preUpdate($contest) {
	    foreach($contest->getParticipants() as $qo) {
	        $qo->setContest($contest);
	    }
	}
	
	public function prePersist($contest) {
		foreach($contest->getParticipants() as $qo) {
	        $qo->setContest($contest);
	    }
	}
}