<?php

namespace App\Application\Hwi\OAuthBundle\Controller;

use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Helper\GoGoHelper;

class ConnectController extends BaseController
{
    // this route is just about using the root instance domain, and then redirect to the subdomain
    // So we don't need to register each subdomain for the SSO
    public function gogoOAuthAction(Request $request, SessionInterface $session, $ressourcePath)
    {
        if ($this->getParameter('use_as_saas') == "true")
            $domainName = $session->get('domainName') . '.';
        else
            $domainName = '';
        $url = $this->getParameter('base_protocol').'://'.$domainName.$this->getParameter('base_url'). "/login/$ressourcePath?";
        foreach($request->query->all() as $key => $value)
            $url = $url . "$key=$value&";
        return $this->redirect($url);
    }

    // gogocarto.fr/connect/facebook
    public function gogoRedirectToServiceAction(Request $request, SessionInterface $session, $domainName, $service)
    {
        $session->set('domainName', $domainName);
        return $this->redirectToService($request, $service);
    }

    // subdomain.gogocarto.fr/connect/facebook
    public function redirectToServiceAction(Request $request, $service)
    {
        if ($this->container->getParameter('use_as_saas') == "true") {
            // redirect to root project
            $url = $request->getUri();
            preg_match_all('/^https?:\/\/([\w-]+)\./', $url, $result);
            $domainName = $result[1][0];
            $url = GoGoHelper::getRootProjectUrlFromInstanceUrl($url, "/root/$domainName");
            $this->saveRefererIntoSession($request);
            return $this->redirect($url);
        } else {
            $this->saveRefererIntoSession($request);
            return $this->redirectToService($request, $service);
        }        
    }

    protected function saveRefererIntoSession($request)
    {
        $session = $request->getSession();

        // Check for a return path and store it before redirect
        if (null !== $session) {
            // initialize the session for preventing SessionUnavailableException
            if (!$session->isStarted()) {
                $session->start();
            }

            foreach ($this->container->getParameter('hwi_oauth.firewall_names') as $providerKey) {
                $sessionKey = '_security.'.$providerKey.'.target_path';
                $sessionKeyFailure = '_security.'.$providerKey.'.failed_target_path';

                $param = $this->container->getParameter('hwi_oauth.target_path_parameter');
                if (!empty($param) && $targetUrl = $request->get($param)) {
                    $session->set($sessionKey, $targetUrl);
                }

                if ($this->container->getParameter('hwi_oauth.failed_use_referer') && !$session->has($sessionKeyFailure) && ($targetUrl = $request->headers->get('Referer')) && $targetUrl !== $authorizationUrl) {
                    $session->set($sessionKeyFailure, $targetUrl);
                }

                if ($this->container->getParameter('hwi_oauth.use_referer') && !$session->has($sessionKey) && ($targetUrl = $request->headers->get('Referer'))) {
                    $session->set($sessionKey, $targetUrl);
                }
            }
        }
    }

    // Redirect to the service sso url, for example : https://login.lescommuns.org/auth/realms/master/protocol/openid-connect/auth
    protected function redirectToService($request, $service)
    {
        try {
            $authorizationUrl = $this->container->get('hwi_oauth.security.oauth_utils')->getAuthorizationUrl($request, $service);
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }        

        return $this->redirect($authorizationUrl);
    }
}

