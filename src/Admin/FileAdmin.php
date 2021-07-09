<?php

namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class FileAdmin extends GoGoAbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'updatedAt',
    ];

    protected function configureFormFields(FormMapper $formMapper)
    {
        $disable = $this->getSubject()->getId();
        $formMapper
            ->add('file', FileType::class)
            ->add('fileName', null, ['disabled' => $disable])
            ->add('customDirectory', null, ['disabled' => $disable])
            ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('fileName');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('fileName')
            ->add('fileUrl')
            ->add('_action', 'actions', [
                'actions' => [
                    'delete' => [],
                ],
            ]);
    }
}
