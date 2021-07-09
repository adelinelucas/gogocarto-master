<?php

namespace App\Helper;

use App\Document\Configuration;

class GoGoHelper
{
    // Extract the name of the Element fields used in a nunjuck template
    static function extractFieldsUsedInTemplate($string) {
        $matches = [];
        preg_match_all('/{{\s*([\w_]*)\s*|[^{}]*}}/', $string, $matches);
        return array_unique(array_filter(array_map(function ($fieldName) {
            if ('image' == $fieldName) {
                $fieldName = 'images';
            }
            return $fieldName;
        }, $matches[1])));
    }

    // Transform mymap.gogocarto.fr/some-route to gogocarto.fr/root/some-route
    // It's used to achieve SSO in SAAS mode, so we only need to regiter the main domain
    // for the SSO. all subdomain will go throught the main domain for SSO request and then will
    // redirected again to the subdomain when authorization is granted
    static function getRootProjectUrlFromInstanceUrl($url, $prefix = '/root') {
        $path = explode($_ENV['BASE_URL'], $url)[1];
        $url = $_ENV['BASE_PROTOCOL'] . '://' . $_ENV['BASE_URL'] . $prefix . $path;
        return $url;
    }

    static function getDmFromAdmin($adminInstance)
    {
        return $adminInstance->getModelManager()->getDocumentManager('App\Document\Configuration');
    }
}