<?php

namespace App\Application\Sonata\UserBundle\Security;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class CustomRoleSecurityHandler implements SecurityHandlerInterface
{
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->superAdminRoles = ['ROLE_SUPER_ADMIN'];
    }

    public function isGranted(AdminInterface $admin, $attributes, $object = null)
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        $adminCode = $admin->getCode();
        // use same permission for all option/categories objects
        if (in_array($adminCode, ['admin.options', 'admin.option.lite_hidden', 'admin.categories.lite_hidden'])) {
            $adminCode = 'admin.categories';
        }

        // give access to all hidden objects, like the images objects
        if (strpos($adminCode, 'hidden')) {
            return true;
        }

        foreach ($attributes as $pos => $attribute) {
            $attributes[$pos] = sprintf($this->getBaseRole($admin, $adminCode), $attribute);
        }

        $allRole = sprintf($this->getBaseRole($admin, $adminCode), 'ALL');

        try {
            return $this->authorizationChecker->isGranted($this->superAdminRoles)
                || $this->authorizationChecker->isGranted($attributes, $object)
                || $this->authorizationChecker->isGranted([$allRole], $object);
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseRole(AdminInterface $admin, $adminCode = null)
    {
        if (!$adminCode) {
            $adminCode = $admin->getCode();
        }

        return 'ROLE_'.str_replace('.', '_', strtoupper($adminCode)).'_%s';
    }

    /**
     * {@inheritdoc}
     */
    public function buildSecurityInformation(AdminInterface $admin)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function createObjectSecurity(AdminInterface $admin, $object)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function deleteObjectSecurity(AdminInterface $admin, $object)
    {
    }
}
