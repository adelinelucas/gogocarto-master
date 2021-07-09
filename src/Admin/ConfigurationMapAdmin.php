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
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Helper\GoGoHelper;

class ConfigurationMapAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_map_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-map';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $featureStyle = ['class' => 'col-md-6 col-lg-3 gogo-feature'];
        $featureFormOption = ['delete' => false, 'required' => false, 'label_attr' => ['style' => 'display:none']];
        $featureFormTypeOption = ['edit' => 'inline'];
        $dm = GoGoHelper::getDmFromAdmin($this);
        $config = $dm->get('Configuration')->findConfiguration();

        $formMapper
            ->tab('params')
                ->panel('map')
                    ->add('defaultTileLayer', ModelType::class, ['class' => 'App\Document\TileLayer'])
                    ->add('defaultViewPicker', HiddenType::class, ['mapped' => false, 'attr' => [
                                                        'class' => 'gogo-viewport-picker',
                                                        'data-title-layer' => $config->getDefaultTileLayer()->getUrl(),
                                                        'data-default-bounds' => json_encode($config->getDefaultBounds()),
                                                    ]])
                    ->add('defaultNorthEastBoundsLat', HiddenType::class, ['attr' => ['class' => 'bounds NELat']])
                    ->add('defaultNorthEastBoundsLng', HiddenType::class, ['attr' => ['class' => 'bounds NELng']])
                    ->add('defaultSouthWestBoundsLat', HiddenType::class, ['attr' => ['class' => 'bounds SWLat']])
                    ->add('defaultSouthWestBoundsLng', HiddenType::class, ['attr' => ['class' => 'bounds SWLng']])
                ->end()
                ->panel('cookies')
                    ->add('saveViewportInCookies', CheckboxType::class)
                    ->add('saveTileLayerInCookies', CheckboxType::class)
                ->end()
            ->end()
            ->tab('features')
                ->panel('favoriteFeature', $featureStyle)
                    ->add('favoriteFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('shareFeature', $featureStyle)
                    ->add('shareFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('directionsFeature', $featureStyle)
                    ->add('directionsFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('reportFeature', $featureStyle)
                    ->add('reportFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('stampFeature', $featureStyle)
                    ->add('stampFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('listModeFeature', $featureStyle)
                    ->add('listModeFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('layersFeature', $featureStyle)
                    ->add('layersFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('mapDefaultViewFeature', $featureStyle)
                    ->add('mapDefaultViewFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('exportIframeFeature', $featureStyle)
                    ->add('exportIframeFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('pendingFeature', $featureStyle)
                    ->add('pendingFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->end()
            ->tab('messages')
                ->panel('message_config', ['class' => 'gogo-feature'])
                    ->add('customPopupFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)
                    ->add('customPopupText', SimpleFormatterType::class, [
                            'format' => 'richhtml',
                            'label_attr' => ['style' => 'margin-top: 20px'],
                            'ckeditor_context' => 'full',
                    ])
                    ->add('customPopupId')
                    ->add('customPopupShowOnlyOnce')
                ->end()
            ->end()
        ;
    }
}
