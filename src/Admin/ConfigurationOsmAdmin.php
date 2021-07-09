<?php
/**
 * @Author: Adrien Pavie
 */

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class ConfigurationOsmAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_osm_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-osm';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->panel('host')
                ->add('osm.osmHost', UrlType::class)
                ->end()
            ->panel('account')
                ->add('osm.osmUsername', TextType::class)
                ->add('osm.osmPassword', PasswordType::class)
            ->end()
        ;
    }
}
