<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-03-29 09:25:31
 */

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use App\Helper\GoGoHelper;

class ConfigurationMailAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_mail_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-mail';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $dm = GoGoHelper::getDmFromAdmin($this);
        $repo = $dm->get('Configuration');
        $config = $repo->findConfiguration();
        $router = $this->getConfigurationPool()->getContainer()->get('router');

        $featureStyle = ['class' => 'col-md-6 col-lg-3'];
        $contributionStyle = ['class' => 'col-md-6 col-lg-4'];
        // $mailStyle = ['class' => 'col-md-12 col-lg-6'];
        $featureFormOption = ['delete' => false, 'label_attr' => ['style' => 'display:none']];
        $featureFormTypeOption = ['edit' => 'inline'];
        $formMapper
            ->tab('mailForElements', ['label_trans_params' => ['%element%' => $config->getElementDisplayNamePlural()]])
                ->panel('infosElements', ['box_class' => 'box box-danger','label_trans_params' => [
                        '%elements%' => $config->getElementDisplayNamePlural(),
                        '%element%' => $config->getElementDisplayNameDefinite()
                ]])->end()
                ->panel('add', ['class' => 'col-md-12 col-lg-6', 'label_trans_params' => ['%url%' => $this->getEmailTestLink($router, 'add')]])
                    ->add('addMail', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('edit', ['class' => 'col-md-12 col-lg-6', 'label_trans_params' => ['%url%' => $this->getEmailTestLink($router, 'edit')]])
                    ->add('editMail', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('delete', ['class' => 'col-md-12 col-lg-6', 'label_trans_params' => ['%url%' => $this->getEmailTestLink($router, 'delete')]])
                    ->add('deleteMail', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->end()
            ->tab('mailForContributors')
                ->panel('infosContributors', ['box_class' => 'box box-danger', 'label_trans_params' => [
                        '%element%' => $config->getElementDisplayNameDefinite()
                ]])->end()
                ->panel('validation', ['class' => 'col-md-12 col-lg-6', 'label_trans_params' => ['%url%' => $this->getEmailTestLink($router, 'validation')]])
                    ->add('validationMail', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('refusal', ['class' => 'col-md-12 col-lg-6', 'label_trans_params' => ['%url%' => $this->getEmailTestLink($router, 'refusal')]])
                    ->add('refusalMail', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
                ->panel('report', ['class' => 'col-md-12 col-lg-6', 'label_trans_params' => ['%url%' => $this->getEmailTestLink($router, 'report')]])
                    ->add('reportResolvedMail', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->end()
            ->tab('newsletters')
                ->panel('infosNewletter', ['box_class' => 'box box-danger'])->end()
                ->panel('newsletter', ['label_trans_params' => ['%url%' => $this->getEmailTestLink($router, 'newsletter')]])
                    ->add('newsletterMail', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->end()
        ;
    }

    private function getEmailTestLink($router, $mailType)
    {
        $url = $router->generate('gogo_mail_draft_automated', ['mailType' => $mailType]);

        return $url;
    }
}
