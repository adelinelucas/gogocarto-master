<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\NewsStatus;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

final class NewsRepository extends DocumentRepository
{
    public function findLastPublishedNews($lastNewsletterSentAt)
    {
        $qb = $this->createQueryBuilder()
            ->field('status')->equals(NewsStatus::PUBLISHED)
            ->sort('publicationDate', 'desc')
            ->field('publicationDate')->lte(new \DateTime());
        if ($lastNewsletterSentAt !== null)
            $qb->field('publicationDate')->gte($lastNewsletterSentAt);
        return $qb->getQuery()->execute();
    }
}
