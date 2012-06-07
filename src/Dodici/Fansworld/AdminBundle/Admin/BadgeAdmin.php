<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Dodici\Fansworld\WebBundle\Entity\Badge;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class BadgeAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('title', 'text', array ())
            ->add('content', 'textarea', array ())
            ->add('type', 'integer', array ())
            ->add('badgesteps', 'orm_one_to_many', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $types = Badge::getTypes();
        $formMapper
            ->add('title', NULL, array (), array ())
            ->add('content', NULL, array (), array ())
            ->add('type', 'choice', array ('choices' => $types), array ())
            ->add('badgesteps', 'sonata_type_collection', array ('required' => false), 
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
            ->add('title', 'text', array ())
            ->add('typeName', 'text', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'title',))
        ;
    }
    
    public function preUpdate($badge) {
	    foreach($badge->getBadgeSteps() as $qo) {
	        $qo->setBadge($badge);
	    }
	}
	
	public function prePersist($badge) {
	    foreach($badge->getBadgeSteps() as $qo) {
	        $qo->setBadge($badge);
	    }
	}
}