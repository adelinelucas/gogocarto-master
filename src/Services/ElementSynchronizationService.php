<?php

namespace App\Services;

use App\Document\Element;
use App\Document\Import;
use App\Document\UserInteractionContribution;
use GuzzleHttp\Promise\Promise;
use Services_OpenStreetMap;
use GuzzleHttp\Psr7\Response;
use Doctrine\ODM\MongoDB\DocumentManager;

class ElementSynchronizationService
{
    protected $config;

    const MAIN_OSM_KEYS = ['highway', 'natural', 'landuse', 'power', 'waterway', 'amenity', 'barrier', 'place', 'leisure', 'railway', 'shop', 'man_made', 'public_transport', 'tourism', 'boundary', 'emergency', 'historic', 'type', 'traffic_sign', 'office', 'traffic_calming', 'aeroway', 'healthcare', 'aerialway', 'craft', 'geological', 'military', 'telecom'];
    const MAIN_OSM_KEYS_FALLBACK = ['addr:housenumber', 'entrance', 'information', 'indoor', 'building']; // To check only if no main OSM tags has been found, as they can be used as descriptive tags and not only main tags
    const EARTH_RADIUS = 6378;
    const OSM_SEARCH_RADIUS_METERS = 50;

    public function __construct(DocumentManager $dm, UrlService $urlService, $appVersion)
    {
        $this->dm = $dm;
        $this->urlService = $urlService;
        $this->appVersion = $appVersion;
    }

    public function getConfig()
    {
        if (!$this->config) $this->config = $this->dm->get('Configuration')->findConfiguration();
        return $this->config;
    }

    /*
        Dispatch the contribution on the original database
        Log in into OSM
        Commit the change...
    */
    public function asyncDispatch(UserInteractionContribution $contribution, $preparedData) : Promise
    {
        // Wrap the whole function into a promise to make it asynchronous
        $promise = new Promise(function () use (&$promise, &$contribution, &$preparedData) {
            try {
                // Init OSM API handler
                $osm = $this->getOsmApiHandler();
                $element = $contribution->getElement();
                $gogoFeature = $this->elementToOsm($element);
                $gogoFeaturesMainTags = $this->getMainTags($gogoFeature);
                if (count($gogoFeaturesMainTags) == 0) {
                    return $promise->resolve(new Response(500, [], null, '1.1', $this->t('config_osm.sync.notags', [], 'admin') ));
                }

                // Check contribution validity according to OSM criterias
                if ($this->allowOsmUpload($contribution, $preparedData)) {
                    $toAdd = null;

                    // Process contribution
                    // New feature
                    if ($preparedData['action'] == 'add') {
                        $toAdd = $osm->createNode($gogoFeature['center']['latitude'], $gogoFeature['center']['longitude'], $gogoFeature['tags']);
                    }
                    // Edit existing feature
                    else if ($preparedData['action'] == 'edit') {
                        $osmFeature = null;
                        $getType = "get".ucfirst($gogoFeature['type']);
                        $osmFeature = $osm->$getType($gogoFeature['osmId']);

                        if ($osmFeature) {
                            // Check version number (to make sure Gogocarto version is the latest)
                            if ($osmFeature->getVersion() == intval($gogoFeature['version'])) {
                                if ($this->editOsmFeatureWithGoGoFeature($osmFeature, $gogoFeature))
                                    $toAdd = $osmFeature;
                                else
                                    return $promise->resolve(new Response(200, [], null, '1.1', ''));
                            }
                            else {
                                $message = $this->t('config_osm.sync.version_mismatch', [ 
                                    'local_version' => $gogoFeature['version'],
                                    'remote_version' => $osmFeature->getVersion()
                                    ], 'admin');
                                return $promise->resolve(new Response(500, [], null, '1.1', $message));
                            }
                        }
                        else {
                            $message = $this->t('config_osm.sync.no_feature', [], 'admin');
                            return $promise->resolve(new Response(404, [], null, '1.1', $message));
                        }
                    }

                    // Create changeset and upload changes
                    if (isset($toAdd)) {
                        $changeset = $this->createsChangeSet($osm, $toAdd, $this->getOsmComment($preparedData));

                        // Close changeset
                        try {
                            $changeset->commit();

                            // Update version in case of feature edit
                            $toUpdateInDb = null;

                            if ($preparedData['action'] == 'add') {
                                $toUpdateInDb = $osm->getNode($toAdd->getId());
                            }
                            else if ($preparedData['action'] == 'edit') {
                                $toUpdateInDb = $osm->$getType($gogoFeature['osmId']);
                            }

                            if ($toUpdateInDb) {
                                if ($preparedData['action'] == 'add') {
                                    $element->setCustomProperty('osm_type', 'node');
                                    $element->setOldId($toUpdateInDb->getId());
                                }
                                $element->setCustomProperty('osm_url', $element->getOsmUrl($this->config));
                                $element->setCustomProperty('osm_version', $toUpdateInDb->getVersion());
                                $element->setCustomProperty('osm_timestamp', strval($toUpdateInDb->getAttributes()->timestamp));
                                $this->dm->persist($element);
                                $this->dm->flush();
                            }

                            return $promise->resolve(new Response(200, [], null, '1.1', 'Success'));
                        }
                        catch(\Exception $e) {
                            $message = 'Error when sending changeset';
                            return $promise->resolve(new Response($e->getCode(), [], null, '1.1', $message));
                        }
                    }
                }
                // If we don't send edit to OSM, just resolve promise
                else {
                    return $promise->resolve(new Response(200, [], null, '1.1', ''));
                }
            }
            catch(\Exception $e) {
                return $promise->resolve(new Response($e->getCode(), [], null, '1.1', $e->getMessage()));
            }
        });

        return $promise;
    }

