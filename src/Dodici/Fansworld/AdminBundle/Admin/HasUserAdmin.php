<?php
namespace Dodici\Fansworld\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class HasUserAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('createdAt', 'datetime', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('target', 'orm_many_to_one', array ())
            ->add('newspost', 'orm_many_to_one', array ())
            ->add('video', 'orm_many_to_one', array ())
            ->add('photo', 'orm_many_to_one', array ())
            ->add('album', 'orm_many_to_one', array ())
            ->add('contest', 'orm_many_to_one', array ())
            ->add('comment', 'orm_many_to_one', array ())
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('createdAt', 'date', array ('attr' => array('class' => 'datetimepicker'), 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'), array ())
            ->add('author', NULL, array (), array ())
            ->add('target', NULL, array (), array ())
//            ->add('newspost', NULL, array (), array ())
//            ->add('video', NULL, array (), array ())
//            ->add('photo', NULL, array (), array ())
//            ->add('album', NULL, array (), array ())
//            ->add('contest', NULL, array (), array ())
//            ->add('comment', NULL, array (), array ())
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('createdAt', 'datetime', array ())
            ->add('author', 'orm_many_to_one', array ())
            ->add('target', 'orm_many_to_one', array ())
            ->add('newspost', 'orm_many_to_one', array ())
            ->add('video', 'orm_many_to_one', array ())
            ->add('photo', 'orm_many_to_one', array ())
            ->add('album', 'orm_many_to_one', array ())
            ->add('contest', 'orm_many_to_one', array ())
            ->add('comment', 'orm_many_to_one', array ())
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('author', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'author',  'mapping_type' => 2,))
            ->add('target', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Application\\Sonata\\UserBundle\\Entity\\User',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'target',  'mapping_type' => 2,))
            ->add('newspost', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\NewsPost',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'newspost',  'mapping_type' => 2,))
            ->add('video', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Video',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'video',  'mapping_type' => 2,))
            ->add('photo', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Photo',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'photo',  'mapping_type' => 2,))
            ->add('album', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Album',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'album',  'mapping_type' => 2,))
            ->add('contest', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Contest',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'contest',  'mapping_type' => 2,))
            ->add('comment', 'doctrine_orm_model', array (  'field_type' => 'entity',  'field_options' =>   array (    'class' => 'Dodici\\Fansworld\\WebBundle\\Entity\\Comment',  ),  'options' =>   array (  ),  'operator_type' => 'sonata_type_boolean',  'operator_options' =>   array (  ),  'field_name' => 'comment',  'mapping_type' => 2,))
        ;
    }
}