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
            ->add('content', NULL, array (), array ())
            ->add('createdAt', NULL, array (), array ())
            ->add('active', NULL, array (), array ())
            ->add('duration', NULL, array (), array ())
            ->add('stream', NULL, array (), array ())
            ->add('youtube', NULL, array (), array ())
            ->add('privacy', NULL, array (), array ())
            ->add('slug', NULL, array (), array ())
            ->add('videocategory', NULL, array (), array ())
            ->add('image', NULL, array (), array ())
            ->add('comments', NULL, array (), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
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

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'title',))
            ->add('content', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'content',))
            ->add('active', 'doctrine_orm_boolean', array (  'field_type' => 'sonata_type_boolean',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'active',))
            ->add('duration', 'doctrine_orm_number', array (  'field_type' => 'number',  'field_options' =>   array (    'csrf_protection' => false,  ),  'options' =>   array (  ),  'field_name' => 'duration',))
            ->add('stream', 'doctrine_orm_number', array (  'field_type' => 'number',  'field_options' =>   array (    'csrf_protection' => false,  ),  'options' =>   array (  ),  'field_name' => 'stream',))
            ->add('youtube', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'youtube',))
            ->add('privacy', 'doctrine_orm_number', array (  'field_type' => 'number',  'field_options' =>   array (    'csrf_protection' => false,  ),  'options' =>   array (  ),  'field_name' => 'privacy',))
            ->add('slug', 'doctrine_orm_string', array (  'field_type' => 'text',  'field_options' =>   array (  ),  'options' =>   array (  ),  'field_name' => 'slug',))
            ->add('videocategory', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\VideoCategory',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'videocategory',  'mapping_type' => 2,))
            ->add('image', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\MediaBundle\\Entity\\Media',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'image',  'mapping_type' => 2,))
            ->add('comments', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Comment',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'comments',  'mapping_type' => 4,))
        ;
    }
}