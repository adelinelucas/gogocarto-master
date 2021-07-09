<?php

namespace App\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Routing\RouterInterface;
use App\Document\Project;
use App\Document\Configuration;

class UrlService
{
    public function __construct(DocumentManager $dm, RouterInterface $router,
                               $baseProtocol, $baseUrl, $useAsSaas)
    {
        $this->dm = $dm;
        $this->router = $router;
        $this->baseProtocol = $baseProtocol;
        $this->baseUrl = $baseUrl;
        $this->useAsSaas = $useAsSaas;
    } 
    
    // Url for current used project
    public function generateUrl($route = 'gogo_homepage', $params = [])
    {
        $config = $this->dm->get('Configuration')->findConfiguration();
        return $this->generateUrlFor($config, $route, $params);
    }

    // Url for a specific project / subdomain
    public function generateUrlFor($domainName, $route = 'gogo_homepage', $params = [])
    {
        $url = '';
        $config = null;

        if ($domainName instanceof Project)
            $domainName = $domainName->getDomainName();
        elseif($domainName instanceof Configuration) {
            $config = $domainName;
            $domainName = $domainName->getDbName();
        }     
        // When using from a command (without REQUEST) we want to use the customDomain (for emails for example)
        if ($config && $config->getCustomDomain() && empty($_REQUEST)) {
            $url .= rtrim($config->getCustomDomain(), '/');
        } else {
            $url .= "$this->baseProtocol://";
            if ($this->useAsSaas && $domainName) $url .= "$domainName.";
            $url .= $this->baseUrl;
        }
        $url .= $this->router->generate($route, $params);
        $url = str_replace('index.php/index.php', 'index.php', $url); // Fix if there is no url rewrite
        return $url;
    }

    public function elementShowUrl($elementId)
    {
        $url = $this->generateUrl('gogo_directory_showElement', ['id' => $elementId]);
        return str_replace('%23', '#', $url);
    }

    public function generateRootUrl($route = 'gogo_saas_home', $params = [])
    {
        return $this->generateUrlFor(null, $route, $params);
    }

    public function getAssetUrl($path)
    {
        $url = "$this->baseProtocol://$this->baseUrl" . $path;
        return str_replace('index.php/', '', $path); // Fix if there is no url rewrite
    }
}