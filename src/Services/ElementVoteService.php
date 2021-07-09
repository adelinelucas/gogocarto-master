<?php

namespace App\Services;

use App\Document\ElementStatus;
use App\Document\ModerationState;
use App\Document\UserInteractionVote;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ElementVoteService
{
    /**
     * Constructor.
     */
    public function __construct(DocumentManager $dm, TokenStorageInterface $securityContext, 
      ConfigurationService $confService, ElementPendingService $elementPendingService,
      TranslatorInterface $t)
    {
        $this->dm = $dm;
        $this->user = $securityContext->getToken() ? $securityContext->getToken()->getUser() : null;
        $this->confService = $confService;
        $this->securityContext = $securityContext;
        $this->elementPendingService = $elementPendingService;
        $this->t = $t;
    }

    // Handle a vote (positive or negative) for pending elements
    public function voteForElement($element, $voteValue, $comment, $userEmail = null)
    {
        // Check user don't vote for his own creation
        if ($element->isLastContributorEqualsTo($this->user, $userEmail)) {
            return $this->t->trans('vote.user_vote_for_him');
        }

        $hasAlreadyVoted = false;

        if ($this->confService->isUserAllowed('directModeration')) {
            $procedureCompleteMessage = $this->handleVoteProcedureComplete($element, ValidationType::Admin, $voteValue >= 1, $comment);
        } else {
            // CHECK USER HASN'T ALREADY VOTED
            $currentVotes = $element->getVotes();
            // if user is anonymous no need to check
            if ($userEmail || $this->user) {
                foreach ($currentVotes as $oldVote) {
                    if ($oldVote->isMadeBy($this->user, $userEmail)) {
                        $hasAlreadyVoted = true;
                        $vote = $oldVote;
                    }
                }
            }

            if (!$hasAlreadyVoted) {
                $vote = new UserInteractionVote();
            }

            $vote->setValue($voteValue);
            $vote->setElement($element);
            $vote->updateUserInformation($this->securityContext, $userEmail);
            if ($comment) {
                $vote->setComment($comment);
            }

            if (!$hasAlreadyVoted) {
                $element->getCurrContribution()->addVote($vote);
            }

            $procedureCompleteMessage = $this->checkVotes($element);
        }

        $element->updateTimestamp();

        $this->dm->persist($element);
        $this->dm->flush();

        $resultMessage = $hasAlreadyVoted ? $this->t->trans('vote.vote_modified', [ 'user' => $this->user ]) : $this->t->trans('vote.vote_added');
        if ($procedureCompleteMessage) {
            $resultMessage .= '</br>'.$procedureCompleteMessage;
        }

        return $resultMessage;
    }

    /*
    * Check vote on PENDING Element
    * Differents conditions :
    *   - Enough votes to change status
    *   - Not too much opposites votes
    *   - Waiting for minimum days after contribution to validate or invalidate
    *
    * If an element is pending for too long, it's set in Moderation
    *
    * This action is called when user vote, and with a CRON job every days
    */
    public function checkVotes($element, $dm = null)
    {
        if (!$element->getCurrContribution()) {
            return;
        }
        if ($dm) $this->dm = $dm;

        $currentVotes = $element->getVotes();
        $nbrePositiveVote = 0;
        $nbreNegativeVote = 0;

        $diffDate = time() - $element->getCurrContribution()->getCreatedAt()->getTimestamp();
        $daysFromContribution = floor($diffDate / (60 * 60 * 24));

        foreach ($currentVotes as $key => $vote) {
            $vote->getValue() >= 0 ? $nbrePositiveVote++ : $nbreNegativeVote++;
        }
        $config = $this->dm->get('Configuration')->findConfiguration();
        $enoughDays = $daysFromContribution >= $config->getMinDayBetweenContributionAndCollaborativeValidation();
        $maxOppositeVoteTolerated = $config->getMaxOppositeVoteTolerated();
        $minVotesToChangeStatus = $config->getMinVoteToChangeStatus();
        $minVotesToForceChangeStatus = $config->getMinVoteToForceChangeStatus();

        if ($nbrePositiveVote >= $minVotesToChangeStatus) {
            if ($nbreNegativeVote <= $maxOppositeVoteTolerated) {
                if ($enoughDays || $nbrePositiveVote >= $minVotesToForceChangeStatus) {
                    return $this->handleVoteProcedureComplete($element, ValidationType::Collaborative, true);
                }
            } else {
                $element->setModerationState(ModerationState::VotesConflicts);
            }
        } elseif ($nbreNegativeVote >= $minVotesToChangeStatus) {
            if ($nbrePositiveVote <= $maxOppositeVoteTolerated) {
                if ($enoughDays || $nbreNegativeVote >= $minVotesToForceChangeStatus) {
                    return $this->handleVoteProcedureComplete($element, ValidationType::Collaborative, false);
                }
            } else {
                $element->setModerationState(ModerationState::VotesConflicts);
            }
        }
        if (!$config->getCollaborativeModerationFeature()->getActive()
            || $daysFromContribution > $config->getMaxDaysLeavingAnElementPending()) {
            $element->setModerationState(ModerationState::PendingForTooLong);
        }
    }

    private function handleVoteProcedureComplete($element, $voteType, $positiveVote, $customMessage = '')
    {
        // in case of procedure complete directly after a userInteraction, we send a message back to the user
        $flashMessage = '';
        $config = $this->dm->get('Configuration')->findConfiguration();
        $elDisplayName = $config->getElementDisplayNameDefinite();

        if (ElementStatus::PendingAdd == $element->getStatus()) {
            if (ValidationType::Collaborative == $voteType) {
                $flashMessage = $positiveVote ? $this->t->trans('vote.collaborative.positive', [ 'element' => $elDisplayName ]) : $this->t->trans('vote.collaborative.negative', [ 'element' => ucwords($elDisplayName) ]);
            } elseif (ValidationType::Admin == $voteType) {
                $flashMessage = $positiveVote ? $this->t->trans('vote.admin.positive', [ 'element' => ucwords($elDisplayName) ]) : $this->t->trans('vote.admin.negative', [ 'element' => ucwords($elDisplayName) ]);
            }
        } elseif (ElementStatus::PendingModification == $element->getStatus()) {
            if ($positiveVote) {
                $flashMessage = ValidationType::Admin == $voteType ? $this->t->trans('vote.pending.admin_validated.positive') : $this->t->trans('vote.pending.user_validated.positive');
            } else {
                $flashMessage = ValidationType::Admin == $voteType ? $this->t->trans('vote.pending.admin_validated.negative') : $this->t->trans('vote.pending.user_validated.negative');
            }
        }

        // Handle validation or refusal with dedicate service
        $this->elementPendingService->resolve($element, $positiveVote, $voteType, $customMessage);

        return $flashMessage;
    }
}
