<?php

namespace App\Controller;

use App\Services\GoGoCartoJsService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;

class DirectoryController extends GoGoController
{
    public function renderAction(Request $request, GoGoCartoJsService $gogoJsService, $elementId = null)
    {
        $gogoConfig = $gogoJsService->getConfig($elementId);

        return $this->render('directory/directory.html.twig', ['gogoConfig' => $gogoConfig]);
    }

    public function appShell(Request $request, DocumentManager $dm)
    {
        $config = $dm->get('Configuration')->findConfiguration();
        
        $params = ['gogoConfigUrl' => $this->generateUrl('gogo_api_gogocartojs_configuration')];
        $params['hideHeader'] = $config ? $config->getHideHeaderInPwa() : false;

        return $this->render('directory/directory.html.twig', $params);
    }
}
