<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-04-22 19:45:15
 */

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ConfigurationStyleAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_style_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-style';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->panel('theme', ['class' => 'col-md-6'])
                ->add('theme', ChoiceType::class, ['choices' => [$this->trans('commons.default') => 'default', 'Flat' => 'flat', 'Près de chez Nous' => 'presdecheznous', 'Transiscope' => 'transiscope']])
                ->add('mainFont', null, ['attr' => ['class' => 'gogo-font-picker']])
                ->add('titleFont', null, ['attr' => ['class' => 'gogo-font-picker']])

            ->end()
            ->panel('primaryColors', ['class' => 'col-md-6'])
                ->add('textColor', null, ['attr' => ['class' => 'gogo-color-picker'], 'required' => true])
                ->add('primaryColor', null, ['attr' => ['class' => 'gogo-color-picker'], 'required' => true])
                ->add('backgroundColor', null, ['attr' => ['class' => 'gogo-color-picker']])
            ->end()
            ->panel('fontImport')
                ->add('fontImport')
                ->add('iconImport')
            ->end()
            ->panel('secondaryColor', ['class' => 'col-md-6'])
                ->add('secondaryColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('headerColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('headerTextColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('headerHoverColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('contentBackgroundColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('contentBackgroundElementBodyColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('homeBackgroundColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('searchBarColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('interactiveSectionColor', null, ['attr' => ['class' => 'gogo-color-picker']])
            ->end()
            ->panel('advancedColor', ['class' => 'col-md-6'])
                ->add('textDarkColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('textDarkSoftColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('textLightColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('textLightSoftColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('errorColor', null, ['label' => "", 'attr' => ['class' => 'gogo-color-picker']])
                ->add('disableColor', null, ['attr' => ['class' => 'gogo-color-picker']])
                ->add('pendingColor', null, ['attr' => ['class' => 'gogo-color-picker']])
            ->end()     
            ;
    }
}