    private function editOsmFeatureWithGoGoFeature($osmFeature, $gogoFeature)
    {
        // Avoid empty commit : in gogocarto the update might be on field that are not sent to OSM
        $isNewFeatureDifferentFromOldOne = false;

        // Edit tags
        $osmTags = $osmFeature->getTags();
        foreach($gogoFeature['tags']  as $tagKey => $gogoTagValue) {
            if (isset($osmTags[$tagKey]) && $osmTags[$tagKey] != $gogoTagValue) $isNewFeatureDifferentFromOldOne = true;
        }

        foreach($gogoFeature['tags'] as $k => $v) {
            if ($v == null || $v == '') {
                $osmFeature->removeTag($k);
            }
            else {
                $osmFeature->setTag($k, $v);
            }
        }

        // If node coordinates are edited, check if it is detached
        if ($gogoFeature['type'] == 'node' && (!$osmFeature->getWays()->valid() || $osmFeature->getWays()->count() == 0)) {
            if ($gogoFeature['center']['latitude'] != $osmFeature->getLat()) {
                $osmFeature->setLat($gogoFeature['center']['latitude']);
                $isNewFeatureDifferentFromOldOne = true;
            }
            if ($gogoFeature['center']['longitude'] != $osmFeature->getLon()) {
                $osmFeature->setLon($gogoFeature['center']['longitude']);
                $isNewFeatureDifferentFromOldOne = true;
            }
        }
        
        return $isNewFeatureDifferentFromOldOne;
    }

    private function createsChangeSet($osm, $feature, $comment)
    {
        $changeset = $osm->createChangeset();
        $changeset->setId(-1); // To prevent bug with setTag
        $changeset->setTag('host', $this->urlService->generateUrl());
        $changeset->setTag('created_by', "GoGoCarto"); // not including version for now to keep it simple, but in future we cna change that : " v" . $this->appVersion . ' - ' . $this->getConfig()->getAppName());
        $changeset->begin($comment);

        // Add edited feature to changeset
        $changeset->add($feature);

        return $changeset;
    }

