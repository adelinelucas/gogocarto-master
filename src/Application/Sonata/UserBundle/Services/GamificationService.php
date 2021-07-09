<?php

/**
 * @Author: Sebastian Castro
 * @Date:   2017-12-30 14:32:19
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-02-11 13:06:59
 */

namespace App\Application\Sonata\UserBundle\Services;

use App\Document\InteractionType;
use Doctrine\ODM\MongoDB\DocumentManager;

class GamificationService
{
    protected $interactionRepo;

    public function __construct(DocumentManager $dm)
    {
        $this->contribsRepo = $dm->get('UserInteractionContribution');
        $this->votesRepo = $dm->get('UserInteractionVote');
        $this->reportsRepo = $dm->get('UserInteractionReport');
    }

    public function updateGamification($user)
    {
        if (!$user->getEmail()) {
            return;
        }

        $contribs = $this->contribsRepo->findByUserEmail($user->getEmail());

        $contribs = array_filter($contribs, function ($interaction) {
            return in_array($interaction->getType(), [InteractionType::Add, InteractionType::Edit]);
        });

        $votes = $this->votesRepo->findByUserEmail($user->getEmail());
        $reports = $this->reportsRepo->findByUserEmail($user->getEmail());

        $result = count($contribs) * 3 + count($reports) + count($votes);
        $user->setGamification($result);
        $user->setContributionsCount(count($contribs));
        $user->setVotesCount(count($votes));
        $user->setReportsCount(count($reports));
    }
}
