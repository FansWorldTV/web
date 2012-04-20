<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class PhotoAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('title', 'text', array ())
            ->add('content', 'textarea', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('privacy', 'integer', array ())
            ->add('slug', 'text', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('album', 'orm_many_to_one', array ())
            ->add('image', 'orm_many_to_one', array ())
            ->add('comments', 'orm_one_to_many', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title', NULL, array (), array ())
            ->add('content', NULL, array (), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('active', NULL, array ('required' => false), array ())
            ->add('privacy', 'choice', array ('choices' => \Dodici\Fansworld\WebBundle\Entity\Privacy::getOptions()), array ())
            ->add('author', NULL, array (), array ())
            ->add('album', NULL, array (), array ())
            ->add('image', 'sonata_type_model', array(), array('edit' => 'list', 'link_parameters' => array('context' => 'default', 'provider' => 'sonata.media.provider.image')))
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
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title', 'text', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('album', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'title',))
            ->add('content', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'content',))
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
            ->add('author', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'author',  'mapping_type' => 2,))
            ->add('album', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Album',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'album',  'mapping_type' => 2,))
        ;
    }

	public function preUpdate($photo) {
	    foreach($photo->getHastags() as $qo) {
	    	$qo->setPhoto($photo);
	    }
	    foreach($photo->getHasusers() as $qo) {
	    	$qo->setPhoto($photo);
	    }
	    foreach($photo->getHasteams() as $qo) {
	    	$qo->setPhoto($photo);
	    }
	}
	
	public function prePersist($photo) {
	    foreach($photo->getHastags() as $qo) {
	    	$qo->setPhoto($photo);
	    }
	    foreach($photo->getHasusers() as $qo) {
	    	$qo->setPhoto($photo);
	    }
	    foreach($photo->getHasteams() as $qo) {
	    	$qo->setPhoto($photo);
	    }
	}
}