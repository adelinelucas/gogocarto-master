<?php

declare(strict_types=1);

namespace App\Admin;

use App\Enum\NewsStatus;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class NewsAdmin extends GoGoAbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('title')
            ->add('content', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'full',
            ])
            ->add('publicationDate', DateTimeType::class)
            ->add('status', ChoiceType::class, ['choices' => [
                'draft' => NewsStatus::DRAFT,
                'published' => NewsStatus::PUBLISHED,
            ]])
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title', 'text')
            ->add('publicationDate', 'datetime', ['format' => $this->t('commons.date_time_format')])
            ->add('status', 'choice', ['choices' => [
                NewsStatus::DRAFT => $this->t('news.fields.status_choices.draft'),
                NewsStatus::PUBLISHED => $this->t('news.fields.status_choices.published'),
            ]])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('title')
            ->add('status', 'doctrine_mongo_choice', [], ChoiceType::class, ['choices' => [
                $this->t('news.fields.status_choices.published') => NewsStatus::PUBLISHED,
            ]])
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('title', 'text')
            ->add('publicationDate', 'datetime', ['format' => $this->t('commons.date_time_format')])
            ->add('status', 'choice', ['choices' => [
                NewsStatus::DRAFT => $this->t('news.fields.status_choices.draft'),
                NewsStatus::PUBLISHED => $this->t('news.fields.status_choices.published'),
            ]])
        ;
    }
}
