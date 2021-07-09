<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-04-22 19:45:15
 */

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ConfigurationMarkerAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_marker_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-marker';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->panel('popup',
                    ['description' => ''])
                ->add('marker.displayPopup', CheckboxType::class)
                ->add('marker.popupAlwaysVisible', CheckboxType::class)
                ->add('marker.popupTemplateUseMarkdown', CheckboxType::class, ['attr' => ['class' => 'use-markdown']])
                ->add('marker.popupTemplate', null, ['attr' => ['class' => 'gogo-code-editor', 'format' => 'twig', 'height' => '200']])
            ->end()
            ->panel('cluster')
                ->add('marker.useClusters', CheckboxType::class)
            ->end()
        ;
    }
}
