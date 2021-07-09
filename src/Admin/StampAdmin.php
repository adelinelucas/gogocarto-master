<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-04-06 10:08:00
 */

namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class StampAdmin extends GoGoAbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('name');
        $formMapper->add('isPublic');
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('name');
        $datagridMapper->add('isPublic');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
         ->addIdentifier('name')
         ->add('_action', 'actions', [
             'actions' => [
                 'show' => [],
                 'edit' => [],
                 'delete' => [],
             ],
         ]);
    }
}