    /**
     * Convert an element into a JSON-like OSM feature
     */
    public function elementToOsm(Element $element)
    {
        if (!$element->isSynchedWithExternalDatabase()) return null;
        $gogoFeature = [];

        // Get original mappings
        $ontology = $element->getSource()->getOntologyMapping();
        $taxonomy = $element->getSource()->getTaxonomyMapping();

        // Type
        $gogoFeature['type'] = $element->getProperty('osm_type');

        // Coordinates
        $gogoFeature['center']['latitude'] = $element->getGeo()->getLatitude();
        $gogoFeature['center']['longitude'] = $element->getGeo()->getLongitude();

        // Categories
        foreach($taxonomy as $taxonomyKey => $taxonomyValue) {
            foreach($ontology as $ontologyKey => $ontologyValue) {
                $ontologySubkeys = explode("/", $ontologyKey);
                $ontologyTrueKey = array_pop($ontologySubkeys);
                if ($ontologyTrueKey == $taxonomyValue['fieldName']) {
                    $this->setNestedArrayValue($gogoFeature, $ontologyKey, $taxonomyKey, "/");
                }
            }
        }

        // Core fields
        $myCoreFields = array_diff($element::CORE_FIELDS, ['latitude', 'longitude', 'categories']);
        foreach($myCoreFields as $field) {
            $elemValue = $element->getProperty($field);
            if (isset($elemValue)) {
                // Ontology
                foreach($ontology as $ontologyKey => $ontologyValue) {
                    if ($ontologyValue['mappedProperty'] == $field) {
                        $this->setNestedArrayValue($gogoFeature, $ontologyKey, $elemValue, "/");
                        break;
                    }
                }
            }
        }

        // Custom data
        foreach($element->getData() as $elemKey => $elemValue) {
            foreach($ontology as $ontologyKey => $ontologyValue) {
                if ($ontologyValue['mappedProperty'] == $elemKey) {
                    $this->setNestedArrayValue($gogoFeature, $ontologyKey, $elemValue, "/");
                    break;
                }
            }
        }

        // Other data
        $gogoFeature['osmId'] = intval($element->getProperty('oldId'));
        if ($element->getOpenHours()) {
            $h = $element->getOpenHours()->toOsm();
            if (strlen($h) > 0) {
                $gogoFeature['tags']['opening_hours'] = $h;
            }
        }

        // Tags from the import query
        $queries = $element->getSource()->getOsmQueries();
        if (count($queries) == 1) {
            $query = $queries[0];
            foreach($query as $condition) {
                if ($condition->operator == "=" && !isset($gogoFeature['tags'][$condition->key]))
                    $gogoFeature['tags'][$condition->key] = $condition->value;
            }
        }
        
        // execute custom code
        try {
            eval(str_replace('<?php', '', $element->getSource()->getCustomCodeForExport()));
        } catch (\Exception $e) {
            $gogoFeature['error'] = $e;
        }

        return $gogoFeature;
    }

