<?php

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Helper\GoGoHelper;

class ConfigurationDuplicatesAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_duplicates_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-duplicates';

    public function getTemplate($name)
    {
        switch ($name) {
            // overwrite edit template so we hide delete button in actions menu
            case 'edit': return 'admin/edit/edit_configuration_duplicates.html.twig';
            break;
            default: return parent::getTemplate($name);
            break;
        }
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $dm = GoGoHelper::getDmFromAdmin($this);
        $props = $dm->get('Element')->findAllCustomProperties();
        array_unshift($props, 'name');
        $propsChoices = [];
        foreach ($props as $value) {
            $propsChoices[$value] = $value;
        }

        $sourceList = $dm->query('Element')->distinct('sourceKey')->getArray();
        $sourceList = array_merge($sourceList, $dm->query('Import')->distinct('sourceName')->getArray());
        $sourceList = array_unique($sourceList);
        // Remove no more used sources
        $priorityList = $this->getSubject()->getDuplicates()->getSourcePriorityInAutomaticMerge();
        $newPriorityList = [];
        foreach($priorityList as $source) {
            if (in_array($source, $sourceList)) $newPriorityList[] = $source;
        }
        // Adds new source to the end
        foreach($sourceList as $source) {
            if (!in_array($source, $newPriorityList)) $newPriorityList[] = $source;
        }
        $this->getSubject()->getDuplicates()->setSourcePriorityInAutomaticMerge($newPriorityList);
        $searchFields = implode(', ', $this->getSubject()->getDuplicates()->getFieldsInvolvedInGlobalSearch());
        $formMapper
            ->panel('configuration')
                ->add('duplicates.useGlobalSearch', CheckboxType::class, [
                    'label' => $this->t('config_duplicates.fields.duplicates.useGlobalSearch', ['%fields%' => $searchFields]) ])
                ->add('duplicates.fieldsToBeUsedForComparaison', ChoiceType::class, [
                    'choices' => $propsChoices,'multiple' => true])
                ->add('duplicates.rangeInMeters')
                ->add('duplicates.detectAfterImport', CheckboxType::class)
            ->end()
            
            ->panel('fusion')
                ->add('duplicates.automaticMergeIfPerfectMatch', CheckboxType::class)
                ->add('duplicates.sourcePriorityInAutomaticMerge', null, [
                    'attr' => [
                        'class' => 'gogo-source-priority',
                        'data-source-list' => $sourceList]])
            ->end()

            ->with('manualDetection', ['box_class' => 'box box-default'])
                ->add('duplicates.sourcesToDetectFrom', ChoiceType::class, [
                    'choice_label' => function ($choice, $key, $value) {
                        if ('' === $choice) return $this->trans('js.import.source_this_map');  
                        return $choice;
                    },
                    'choices' => $sourceList,
                    'multiple' => true])
                ->add('duplicates.sourcesToDetectWith', ChoiceType::class, [
                    'choices' => $sourceList,
                    'choice_label' => function ($choice, $key, $value) {
                        if ('' === $choice) return $this->trans('js.import.source_this_map');              
                        return $choice;
                    },
                    'multiple' => true])
            ->end()
        ;
    }
}
