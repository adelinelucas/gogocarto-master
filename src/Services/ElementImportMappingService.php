<?php

namespace App\Services;

use Doctrine\ODM\MongoDB\DocumentManager;

class ElementImportMappingService
{
    protected $dm;
    protected $config;

    public function __construct(DocumentManager $dm, 
                                ElementImportMappingOntologyService $ontologyService,
                                ElementImportMappingTaxonomyService $taxonomyService)
    {
        $this->dm = $dm;
        $this->ontologyService = $ontologyService;
        $this->taxonomyService = $taxonomyService;
        $this->mappingTableIds = [];
    }

    private function getConfig()
    {
        if (!$this->config) $this->config = $this->dm->get('Configuration')->findConfiguration();
        return $this->config;
    }

    public function transform($data, $import)
    {
        // Execute custom code (the <?php is used to have proper code highliting in text editor, we remove it before executing)
        try {
            eval(str_replace('<?php', '', $import->getCustomCode()));
        } catch (\Exception $e) {
            return null;
        }
        if (null == $data || !is_array($data)) {
            return [];
        }

        // elements is ofently stored nested in some attribute
        if (array_key_exists('data', $data) && is_array($data['data'])) {
            $data = $data['data'];
        }
        if (array_key_exists('elements', $data) && is_array($data['elements'])) {
            $data = $data['elements'];
        }
        if (array_key_exists('features', $data) && is_array($data['features'])) {
            $data = $data['features'];
        }

        // Fixs known ontology when importing
        foreach ($data as $key => $row) {
            if (is_array($row)) {
                // GOGOCARTO ONTOLGY
                if (isset($row['geo']) && isset($row['geo']['latitude']) && isset($row['geo']['longitude'])) {
                    $data[$key]['latitude'] = $row['geo']['latitude'];
                    $data[$key]['longitude'] = $row['geo']['longitude'];
                    unset($data[$key]['geo']);
                }
                if (array_key_exists('address', $row)) {
                    $address = $row['address'];

                    if ('string' == gettype($address)) {
                        $data[$key]['streetAddress'] = $address;
                    } elseif ($address) {
                        $data[$key] = array_merge($data[$key], $address);
                    }
                    unset($data[$key]['address']);
                }
                if (array_key_exists('categories', $row) && array_key_exists('categoriesFull', $row)) {
                    $data[$key]['categories'] = $data[$key]['categoriesFull'];
                    unset($data[$key]['categoriesFull']);
                }

                // GEOJSON
                if (isset($row['geometry']) && isset($row['geometry']['coordinates'])) {
                    $data[$key]['latitude'] = $row['geometry']['coordinates'][1];
                    $data[$key]['longitude'] = $row['geometry']['coordinates'][0];
                    unset($data[$key]['geometry']);

                    if (isset($row['properties']) && is_associative_array($row['properties'])) {
                        foreach ($row['properties'] as $field => $value) {
                            $data[$key][$field] = $value;
                        }
                        unset($data[$key]['properties']);
                    }
                }

                // OPENSTREETMAP
                if ($import->getSourceType() == 'osm') {
                    if (isset($data[$key]['center'])) {
                        $data[$key]['lat'] = $data[$key]['center']['lat'];
                        $data[$key]['lon'] = $data[$key]['center']['lon'];
                        unset($data[$key]['center']);
                    } 
                    // remove unrelevant metadata so it does not get too noisy            
                    unset($data[$key]['nodes']); 
                    unset($data[$key]['changeset']); 
                    unset($data[$key]['uid']); 
                    unset($data[$key]['user']); 
                    $data[$key]['osmUrl'] = $this->getConfig()->getOsm()->getFormattedOsmHost() . "{$row['type']}/{$row['id']}";
                }
            } else {
                // the $row is not an array, probably a string so we ignore it
                unset($data[$key]);
            }
        }
        
        // Ontology
        $this->ontologyService->collectOntology($data, $import);
        $data = $this->ontologyService->mapOntology($data, $import);
        
        if (null == $data || !is_array($data)) {
            return [];
        }
        // remove empty row, i.e. without name
        $data = array_filter($data, function ($row) { return array_key_exists('name', $row); });

        // Taxonomy
        if ($import->isCategoriesFieldMapped()) {
            $this->taxonomyService->collectTaxonomy($data, $import);
            $data = $this->taxonomyService->mapTaxonomy($data, $import);
        }

        try {
            $this->dm->persist($import);
            $this->dm->flush();
        } catch (\Exception $e) {
            // catching corrupt BSON
            return null;
        }

        return $data;
    }
}