    /**
     * When adding an element in GoGoCarto, we should consider if this element
     * might be added to OSM as well. If it should, then we detect duplicate on OSM
     * before adding it
     *
     * @param Element $element
     * @return void
     */
    public function checkIfNewElementShouldBeAddedToOsm(Element $element)
    {
        if ($this->linkElementToExistingOsmImport($element)) {
            // Get element in OSM format
            $gogoFeature = $this->elementToOsm($element);
            $gogoFeaturesMainTags = $this->getMainTags($gogoFeature);

            // If can't find main tags, do not send to OSM, feature might be broken
            if (count($gogoFeaturesMainTags) == 0) {
                return ['result' => false, 'duplicates' => []];
            }
            // Otherwise, start looking for duplicates
            else {
                $duplicates = [];

                // Compute bounding box to retrieve
                $radiusKm = self::OSM_SEARCH_RADIUS_METERS / 1000;
                $north = $gogoFeature['center']['latitude'] + ($radiusKm / self::EARTH_RADIUS) * (180 / M_PI);
                $east = $gogoFeature['center']['longitude'] + ($radiusKm / self::EARTH_RADIUS) * (180 / M_PI) / cos($gogoFeature['center']['latitude'] * M_PI / 180);
                $south = $gogoFeature['center']['latitude'] - ($radiusKm / self::EARTH_RADIUS) * (180 / M_PI);
                $west = $gogoFeature['center']['longitude'] - ($radiusKm / self::EARTH_RADIUS) * (180 / M_PI) / cos($gogoFeature['center']['latitude'] * M_PI / 180);

                // Load data from OSM editing API
                $osm = $this->getOsmApiHandler();
                $osm->get($west, $south, $east, $north);
                $potentialDuplicates = $osm->search($gogoFeaturesMainTags);

                // Transform found potential duplicates into GogoCarto format$
                foreach($potentialDuplicates as $dup) {
                    $dupOsmId = $dup->getType().'/'.$dup->getId();
                    array_push($duplicates, [
                        'name' => $dup->getTag('name') ?? $dup->getTag('brand') ?? $dup->getTag('operator') ?? $dup->getTag('owner') ?? $dup->getTag('ref') ?? $dupOsmId,
                        'osmId' => $dupOsmId,
                        'description' => 'Attributs : '.$this->osmTagsToString($dup->getTags()),
                        'address' => [
                            'streetAddress' => implode(' ', array_filter([$dup->getTag('addr:housenumber'), $dup->getTag('addr:street')])),
                            'postalCode' => $dup->getTag('addr:postcode'),
                            'addressLocality' => $dup->getTag('addr:city')
                        ]
                    ]);
                }

                return ['result' => true, 'duplicates' => $duplicates];
            }

        } else {
            return ['result' => false, 'duplicates' => []];
        }
    }

