<?php

namespace App\Services;

use App\Document\ElementStatus;
use App\Document\UserInteractionContribution;
use App\Document\InteractionType;
use App\Document\Webhook;
use App\Document\WebhookPost;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Service used to handle to resolution of pending Elements.
 **/
class UserInteractionService
{
    protected $webhooks;

    /**
     * Constructor.
     */
    public function __construct(DocumentManager $dm, TokenStorageInterface $securityContext)
    {
        $this->dm = $dm;
        $this->securityContext = $securityContext;
        $this->webhooks = $this->dm->getRepository(Webhook::class)->findAll();
    }

    public function createContribution($element, $message, $interactType, $status, $directModerationWithHash = false, $email = null, $automatic = false)
    {
        $contribution = new UserInteractionContribution();
        $contribution->setType($interactType);
        $contribution->updateUserInformation($this->securityContext, $email, $directModerationWithHash, $automatic);
        $contribution->setResolvedMessage($message);

        // pending contribution does not have status
        if ($status) {
            $contribution->updateResolvedBy($this->securityContext, null, $directModerationWithHash, $automatic);
            $contribution->setStatus($status);
        }

        // Create webhook posts to be dispatched
        // for Pending contributions, we will wait for the status to be set (i.e. contribution is resolved) before dipatching those events
        if (!$automatic && $interactType != InteractionType::ModerationResolved) { 
            foreach ($this->webhooks as $webhook) {
                $this->createPostFor($contribution, $webhook);
            }            
            if ($element && $element->isSynchedWithExternalDatabase()) {
                $this->createPostFor($contribution, null);
            }
        }
        if ($element) $element->addContribution($contribution);
        return $contribution;
    }

    private function createPostFor($contribution, $webhook)
    {
        $post = new WebhookPost();
        if ($webhook) $post->setWebhook($webhook);
        $post->setNextAttemptAt(new \DateTime());
        $contribution->addWebhookPost($post);
    }

    public function resolveContribution($element, $isAccepted, $validationType, $message)
    {
        $contribution = $element->getCurrContribution();
        if (!$contribution) {
            return;
        }
        if (!$isAccepted) {
            $contribution->clearWebhookPosts();
        }

        if (2 == $validationType) { // 2 = ValidationType::Admin
            $contribution->setResolvedMessage($message);
            $contribution->updateResolvedby($this->securityContext);
            $contribution->setStatus($isAccepted ? ElementStatus::AdminValidate : ElementStatus::AdminRefused);
        } else {
            $text = $isAccepted ? $this->t('pending-contributions.approved') : $this->t('pending-contributions.rejected');
            $contribution->setResolvedMessage($text);
            $contribution->setResolvedby('Collaborative process');
            $contribution->setStatus($isAccepted ? ElementStatus::CollaborativeValidate : ElementStatus::CollaborativeRefused);
        }
    }
}
