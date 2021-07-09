<?php

namespace App\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class ElementImportMappingTaxonomyService
{
    protected $ontologyMapping;

    public function __construct(DocumentManager $dm, TranslatorInterface $t)
    {
        $this->dm = $dm;
        $this->t = $t;
        $this->mappingTableIds = [];
    }

    public function collectTaxonomy($data, $import)
    {
        $taxonomyMapping = $import->getTaxonomyMapping();
        // delete obsolte mapping (if an option have been deleted, but is still in the mapping)
        $allOptionsIds = $this->dm->query('Option')->getIds();
        foreach ($taxonomyMapping as $key => $mappedObject) {
            $taxonomyMapping[$key]['mappedCategoryIds'] = array_filter($mappedObject['mappedCategoryIds'], function ($el) use ($allOptionsIds) {
                return in_array($el, $allOptionsIds);
            });
        }
        $import->setTaxonomyMapping($taxonomyMapping);
        $allNewCategories = [];
        $this->createOptionsMappingTable();
        foreach ($data as $row) {
            if (isset($row['categories'])) {
                foreach($row['categories'] as $originProp => $categories) {
                    $categories = is_array($categories) ? $categories : preg_split("[,;]+/", $categories);
                    foreach ($categories as $category) {
                        if (is_array($category)) {
                            $category = $category['name'];
                        }
                        $category = ltrim(rtrim($category));
                        $category = str_replace('.', '_', $category);
                        if (!in_array($category, $allNewCategories)) {
                            $allNewCategories[] = $category;
                        }
                        if ($category) {
                            if (!array_key_exists($category, $taxonomyMapping)) {
                                $categorySlug = slugify($category);
                                $mappedCategoryId = array_key_exists($categorySlug, $this->mappingTableIds) ? $this->mappingTableIds[$categorySlug]['id'] : '';

                                $taxonomyMapping[$category] = [
                                    'mappedCategoryIds' => [$mappedCategoryId],
                                    'collectedCount' => 1,
                                    'fieldName' => $originProp
                                ];
                                if (!$mappedCategoryId) {
                                    $import->setNewTaxonomyToMap(true);
                                }
                            } else {
                                $taxonomyMapping[$category]['collectedCount']++;
                                $taxonomyMapping[$category]['fieldName'] = $originProp;
                            }
                        }
                    }
                }
            }
        }
        // delete no more used categories
        foreach ($taxonomyMapping as $category => $mappedCategory) {
            if (!in_array($category, $allNewCategories)) {
                unset($taxonomyMapping[$category]);
            }
        }
        foreach($taxonomyMapping as &$mappedObject) {
            $mappedObject['collectedPercent'] = $mappedObject['collectedCount'] / count($data) * 100;
        }
        $import->setTaxonomyMapping($taxonomyMapping);
    }

    public function mapTaxonomy($data, $import)
    {
        $mapping = $import->getTaxonomyMapping();
        foreach ($data as $key => $row) {
            if (isset($row['categories'])) {
                $elementCategories = [];
                $categoriesIds = [];
                foreach ($row['categories'] as $fieldName => $categories) {
                    foreach($categories as $category) {
                        $catName = is_array($category) ? $category['name'] : $category;
                        $catName = ltrim(rtrim($catName));
                        if (isset($mapping[$catName]['mappedCategoryIds']) && $mapping[$catName]['mappedCategoryIds']) {
                            foreach ($mapping[$catName]['mappedCategoryIds'] as $mappedCategoryId) {
                                if (array_key_exists($mappedCategoryId, $this->mappingTableIds)) {
                                    $newcat['id'] = $this->mappingTableIds[$mappedCategoryId]['id'];
                                    if (!in_array($newcat['id'], $categoriesIds)) {
                                        if (isset($category['index'])) {
                                            $newcat['index'] = $category['index'];
                                        }
                                        if (isset($category['description'])) {
                                            $newcat['description'] = $category['description'];
                                        }
                                        $elementCategories[] = $newcat;
                                        $categoriesIds[] = $newcat['id'];
                                        $parentIds = $this->mappingTableIds[$mappedCategoryId]['idAndParentsId'];
                                        // Adds also the parent categories
                                        foreach ($parentIds as $id) {
                                            if (!in_array($id, $categoriesIds)) {
                                                $categories[] = [
                                                    'id' => $id,
                                                    'info' => $this->t->trans('importService.auto_added_category', [], 'admin')
                                                ];
                                                $categoriesIds[] = $id;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $data[$key]['categories'] = $elementCategories;
            }
        }

        return $data;
    }

    private function createOptionsMappingTable($options = null)
    {
        if (null === $options) {
            $options = $this->dm->get('Option')->findAll();
        }

        foreach ($options as $option) {
            $ids = [
                'id' => $option->getId(),
                'name' => $option->getName(),
                'idAndParentsId' => $option->getIdAndParentOptionIds(),
            ];
            $this->mappingTableIds[slugify($option->getNameWithParent())] = $ids;
            $this->mappingTableIds[slugify($option->getName())] = $ids;
            $this->mappingTableIds[strval($option->getId())] = $ids;
            if ($option->getCustomId()) {
                $this->mappingTableIds[slugify($option->getCustomId())] = $ids;
            }
        }
    }
}