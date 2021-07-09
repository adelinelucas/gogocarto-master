<?php

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;

class ConfigurationMobileAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_mobile_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-mobile';
    
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->panel('progressive')
                ->add('appNameShort')
                ->add('hideHeaderInPwa')                
            ->end()
            ->panel('trusted')
                ->add('packageName')
                ->add('sha256CertFingerprints')
            ->end()
        ;
    }
}