    /**
     * Extract the mains tags from the osm feature
     */
    public function getMainTags($osmFeature)
    {
        // List tags to use for potential duplicates search
        $osmFeaturesMainTags = array_filter(
            $osmFeature['tags'],
            function($key) {
                return in_array($key, self::MAIN_OSM_KEYS);
            },
            ARRAY_FILTER_USE_KEY
        );

        if (count($osmFeaturesMainTags) == 0) {
            $osmFeaturesMainTags = array_filter(
                $osmFeature['tags'],
                function($key) {
                    return in_array($key, self::MAIN_OSM_KEYS_FALLBACK);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $osmFeaturesMainTags;
    }

    /**
     * When adding an element that should be linked to OSM, if we detect some
     * duplicates on OSM we show them to the user. If the user click "yes this is a duplicate"
     * of an OSM point, then instead of adding a new point to OSM, we should update the existing
     * OSM point with the new data.
     *
     * @param Element $element The new element being added to GoGoCarto
     * @param integer $odmId ID of the duplicate on OSM
     * @return void
     */
    public function updateOsmDuplicateWithNewElementData($osmId, Element $element)
    {
        // Init OSM API handler
        $osm = $this->getOsmApiHandler();
        $gogoFeature = $this->elementToOsm($element);
        $osmFeature = null;
        $osmIdParts = explode('/', $osmId); // OSM id is as follow : type/ID <=> node/145236545
        $gogoFeature['type'] = $osmIdParts[0];
        $getType = 'get'.ucfirst($osmIdParts[0]);
        $osmFeature = $osm->$getType($osmIdParts[1]);

        if (!$osmFeature) return;

        $somethingChanged = $this->editOsmFeatureWithGoGoFeature($osmFeature, $gogoFeature);
        if (!$somethingChanged) return;

        $comment = 'Mise à jour attributs '.($osmFeature->getTag('name') ?? $osmFeature->getTag('brand') ?? $osmFeature->getTag('operator') ?? $osmId);
        $changeset = $this->createsChangeSet($osm, $osmFeature, $comment);

        // Close changeset
        try {
            $changeset->commit();

            // Update version in case of feature edit
            if ($toUpdateInDb = $osm->$getType($osmIdParts[1])) {
                $element->setOldId($osmIdParts[1]);
                $element->setCustomProperty('osm_type', $toUpdateInDb->getType());
                $element->setCustomProperty('osm_version', $toUpdateInDb->getVersion());
                $element->setCustomProperty('osm_timestamp', strval($toUpdateInDb->getAttributes()->timestamp));
                $element->setCustomProperty('osm_url', $element->getOsmUrl($this->config));
                $this->dm->persist($element);
                $this->dm->flush();
            }
        }
        catch(\Exception $e) {
            // Error when sending changeset
        }
    }

    /**
     * Check if an element have at least one categorie which is used in an OSM import
     * that is synchronized. In this case the new element will be linked to this import
     *
     * @param Element $element
     * @return Import
     */
    private function linkElementToExistingOsmImport(Element $element)
    {
        $elementOptionsIds = $element->getOptionIds();
        $osmImports = $this->dm->get('Import')->findBy(['sourceType' => 'osm', 'isSynchronized' => true]);
        $linkedImport = null;

        foreach($osmImports as $import) {
            foreach($import->getOptionsToAddToEachElement() as $option) {
                if (in_array($option->getId(), $elementOptionsIds)) {
                    $linkedImport = $import;
                    break;
                }
            }
            foreach($import->getTaxonomyMapping() as $mappedObject) {
                if (array_intersect($mappedObject['mappedCategoryIds'], $elementOptionsIds)) {
                    $linkedImport = $import;
                    break;
                }
            }
        }
        if ($linkedImport) {
            $element->setSource($linkedImport);
        }
        return $linkedImport;
    }

    /*
     * Get a ready-to-use version of OSM API handler (with account/server defined)
     */
    private function getOsmApiHandler()
    {
        $configOsm = $this->getConfig()->getOsm();
        return new Services_OpenStreetMap([
            'server' => $configOsm->getFormattedOsmHost(),
            'user' => $configOsm->getOsmUsername(),
            'password' => $configOsm->getOsmPassword(),
            'User-Agent' => $this->getConfig()->getAppName(),
            'verbose' => true
        ]);
    }

    /**
     * Generate comment for OSM changeset
     */ 
    private function getOsmComment($preparedData) {
        $name = isset($preparedData['data']) && isset($preparedData['data']['name']) ? " " . $preparedData['data']['name'] : "";
        return $this->t('config_osm.sync.comment_text', [
            'action' => $this->t('config_osm.sync.comments.'.$preparedData['action']),
            'name' => $name
        ], 'admin');
    }

    /**
     * Transform a list of OSM tags object into a human-readable string
     */
    private function osmTagsToString($tags) {
        $str = '';

        foreach($tags as $k => $v) {
            if (strlen($str) > 0) { $str .= ', '; }
            $str .= $k.' = '.$v;
        }

        return $str;
    }

    /**
     * Should this contribution be sent to OSM ?
     */
    private function allowOsmUpload($contribution, $preparedData) {
        return $contribution->hasBeenAccepted()
            && ($preparedData['action'] == 'edit' 
            || $preparedData['action'] == 'add' && !$contribution->getElement()->getProperty('osm_version')); // when adding an element, if we find a duplicate on OSM and we say "that's the same", then the gogocarto element is merged with the OSM feature. But an Add contribution is still created, and we should ignore it
    }

    /**
     * Sets a value in a nested array based on path
     * @param array $array The array to modify
     * @param string $path The path in the array
     * @param mixed $value The value to set
     * @param string $delimiter The separator for the path
     * @return The previous value
     */
    private function setNestedArrayValue(&$array, $path, &$value, $delimiter = '/') {
        $pathParts = explode($delimiter, $path);

        $current = &$array;
        foreach($pathParts as $key) {
            $current = &$current[$key];
        }

        $backup = $current;
        $current = $value;

        return $backup;
    }
}
