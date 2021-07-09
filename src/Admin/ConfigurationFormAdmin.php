<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-04-22 19:45:15
 */

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Helper\GoGoHelper;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;

class ConfigurationFormAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_form_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-form';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $dm = GoGoHelper::getDmFromAdmin($this);
        $repo = $dm->get('Element');
        $elementProperties = json_encode($repo->findAllCustomProperties());

        $formMapper
            ->tab('form')
                ->panel('config')
                    ->add('elementFormFieldsJson', HiddenType::class, ['attr' => ['class' => 'gogo-form-builder', 'data-props' => $elementProperties]])
                ->end()
            ->end()
            ->tab('other')
                ->panel('other', ['class' => 'col-md-12'])
                    ->add('elementFormIntroText', SimpleFormatterType::class, [
                        'format' => 'richhtml', 'ckeditor_context' => 'full',
                    ])
                    ->add('elementFormValidationText', TextareaType::class)
                    ->add('elementFormOwningText', TextareaType::class)
                    ->add('elementFormGeocodingHelp')
                ->end()
            ->end()
            ;
    }
}
