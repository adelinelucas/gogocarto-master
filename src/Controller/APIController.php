<?php

namespace App\Controller;

use App\Services\GoGoCartoJsService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Intervention\Image\ImageManagerStatic as InterventionImage;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class APIController extends GoGoController
{
    /* Retrieve elements via API, allow params are
    * @id
    * @limit
    * @excludeExternal -> exclude external sources in API
    * @bounds
    * @categories (ids)
    * @stamps (ids)
    * @ontology ( gogofull or gogocompact )
    **/
    public function getElementsAction(Request $request, $id = null, $_format = 'json', DocumentManager $dm)
    {
        $jsonLdRequest = $this->isJsonLdRequest($request, $_format);
        $ontology = $request->get('ontology') ? strtolower($request->get('ontology')) : 'gogofull';
        $fullRepresentation = $jsonLdRequest || 'gogocompact' != $ontology;
        $elementId = $id ? $id : $request->get('id');
        $config = $dm->get('Configuration')->findConfiguration();
        $isAdmin = $this->isUserAdmin();
        $elementRepo = $dm->get('Element');

        if ($elementId) {
            $element = $elementRepo->findOneBy(['id' => $elementId]);
            $elementsJson = $element ? $element->getJson($isAdmin) : null;
        } else {
            if ($request->get('bounds')) {
                $boxes = [];
                $bounds = explode(';', $request->get('bounds'));
                foreach ($bounds as $key => $bound) {
                    $boxes[] = explode(',', $bound);
                }
                $elementsFromDB = $elementRepo->findWhithinBoxes($boxes, $request, $fullRepresentation, $isAdmin);
            } else {
                $elementsFromDB = $elementRepo->findAllPublics($fullRepresentation, $isAdmin, $request);
            }
            $elementsJson = $this->encodeElementArrayToJsonArray($elementsFromDB, $fullRepresentation, $isAdmin);
        }

        $status = 200;
        if (!$elementsJson) {
            $elementsJson = '{ "error": "Element does not exists" }';
            $status = 500;
        } elseif ($jsonLdRequest) {
            $elementsJson = '{
                "@context" : "https://rawgit.com/jmvanel/rdf-convert/master/context-gogo.jsonld",
                "@graph"   :  '.$elementsJson.'
            }';
        } else {
            $elementsJson = '{
                "licence": "'.$config->getDataLicenseUrl().'",
                "ontology":"'.$ontology.'",
                "data":' . $elementsJson;

            if (!$fullRepresentation) {
                $mapping = ['id', array_keys($config->getCompactFields()), 'latitude', 'longitude', 'status', 'moderationState'];
                $elementsJson .= ', "mapping":'.json_encode($mapping);
            }

            $elementsJson .= '}';
        }

        // TODO count how much a user is using the API
        // $responseSize = strlen($elementsJson);

        return $this->createResponse($elementsJson, $config, $status);
    }

    public function getTaxonomyAction(Request $request, $id = null, $_format = 'json', DocumentManager $dm,
                                    SerializerInterface $serializer)
    {
        $optionId = $id ? $id : $request->get('id');
        $jsonLdRequest = $this->isJsonLdRequest($request, $_format);

        if ($optionId) {
            $option = $dm->get('Option')->findOneBy(['id' => $optionId]);
            $serializationContext = $jsonLdRequest ? SerializationContext::create()->setGroups(['semantic']) : null;
            $dataJson = $serializer->serialize($option, 'json', $serializationContext);
            if ($jsonLdRequest) {
                $dataJson = '['.$dataJson.']';
            }
        } else {
            $dataJson = $dm->get('Taxonomy')->findTaxonomyJson($jsonLdRequest);
        }

        if ($jsonLdRequest) {
            $responseJson = '{
                "@context" : "https://rawgit.com/jmvanel/rdf-convert/master/pdcn-taxonomy/taxonomy.context.jsonld",
                "@graph"   :  '.$dataJson.'
            }';
        } else {
            $responseJson = $dataJson;
        }

        $config = $dm->get('Configuration')->findConfiguration();

        return $this->createResponse($responseJson, $config);
    }

    public function getTaxonomyMappingAction(DocumentManager $dm, SerializerInterface $serializer)
    {
        $options = $dm->get('Option')->findAll();
        $result = [];
        foreach ($options as $key => $option) {
            $result[$option->getId()] = $option;
        }

        $responseJson = $serializer->serialize($result, 'json');

        $config = $dm->get('Configuration')->findConfiguration();

        return $this->createResponse($responseJson, $config);
    }

    private function isJsonLdRequest($request, $_format)
    {
        return 'jsonld' == $_format || 'application/ld+json' == $request->headers->get('Accept');
    }

    private function createResponse($text, $config, $status = 200)
    {
        $response = new Response($text, $status);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getElementsFromTextAction(Request $request, DocumentManager $dm)
    {
        $isAdmin = $this->isUserAdmin();

        $elements = $dm->get('Element')->findElementsWithText($request->get('text'), true, $isAdmin);

        $elementsJson = $this->encodeElementArrayToJsonArray($elements, true, $isAdmin, true);
        $responseJson = '{ "data":'.$elementsJson.', "ontology" : "gogofull"}';

        $config = $dm->get('Configuration')->findConfiguration();

        return $this->createResponse($responseJson, $config);
    }

    /* Use is elements field (linking elements betwwen each others) */
    public function getElementNamesFromTextAction(Request $request, DocumentManager $dm)
    {
        $elements = $dm->get('Element')->findElementNamesWithText($request->get('text'), $request->get('excludeId'));

        $responseJson = json_encode($elements);

        $config = $dm->get('Configuration')->findConfiguration();

        return $this->createResponse($responseJson, $config);
    }

    private function isUserAdmin()
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->getUser();
            $isAdmin = $user && $user->isAdmin();

            return $isAdmin;
        }

        return false;
    }

    private function encodeElementArrayToJsonArray($array, $fullRepresentation, $isAdmin = false)
    {
        if (count($array) == 0) return '[]';
        $elementsJson = '[';
        foreach ($array as &$value) {
            if ('true' == $fullRepresentation) {
                $elementJson = '{';
                if (isset($value['score'])) {
                    $elementJson .= '"searchScore" : '.$value['score'].',';
                }
                $elementJson .= $value['baseJson'];
                if ($isAdmin && '' != $value['adminJson']) {
                    $elementJson .= ',' . $value['adminJson'];
                } 
                $elementJson .= '}';               
            } else {
                $elementJson = $value['compactJson'];
            }
            $elementsJson .= $elementJson.',';
        }

        $elementsJson = substr($elementsJson, 0, -1) . ']'; // remove last comma

        return $elementsJson;
    }

    public function getGoGoCartoJsConfigurationAction(DocumentManager $dm, GoGoCartoJsService $gogoJsService)
    {
        $config = $dm->get('Configuration')->findConfiguration();
        $gogoJsService->setUrlType(UrlGeneratorInterface::ABSOLUTE_URL);
        $gogocartoConf = $gogoJsService->getConfig();

        return $this->createResponse(json_encode($gogocartoConf), $config);
    }

    public function apiUiAction(DocumentManager $dm)
    {
        $config = $dm->get('Configuration')->findConfiguration();
        $options = $dm->get('Option')->findAll();

        return $this->render('api/api-ui.html.twig', ['options' => $options, 'config' => $config]);
    }

    public function getManifestAction(Request $request, DocumentManager $dm)
    {
        $config = $dm->get('Configuration')->findConfiguration();
        if (!$config) return new Response(json_encode(['error' => "No configuration found"]));
        
        $img = $config->getFavicon() ? $config->getFavicon() : $config->getLogo();
        $imageData = null;

        if ($img) {
            $imgUrl = $img->getImageUrl('512x512', 'png');
            try {
                if (!$img->isExternalFile()) {
                    $imageData = InterventionImage::make($img->calculateFilePath('512x512', 'png'));
                } else {
                    $imageData = InterventionImage::make($imgUrl);
                }
            } catch (\Exception $error) {
            }
        }
        if (!$imageData) {
            $imgUrl = $request->getUriForPath('/img/gogo-bot.png');
            if ('dev' == $this->getParameter('kernel.environment')) {
                $imgUrl = str_replace('index.php/', '', $imgUrl);
            }
            try {
                $imageData = InterventionImage::make($imgUrl);
            } catch (\Exception $error) {
            }
        }

        $icon = ['src' => $imgUrl];
        if ($imageData) {
            $icon['sizes'] = $imageData->height().'x'.$imageData->width();
            $icon['mime'] = $imageData->mime();
        }
        $shortName = $config->getAppNameShort() && strlen($config->getAppNameShort()) > 0 ? 
                     mb_substr($config->getAppNameShort(), 0, 12) : 
                     mb_substr($config->getAppName(), 0, 11) . '.';
        $responseArray = [
          'name' => $config->getAppName(),
          'short_name' => $shortName,
          'lang' => 'fr',
          'start_url' => $this->generateUrl('gogo_app_shell') . '#/carte/autour-de-moi',
          'display' => 'standalone',
          'theme_color' => $config->getPrimaryColor(),
          'background_color' => $config->getBackgroundColor(),
          'icons' => [$icon],
        ];
        $responseJson = json_encode($responseArray);
        if (!is_string($responseJson)) {
            throw new \Exception("Cannot convert responseArray to json : " . var_dump($responseArray));
        }
        $response = new Response($responseJson);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getProjectInfoAction(DocumentManager $dm)
    {
        $config = $dm->get('Configuration')->findConfiguration();
        $img = $config->getSocialShareImage() ? $config->getSocialShareImage() : $config->getLogo();
        $imageUrl = $img ? $img->getImageUrl() : null;
        $dataSize = $dm->get('Element')->findVisibles(true);

        $responseArray = [
          'name' => $config->getAppName(),
          'imageUrl' => $imageUrl,
          'description' => $config->getAppBaseline(),
          'tags' => $config->getAppTags(),
          'dataSize' => $dataSize
        ];
        $response = new Response(json_encode($responseArray));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getConfigurationAction(DocumentManager $dm)
    {
        $config = $dm->get('Configuration')->findConfiguration();
        $defaultTileLayer = $config->getDefaultTileLayer()->getName();
        $config = json_decode(json_encode($config));

        $tileLayers = $dm->get('TileLayer')->findAll();

        $config->defaultTileLayer = $defaultTileLayer;
        $config->tileLayers = $tileLayers;
        $response = new Response(json_encode($config));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function hideLogAction($id, DocumentManager $dm)
    {
        $log = $dm->get('GoGoLog')->find($id);
        $log->setHidden(true);
        $dm->flush();
        $response = new Response(json_encode(['success' => true]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function hideAllLogsAction(DocumentManager $dm)
    {
        $qb = $dm->query('GoGoLog');
        $qb->updateMany()
            ->field('type')->notEqual('update')
            ->field('hidden')->equals(false)
            ->field('hidden')->set(true)->execute();

        return $this->redirectToRoute('sonata_admin_dashboard');
    }

    public function hideAllMessagesAction(DocumentManager $dm)
    {
        $qb = $dm->query('GoGoLogUpdate');
        $qb->updateMany()
            ->field('type')->equals('update')
            ->field('hidden')->equals(false)
            ->field('hidden')->set(true)->execute();

        return $this->redirectToRoute('sonata_admin_dashboard');
    }
}
