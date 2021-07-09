<?php

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class ConfigurationAdmin extends ConfigurationAbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $imagesOptions = [
            'class' => 'App\Document\ConfImage',
            'placeholder' => $this->trans('images.placeholder'),
            'mapped' => true,
        ];

        $container = $this->getConfigurationPool()->getContainer();

        $formMapper
            ->halfPanel('main');
        if ($container->getParameter('use_as_saas')) {
            $formMapper
                ->add('publishOnSaasPage', null, ['label_trans_params' => ['url' => $container->getParameter('base_url')]]);
        }
        $formMapper
                ->add('appName')
                ->add('appBaseline')
                ->add('appTags')
                ->add('locale', ChoiceType::class, ['choices' => [
                    'Français' => 'fr', 
                    'English' => 'en',
                    'Deutsche' => 'de'
                ]])
                ->add('customDomain', UrlType::class, ['help_trans_params' => ['ip' => $_SERVER['SERVER_ADDR']]])
                ->add('dataLicenseUrl')
            ->end()
            ->halfPanel('images')
                ->add('logo', ModelType::class, $imagesOptions)
                ->add('logoInline', ModelType::class, $imagesOptions)
                ->add('socialShareImage', ModelType::class, $imagesOptions)
                ->add('favicon', ModelType::class, $imagesOptions)
            ->end()
            ->halfPanel('pages')
                ->add('activateHomePage')
                ->add('activatePartnersPage')
                ->add('partnerPageTitle')
                ->add('activateAbouts')
                ->add('aboutHeaderTitle')
            ->end()
            ->halfPanel('text')
                ->add('elementDisplayName')
                ->add('elementDisplayNameDefinite')
                ->add('elementDisplayNameIndefinite')
                ->add('elementDisplayNamePlural')
            ->end()
        ;
    }
}
