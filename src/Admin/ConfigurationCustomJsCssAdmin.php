<?php

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;

class ConfigurationCustomJsCssAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_custom_js_css_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-custom-js-css';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->tab('custom_style')
                ->panel('custom_style_hint')
                    ->add('customCSS', null, ['attr' => ['class' => 'gogo-code-editor', 'format' => 'css', 'height' => '500']])
                ->end()
            ->end()
            ->tab('custom_javascript')
                ->panel('custom_javascript_hint')
                    ->add('customJavascript', null, ['attr' => ['class' => 'gogo-code-editor', 'format' => 'javascript', 'height' => '500']])
                ->end()
            ->end()
        ;
    }
}
