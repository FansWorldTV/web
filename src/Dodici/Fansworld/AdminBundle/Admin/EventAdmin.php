<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Dodici\Fansworld\WebBundle\Entity\Event;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class EventAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('title', 'text', array ())
            ->add('content', 'textarea', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('fromtime', 'datetime', array ())
            ->add('totime', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('type', 'integer', array ())
            ->add('userCount', 'integer', array ())
            ->add('commentCount', 'integer', array ())
            ->add('slug', 'text', array ())
            ->add('hastags', 'orm_one_to_many', array ())
            ->add('hasusers', 'orm_one_to_many', array ())
            ->add('hasteams', 'orm_one_to_many', array ())
            ->add('comments', 'orm_one_to_many', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $types = Event::getTypes();
    	
    	$formMapper
            ->add('title', NULL, array (), array ())
            ->add('content', NULL, array (), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('fromtime', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('totime', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('active', NULL, array (), array ())
            ->add('type', 'choice', array ('choices' => $types), array ())
            ->add('external', NULL, array (), array ())
            ->add('hasteams', 'sonata_type_collection', array ('required' => false), 
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
            ->add('createdAt', 'datetime', array ())
            ->add('fromtime', 'datetime', array ())
            ->add('totime', 'datetime', array ())
            ->add('active', 'boolean', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'title',))
        ;
    }
    
    public function preUpdate($event) {
	    foreach($event->getHasteams() as $qo) {
	    	$qo->setEvent($event);
	    }
	}
	
	public function prePersist($event) {
	    foreach($event->getHasteams() as $qo) {
	    	$qo->setEvent($event);
	    }
	}
}