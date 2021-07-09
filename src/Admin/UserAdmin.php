<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Admin;

use FOS\UserBundle\Model\UserManagerInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\UserBundle\Form\Type\SecurityRolesType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserAdmin extends GoGoAbstractAdmin
{
    /**
     * @var UserManagerInterface
     */
    protected $userManager;

    protected $userContribRepo;

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('username')
            ->add('email')
            ->add('groups')
            ->add('gamification')
            ->add('contributionsCount')
            ->add('votesCount')
            ->add('reportsCount')
            ->add('createdAt', 'date', ['format' => $this->trans('commons.date_format')])
        ;

        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $listMapper
                ->add('impersonating', 'string', ['template' => 'SonataUserBundle:Admin:Field/impersonating.html.twig'])
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFormBuilder()
    {
        $this->formOptions['data_class'] = $this->getClass();

        $options = $this->formOptions;
        $options['validation_groups'] = (!$this->getSubject() || is_null($this->getSubject()->getId())) ? 'Registration' : 'Profile';

        $formBuilder = $this->getFormContractor()->getFormBuilder($this->getUniqid(), $options);

        $this->defineFormBuilder($formBuilder);

        return $formBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getExportFields()
    {
        // avoid security field to be exported
        return array_filter(parent::getExportFields(), function ($v) {
            return !in_array($v, ['password', 'salt']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate($user)
    {
        $this->getUserManager()->updateCanonicalFields($user);
        $this->getUserManager()->updatePassword($user);
    }

    public function setUserManager(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @return UserManagerInterface
     */
    public function getUserManager()
    {
        return $this->userManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('id')
            ->add('username')
            ->add('newsletterFrequency', 'doctrine_mongo_callback', [
                'field_type' => CheckboxType::class,
                'callback' => function ($queryBuilder, $alias, $field, $value) {
                    if (!$value || !$value['value']) {
                        return;
                    }
                    $queryBuilder->field('newsletterFrequency')->gt(0);

                    return true;
                }, ])
            ->add('email')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->tab('user')
                ->halfPanel('general')
                    ->add('username')
                    ->add('email')
                    ->add('plainPassword', PasswordType::class, [
                        'required' => (!$this->getSubject() || is_null($this->getSubject()->getId())),
                    ])
                    ->add('allowedStamps', ModelType::class, [
                        'required' => false,
                        'expanded' => false,
                        'multiple' => true,
                    ])
                ->end()
                ->halfPanel('status')
                    ->add('locked', CheckboxType::class)
                    ->add('expired', CheckboxType::class)
                    ->add('enabled', CheckboxType::class)
                    ->add('credentialsExpired', CheckboxType::class)
                ->end()
                ->halfPanel('groups')
                    ->add('groups', ModelType::class, [
                        'expanded' => true,
                        'multiple' => true,
                    ])
                ->end()
                ->panel('notifications')
                    ->add('watchModeration')
                    ->add('watchModerationOnlyWithOptions', ModelType::class, [
                        'class' => 'App\Document\Option',
                        'multiple' => true,
                        'btn_add' => false,
                        ], ['admin_code' => 'admin.options'])
                    ->add('watchModerationOnlyWithPostCodes')
                ->end()
            ->end()
            ->tab('security')
                ->panel('roles')
                    ->add('realRoles', SecurityRolesType::class, [
                        'label' => false,
                        'expanded' => true,
                        'multiple' => true,
                        'required' => false,
                    ])
                ->end()
            ->end()
        ;
    }

    public function getTemplate($name)
    {
        switch ($name) {
         case 'list': return 'admin/list/list_user.html.twig';
             break;
         default: return parent::getTemplate($name);
             break;
     }
    }

    public function configureBatchActions($actions)
    {
        // $actions = parent::configureBatchActions($actions);
        $actions = [];

        $actions['sendMail'] = [
         'ask_confirmation' => false,
         'modal' => [
            ['type' => 'text',      'label' => $this->t('sonata.user.user.fields.email'),  'id' => 'from'],
            ['type' => 'text',      'label' => $this->t('sonata.user.user.fields.object'),  'id' => 'mail-subject'],
            ['type' => 'textarea',  'label' => $this->t('sonata.user.user.fields.content'), 'id' => 'mail-content'],
         ],
      ];

        return $actions;
    }
}
