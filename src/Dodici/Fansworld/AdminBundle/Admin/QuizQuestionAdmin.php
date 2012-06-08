<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class QuizQuestionAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('title', 'text', array ())
            ->add('content', 'textarea', array ())
            ->add('active', 'boolean', array ())
            ->add('multiple', 'boolean', array ())
            ->add('results', 'boolean', array ())
            ->add('score', 'integer', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('team', 'orm_many_to_one', array ())
            ->add('idol', 'orm_many_to_one', array ())
            ->add('quizoptions', 'orm_one_to_many', array ())
            ->add('quizanswers', 'orm_one_to_many', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title', NULL, array (), array ())
            ->add('content', NULL, array (), array ())
            ->add('active', NULL, array ('required' => false), array ())
            ->add('multiple', NULL, array ('required' => false), array ())
            ->add('results', NULL, array ('required' => false), array ())
            ->add('score', NULL, array (), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('team', NULL, array (), array ())
            ->add('idol', NULL, array (), array ())
            ->add('quizoptions', 'sonata_type_collection', array ('required' => false), 
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
            ->add('multiple', 'boolean', array ())
            ->add('results', 'boolean', array ())
            ->add('score', 'integer', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('team', 'orm_many_to_one', array ())
            ->add('idol', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'title',))
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
            ->add('multiple', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'multiple',))
            ->add('results', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'results',))
        ;
    }
    
    public function preUpdate($question) {
	    foreach($question->getQuizoptions() as $qo) {
	        $qo->setQuizquestion($question);
	    }
	}
	
	public function prePersist($question) {
	    foreach($question->getQuizoptions() as $qo) {
	        $qo->setQuizquestion($question);
	    }
	}
}