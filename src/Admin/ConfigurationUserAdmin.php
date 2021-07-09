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

class ConfigurationUserAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_login_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-login';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $container = $this->getConfigurationPool()->getContainer();

        $formMapper
            ->add('user.enableRegistration', CheckboxType::class)
            ->add('user.sendConfirmationEmail', CheckboxType::class);

        // provide oauth id if configured
        if ('disabled' != $container->getParameter('oauth_communs_id')) {
            $formMapper->add('user.loginWithLesCommuns', CheckboxType::class);
            $formMapper->add('user.loginWithMonPrintemps', CheckboxType::class);
        }
        if ('disabled' != $container->getParameter('oauth_google_id')) {
            $formMapper->add('user.loginWithGoogle', CheckboxType::class);
        }
        if ('disabled' != $container->getParameter('oauth_facebook_id')) {
            $formMapper->add('user.loginWithFacebook', CheckboxType::class);
        }
    }
}
