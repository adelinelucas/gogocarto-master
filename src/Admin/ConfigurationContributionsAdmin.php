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
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;

class ConfigurationContributionsAdmin extends ConfigurationAbstractAdmin
{
    protected $baseRouteName = 'gogo_core_bundle_config_contributions_admin_classname';

    protected $baseRoutePattern = 'gogo/core/configuration-contributions';

    protected function configureFormFields(FormMapper $formMapper)
    {
        $contributionStyle = ['class' => 'col-md-6 col-lg-4 gogo-feature'];
        $featureFormOption = ['delete' => false, 'label_attr' => ['style' => 'display:none']];
        $featureFormTypeOption = ['edit' => 'inline'];

        $formMapper
            ->panel('addFeature', $contributionStyle)
                ->add('addFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->panel('editFeature', $contributionStyle)
                ->add('editFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->panel('deleteFeature', $contributionStyle)
                ->add('deleteFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->panel('directModerationFeature', $contributionStyle)
                ->add('directModerationFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->panel('collaborativeModerationFeature', ['class' => 'col-md-6 col-lg-4 gogo-feature collaborative-feature'])
                ->add('collaborativeModerationFeature', AdminType::class, $featureFormOption, $featureFormTypeOption)->end()
            ->panel('collaborativeModerationParameters', ['class' => 'col-md-4 collaborative-moderation-box'])
                ->add('minVoteToChangeStatus')
                ->add('maxOppositeVoteTolerated')
                ->add('minDayBetweenContributionAndCollaborativeValidation')
                ->add('maxDaysLeavingAnElementPending')
                ->add('minVoteToForceChangeStatus')
            ->end()
            ->panel('text')
                ->add('collaborativeModerationExplanations', SimpleFormatterType::class, [
                        'format' => 'richhtml',
                        'ckeditor_context' => 'full',
                ])
            ->end()
        ;
    }
}
