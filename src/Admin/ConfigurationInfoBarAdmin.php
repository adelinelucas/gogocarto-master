<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-04-22 19:45:15
 */

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Helper\GoGoHelper;

class ConfigurationInfoBarAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_map_element_form_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-map-element-form';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $featureFormOption = ['delete' => false, 'required' => false, 'label_attr' => ['style' => 'display:none']];

        $dm = GoGoHelper::getDmFromAdmin($this);
        $apiProperties = $dm->get('Element')->findAllCustomProperties();
        $propertiesText = implode($apiProperties, ',');

        $formMapper
            ->tab('config_infobar._label')
                ->panel('infobar_content')
                    ->add('infobar.headerTemplateUseMarkdown', CheckboxType::class, ['attr' => ['class' => 'use-markdown']])
                    ->add('infobar.headerTemplate', null, ['attr' => ['class' => 'gogo-code-editor', 'format' => 'twig', 'height' => '200']])
                    ->add('infobar.bodyTemplateUseMarkdown', CheckboxType::class, ['attr' => ['class' => 'use-markdown']])
                    ->add('infobar.bodyTemplate', null, ['attr' => ['class' => 'gogo-code-editor', 'data-id' => 'body-template', 'format' => 'twig', 'height' => '500']])
                ->end()
                ->panel('infobar_param')
                    ->add('infobar.width', IntegerType::class)
                ->end()
            ->end()
            ->tab('field_list')
                ->panel('')
                    ->add('elementFormFieldsJson', HiddenType::class, ['attr' => ['class' => 'gogo-form-fields', 'dataproperties' => $propertiesText]])
                ->end()
            ->end()
            ->tab('filter_list')
                ->panel('automatic_emails', ['box_class' => 'box box-default'])->end()
            ->end()
        ;
    }
}
