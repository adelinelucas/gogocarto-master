<?php

namespace App\Services;

use App\Document\ElementStatus;
use App\Document\ModerationState;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

// strange bug importing this class does not work, so redeclare it here
abstract class InteractType
{
    const Deleted = -1;
    const Add = 0;
    const Edit = 1;
    const Vote = 2;
    const Report = 3;
    const Import = 4;
    const Restored = 5;
    const ModerationResolved = 6;
}

/**
 * Service used to handle to resolution of pending Elements.
 **/
class ElementActionService
{
    /**
     * Constructor.
     */
    public function __construct(DocumentManager $dm, TokenStorageInterface $securityContext, MailService $mailService, ElementPendingService $elementPendingService, UserInteractionService $interactionService)
    {
        $this->dm = $dm;
        $this->securityContext = $securityContext;
        $this->mailService = $mailService;
        $this->elementPendingService = $elementPendingService;
        $this->interactionService = $interactionService;
    }

    public function add($element, $sendMail = true, $message = null)
    {
        $this->addContribution($element, $message, InteractType::Add, ElementStatus::AddedByAdmin);
        $element->setStatus(ElementStatus::AddedByAdmin);
        if ($sendMail) {
            $this->mailService->sendAutomatedMail('add', $element, $message);
        }
        $element->updateTimestamp();
    }

    public function edit($element, $sendMail = true, $message = null, $modifiedByOwner = false, $directModerationWithHash = false)
    {
        if (ElementStatus::ModifiedPendingVersion == $element->getStatus()) {
            $element = $this->dm->get('Element')->findOriginalElementOfModifiedPendingVersion($element);
            $this->resolve($element, true, ValidationType::Admin, $message);
        } elseif ($sendMail) {
            $this->mailService->sendAutomatedMail('edit', $element, $message);
        }

        $status = $modifiedByOwner ? ElementStatus::ModifiedByOwner : ElementStatus::ModifiedByAdmin;
        $status = $directModerationWithHash ? ElementStatus::ModifiedFromHash : $status;
        $this->addContribution($element, $message, InteractType::Edit, $status, $directModerationWithHash);
        $element->setStatus($status);
        if (!$modifiedByOwner) {
            $this->resolveReports($element, $message);
        }
        $element->updateTimestamp();
    }

    public function createPending($element, $editMode, $userEmail)
    {
        $this->elementPendingService->createPending($element, $editMode, $userEmail);
        $element->updateTimestamp();
    }

    public function savePendingModification($element)
    {
        return $this->elementPendingService->savePendingModification($element);
    }

    public function resolve($element, $isAccepted, $validationType = ValidationType::Admin, $message = null)
    {
        $this->elementPendingService->resolve($element, $isAccepted, $validationType, $message);
        $element->updateTimestamp();
    }

    public function delete($element, $sendMail = true, $message = null)
    {
        if ($sendMail) {
            $this->mailService->sendAutomatedMail('delete', $element, $message);
        }
        // do not add contribution for elements already deleted
        if ($element->isVisible()) {
            $this->addContribution($element, $message, InteractType::Deleted, ElementStatus::Deleted);
        }

        $newStatus = $element->isPotentialDuplicate() ? ElementStatus::Duplicate : ElementStatus::Deleted;
        $element->setStatus($newStatus);
        $this->resolveReports($element, $message);
        $element->updateTimestamp();
    }

    public function restore($element, $sendMail = true, $message = null)
    {
        $this->addContribution($element, $message, InteractType::Restored, ElementStatus::AddedByAdmin);
        $element->setStatus(ElementStatus::AddedByAdmin);
        $this->resolveReports($element, $message);
        if ($sendMail) {
            $this->mailService->sendAutomatedMail('add', $element, $message);
        }
        $element->setDuplicateOf(null); // reset this field
        $element->updateTimestamp();
    }

    public function resolveReports($element, $message = '', $addContribution = false)
    {
        $reports = $element->getUnresolvedReports();
        if (count($reports) > 0) {
            foreach ($reports as $key => $report) {
                $report->setResolvedMessage($message);
                $report->updateResolvedBy($this->securityContext);
                $report->setIsResolved(true);
                $this->mailService->sendAutomatedMail('report', $element, $message, $report);
            }
        } elseif ($addContribution) {
            $this->addContribution($element, $message, InteractType::ModerationResolved, $element->getStatus());
        }

        $element->updateTimestamp();
        $element->setModerationState(ModerationState::NotNeeded);
    }

    private function addContribution($element, $message, $interactType, $status, $directModerationWithHash = false)
    {
        // clear contributions with same type that have not been dispatched yet
        if ($element->getContributions()) {
            foreach ($element->getContributions() as $contribution) {
                if ($contribution->getType() == $interactType && $contribution->getWebhookPosts()) {
                    $contribution->clearWebhookPosts();
                }
            }
        }
        $contribution = $this->interactionService->createContribution($element, $message, $interactType, $status, $directModerationWithHash);
    }
}
