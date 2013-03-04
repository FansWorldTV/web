<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class VideoAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('title', 'text', array ())
            ->add('content', 'textarea', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('duration', 'integer', array ())
            ->add('stream', 'integer', array ())
            ->add('youtube', 'text', array ())
            ->add('privacy', 'integer', array ())
            ->add('slug', 'text', array ())
            ->add('videocategory', 'orm_many_to_one', array ())
            ->add('image', 'orm_many_to_one', array ())
            ->add('comments', 'orm_one_to_many', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title', NULL, array (), array ())
            ->add('author', NULL, array ('required' => true), array ())
            ->add('content', NULL, array (), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('active', NULL, array ('required' => false), array ())
            ->add('processed', NULL, array ('required' => false), array ())
            ->add('notified', NULL, array ('required' => false), array ())
            ->add('highlight', NULL, array ('required' => false), array ())
            ->add('duration', NULL, array (), array ())
            ->add('stream', NULL, array (), array ())
            ->add('youtube', NULL, array (), array ())
            ->add('event', NULL, array ('required' => false), array ())
            ->add('privacy', 'choice', array ('choices' => \Dodici\Fansworld\WebBundle\Entity\Privacy::getOptions()), array ())
            ->add('videocategory', NULL, array ('required' => true), array ())
            ->add('image', 'sonata_type_model', array(), array('edit' => 'list', 'link_parameters' => array('context' => 'default', 'provider' => 'sonata.media.provider.image')))
            ->add('splash', 'sonata_type_model', array(), array('edit' => 'list', 'link_parameters' => array('context' => 'default', 'provider' => 'sonata.media.provider.image')))
            ->add('comments', 'sonata_type_collection', array ('required' => false), 
            	array(
                      'edit' => 'inline',
                	  'inline' => 'table', 
                    )
            )
            ->add('hastags', 'sonata_type_collection', array ('required' => false), 
            	array(
                      'edit' => 'inline',
                	  'inline' => 'table', 
                    )
            )
            ->add('hasusers', 'sonata_type_collection', array ('required' => false), 
            	array(
                      'edit' => 'inline',
                	  'inline' => 'table', 
                    )
            )
            ->add('hasteams', 'sonata_type_collection', array ('required' => false), 
            	array(
                      'edit' => 'inline',
                	  'inline' => 'table', 
                    )
            )
            ->add('hasidols', 'sonata_type_collection', array ('required' => false), 
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
            ->add('author', 'orm_many_to_one', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('highlight', 'boolean', array ())
            ->add('videocategory', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'title',))
            ->add('author', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'author',  'mapping_type' => 2,))
            ->add('content', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'content',))
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
            ->add('highlight', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'highlight',))
            ->add('videocategory', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\VideoCategory',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'videocategory',  'mapping_type' => 2,))
        ;
    }

	public function preUpdate($video) {
	    foreach($video->getHastags() as $qo) {
	    	$qo->setVideo($video);
	    }
	    foreach($video->getHasusers() as $qo) {
	    	$qo->setVideo($video);
	    }
	    foreach($video->getHasteams() as $qo) {
	    	$qo->setVideo($video);
	    }
	    foreach($video->getHasidols() as $qo) {
	    	$qo->setVideo($video);
	    }
	}
	
	public function prePersist($video) {
	    foreach($video->getHastags() as $qo) {
	    	$qo->setVideo($video);
	    }
	    foreach($video->getHasusers() as $qo) {
	    	$qo->setVideo($video);
	    }
	    foreach($video->getHasteams() as $qo) {
	    	$qo->setVideo($video);
	    }
	    foreach($video->getHasidols() as $qo) {
	    	$qo->setVideo($video);
	    }
	}
}