<?php

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Helper\GoGoHelper;

class ConfigurationMenuAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_menu_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-menu';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $dm = GoGoHelper::getDmFromAdmin($this);
        $config = $dm->get('Configuration')->findConfiguration();
        $apiProperties = $dm->get('Element')->findAllCustomProperties();
        $propertiesText = implode($apiProperties, ',');

        $featureStyle = ['class' => 'col-md-6 col-lg-3 gogo-feature'];
        $featureFormOption = ['delete' => false, 'required' => false, 'label_attr' => ['style' => 'display:none']];
        $featureFormTypeOption = ['edit' => 'inline'];

        $formMapper
            ->tab('general')
                ->panel('menu')
                    ->add('menu.width', IntegerType::class)
                    ->add('menu.smallWidthStyle', CheckboxType::class)
                    ->add('menu.showOnePanePerMainOption', CheckboxType::class)
                    ->add('menu.showCheckboxForMainFilterPane', CheckboxType::class)
                    ->add('menu.showCheckboxForSubFilterPane', CheckboxType::class)
                    ->add('menu.displayNumberOfElementForEachCategory', CheckboxType::class)
                    ->add('menu.displayNumberOfElementRoundResults', CheckboxType::class)
                ->end()
                ->panel('custom')
                    ->add('menu.filtersJson', HiddenType::class, ['attr' => ['class' => 'gogo-filters-builder', 'dataproperties' => $propertiesText]])
                ->end()
            ->end()
            ->tab('search')
                ->panel('searchPlaceFeature', $featureStyle)
                    ->add('searchPlaceFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('searchGeolocateFeature', $featureStyle)
                    ->add('searchGeolocateFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('searchElementsFeature', $featureStyle)
                    ->add('searchElementsFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('searchCategoriesFeature', $featureStyle)
                    ->add('searchCategoriesFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('searchExcludingWords')
                    ->add('searchExcludingWords')
                ->end()
                ->panel('box')
                ->end()
            ->end()
        ;
    }
}
