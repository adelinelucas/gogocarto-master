<?php

namespace App\Admin;

use App\Document\WebhookFormat;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class WebhookAdmin extends GoGoAbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'id',
    ];

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('format', ChoiceType::class, [
                'choices' => [
                    'Brut' => WebhookFormat::Raw,
                    'Mattermost' => WebhookFormat::Mattermost,
                    'Slack' => WebhookFormat::Slack,
                ],
                'expanded' => false,  'multiple' => false,
                'required' => true, 'placeholder' => false,
                ])
            ->add('url', UrlType::class);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('url');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('url')
            ->add('format', 'choice', ['choices' => [
                WebhookFormat::Raw => 'Brut',
                WebhookFormat::Mattermost => 'Mattermost',
                WebhookFormat::Slack => 'Slack',
            ]])
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }
}
