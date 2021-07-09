<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-06-09 14:29:33
 */

namespace App\Admin\Element;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\ElementImageType;
use App\Form\ElementFileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Helper\GoGoHelper;

class ElementAdminShowEdit extends ElementAdminList
{
    public $config;

    protected function configureFormFields(FormMapper $formMapper)
    {
        $dm = GoGoHelper::getDmFromAdmin($this);
        $this->config = $dm->get('Configuration')->findConfiguration();
        $categories = $dm->query('Option')->select('name')->getArray();
        $categoriesChoices = array_flip($categories);
        $elementProperties = $dm->get('Element')->findDataCustomProperties();
        $elementProperties = array_values(array_diff($elementProperties, array_keys($this->getSubject()->getData())));

        $formMapper
          ->panel('general', ['class' => 'col-md-6'])
            ->add('name', null, ['required' => true])
            ->add('optionIds', ChoiceType::class, [
              'multiple' => true,
              'choices' => $categoriesChoices], ['admin_code' => 'admin.options'])
            ->add('data', null, [
              'label_attr' => ['style' => 'display:none;'],
              'attr' => [
                'class' => 'gogo-element-data',
                'data-props' => json_encode($elementProperties)
              ]])
            ->add('userOwnerEmail', EmailType::class)
            ->add('email', EmailType::class)
            ->add('images', CollectionType::class, [
              'entry_type' => ElementImageType::class,
              'allow_add' => true,
              'allow_delete' => true
            ])
            ->add('files', CollectionType::class, [
              'entry_type' => ElementFileType::class,
              'allow_add' => true,
              'allow_delete' => true
            ])
            // ->add('openHours', OpenHoursType::class, ['required' => false])
          ->end()
          ->panel('localisation', ['class' => 'col-md-6'])
            ->add('address.streetAddress', TextType::class, ['label_attr' => ['style' => 'display:none;'], 'attr' => ['class' => 'gogo-element-address']])
          ->end()
        ;
    }

    protected function configureShowFields(ShowMapper $show)
    {
        $needModeration = 0 != $this->subject->getModerationState();

        $show
          ->with('elements.form.groups.otherInfos', ['class' => 'col-md-6'])
            ->add('id')
            ->add('randomHash')
            ->add('oldId')
            ->add('sourceKey')
            ->add('createdAt', 'datetime', ['format' => $this->t('commons.date_time_format')])
            ->add('updatedAt', 'datetime', ['format' => $this->t('commons.date_time_format')])
          ->end();

        if ($this->subject->isPending()) {
          $show->with('elements.form.groups.pending', ['class' => 'col-md-6'])
            ->add('currContribution', null, ['template' => 'admin/partials/show_one_contribution.html.twig'])->end();
        } else {
          $show->with('elements.fields.status', ['class' => 'col-md-6'])
            ->add('status', ChoiceType::class, [
              'template' => 'admin/partials/show_choice_status.html.twig',
            ])->end();
        }

        if ($needModeration) {
            $show
              ->with('elements.form.groups.moderation', ['class' => 'col-md-6'])
                ->add('moderationState', ChoiceType::class, ['template' => 'admin/partials/show_choice_moderation.html.twig',])
                ->add('reports', null, ['template' => 'admin/partials/show_pending_reports.html.twig'])
              ->end();
        }

        $show
          ->with('elements.form.groups.show_contributions')
            ->add('contributions', null, ['template' => 'admin/partials/show_contributions.html.twig'])
          ->end();

        $show
          ->with('JSON', ['box_class' => 'box box-default'])
            ->add('compactJson')
            ->add('baseJson')
            ->add('adminJson')
          ->end();
    }
}