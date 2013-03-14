<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Dodici\Fansworld\WebBundle\Entity\Idol;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class IdolAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('firstname', 'text', array ())
            ->add('lastname', 'text', array ())
            ->add('nicknames', 'textarea', array ())
            ->add('content', 'textarea', array ())
            ->add('birthday', 'datetime', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('origin', 'text', array ())
            ->add('sex', 'text', array ())
            ->add('twitter', 'text', array ())
            ->add('score', 'integer', array ())
            ->add('slug', 'text', array ())
            ->add('fanCount', 'integer', array ())
            ->add('image', 'orm_many_to_one', array ())
            ->add('idolcareers', 'orm_one_to_many', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('firstname', NULL, array (), array ())
            ->add('lastname', NULL, array (), array ())
            ->add('nicknames',null,array('label'=>'Apodos','required'=>false))
            ->add('content', NULL, array (), array ())
            ->add('birthday', 'date', array ('required' => false, 'attr' => array('class' => 'datepicker'), 'widget' => 'single_text',
                	'format' => 'dd/MM/yyyy'), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('active', NULL, array ('required' => false), array ())
            ->add('origin',null,array('label'=>'Lugar de origen','required'=>false))
            ->add('sex','choice',array('label'=>'Sexo','required'=>false, 'choices' => array(Idol::SEX_MALE => 'Hombre', Idol::SEX_FEMALE => 'Mujer')))
            ->add('twitter', NULL, array (), array ())
            ->add('image', 'sonata_type_model', array(), array('edit' => 'list', 'link_parameters' => array('context' => 'default', 'provider' => 'sonata.media.provider.image')))
            ->add('splash', 'sonata_type_model', array(), array('edit' => 'list', 'link_parameters' => array('context' => 'default', 'provider' => 'sonata.media.provider.image')))
            ->add('idolcareers', 'sonata_type_collection', array ('label'=>'Equipos Carrera', 'required' => false), 
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
            ->addIdentifier('firstname', 'text', array ())
            ->add('lastname', 'text', array ())
            ->add('active', 'boolean', array ())
            ->add('fanCount', 'integer', array ())
            ->add('team', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('firstname', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'firstname',))
            ->add('lastname', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'lastname',))
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
            ->add('sex', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'sex',))
        ;
    }
    
	public function preUpdate($idol) {
	    foreach($idol->getIdolcareers() as $qo) {
	        $qo->setIdol($idol);
	    }
	}
	
	public function prePersist($idol) {
	    foreach($idol->getIdolcareers() as $qo) {
	        $qo->setIdol($idol);
	    }
	}
}