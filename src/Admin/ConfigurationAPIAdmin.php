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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Helper\GoGoHelper;

class ConfigurationAPIAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_api_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-api';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $dm = GoGoHelper::getDmFromAdmin($this);
        $apiProperties = $dm->get('Element')->findAllCustomProperties();

        $apiPropertiesChanged = [];
        foreach ($apiProperties as $key => $value) {
            $apiPropertiesChanged[$value] = $value;
        }

        $formMapper
            ->panel('config')
                ->add('api.publicApiPrivateProperties', ChoiceType::class, ['choices' => $apiPropertiesChanged, 'multiple' => true])
            ->end()
            ->panel('apis')
                ->add('apilist', TextType::class, ['mapped' => false, 'attr' => ['class' => 'gogo-api-list']])
            ->end()
        ;
    }
}
