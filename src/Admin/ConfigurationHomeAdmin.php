<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-04-22 19:45:15
 */

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ConfigurationHomeAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_home_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-home';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $imagesOptions = [
            'class' => 'App\Document\ConfImage',
            'mapped' => true,
        ];

        $featureStyle = ['class' => 'col-md-6 col-lg-3'];
        $contributionStyle = ['class' => 'col-md-6 col-lg-4'];
        $mailStyle = ['class' => 'col-md-12 col-lg-6'];
        $featureFormOption = ['delete' => false, 'required' => false, 'label_attr' => ['style' => 'display:none']];
        $featureFormTypeOption = ['edit' => 'inline'];
        $formMapper
            ->add('activateHomePage')
            ->add('backgroundImage', ModelType::class, $imagesOptions)
            ->add('home.displayCategoriesToPick', CheckboxType::class)
            ->add('home.addElementHintText')
            ->add('home.seeMoreButtonText')
        ;
    }
}
