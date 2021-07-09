<?php

namespace App\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class TaxonomyRepository extends DocumentRepository
{
    public function findTaxonomy()
    {
        return $this->query('Taxonomy')->getOne();
    }

    public function findTaxonomyJson($getOnlyOptions = false)
    {
        $qb = $this->query('Taxonomy');
        $taxonomy = $qb->hydrate(false)->getOne();

        return $getOnlyOptions ? $taxonomy['optionsJson'] : $taxonomy['taxonomyJson'];
    }
}
