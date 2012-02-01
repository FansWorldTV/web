<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class CommentAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('content', 'textarea', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('privacy', 'integer', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('newspost', 'orm_many_to_one', array ())
            ->add('target', 'orm_many_to_one', array ())
            ->add('video', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('content', NULL, array (), array ())
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('active', NULL, array ('required' => false), array ())
            ->add('privacy', 'choice', array ('choices' => \Dodici\Fansworld\WebBundle\Entity\Privacy::getOptions()), array ())
            ->add('author', NULL, array (), array ())
            ->add('newspost', NULL, array ('required' => false), array ())
            ->add('target', NULL, array ('required' => false), array ())
            ->add('video', NULL, array ('required' => false), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('slimContent', 'textarea', array ())
            ->add('createdAt', 'datetime', array ())
            ->add('active', 'boolean', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('newspost', 'orm_many_to_one', array ())
            ->add('target', 'orm_many_to_one', array ())
            ->add('video', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('content', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'content',))
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
            ->add('privacy', 'doctrine_orm_number', array (  'field_type' => 'number',  'field_options' =>   array (    'csrf_protection' => false,  ),  'options' =>   array (  ),  'field_name' => 'privacy',))
            ->add('author', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'author',  'mapping_type' => 2,))
            ->add('newspost', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\NewsPost',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'newspost',  'mapping_type' => 2,))
            ->add('target', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'target',  'mapping_type' => 2,))
            ->add('video', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Video',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'video',  'mapping_type' => 2,))
        ;
    }
}