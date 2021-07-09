<?php

namespace App\Application\Hwi\OAuthBundle\Security;

use HWI\Bundle\OAuthBundle\Security\OAuthUtils as Base;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Helper\GoGoHelper;

class OAuthUtils extends Base
{
   
    public function getAuthorizationUrl(Request $request, $name, $redirectUrl = null, array $extraParameters = array())
    {
        $resourceOwner = $this->getResourceOwner($name);
        if (null === $redirectUrl) {
            if (!$this->connect || !$this->authorizationChecker->isGranted($this->grantRule)) {
                $path = $this->getResourceOwnerCheckPath($name);
                
                $redirectUrl = $this->httpUtils->generateUri($request, $path);
            } else {
                $redirectUrl = $this->getServiceAuthUrl($request, $resourceOwner);
            }
        }
        $redirectUrl = GoGoHelper::getRootProjectUrlFromInstanceUrl($redirectUrl);
        return $resourceOwner->getAuthorizationUrl($redirectUrl, $extraParameters);
    }

    public function getServiceAuthUrl(Request $request, ResourceOwnerInterface $resourceOwner)
    {
        if ($resourceOwner->getOption('auth_with_one_url')) {
            return $this->httpUtils->generateUri($request, $this->getResourceOwnerCheckPath($resourceOwner->getName()));
        }

        $request->attributes->set('service', $resourceOwner->getName());

        return $this->httpUtils->generateUri($request, 'hwi_oauth_connect_service');
    }
}