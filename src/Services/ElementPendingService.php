<?php

namespace App\Services;

use App\Document\ElementStatus;
use App\Document\ModerationState;
use Doctrine\ODM\MongoDB\DocumentManager;

abstract class ValidationType
{
    const Collaborative = 1;
    const Admin = 2;
}

/**
 * Service used to handle to resolution of pending Elements.
 **/
class ElementPendingService
{
    /**
     * Constructor.
     */
    public function __construct(DocumentManager $dm, MailService $mailService, UserInteractionService $interactionService)
    {
        $this->dm = $dm;
        $this->mailService = $mailService;
        $this->interactionService = $interactionService;
    }

    // When element in added or modified by non admin, we go throw this function
    // It create an appropriate contribution, and set the status to pending
    // We could also send a confirmation mail to the contributor for example
    public function createPending($element, $editMode, $userEmail, $automatic = false)
    {
        // in case user edit it's own contribution, the element is still pending, and
        // we want to make it pending again. So we resolve the previous contribution
        if ($element->isPending() && $element->getCurrContribution()) {
            $element->getCurrContribution()->setStatus(ElementStatus::ModifiedByOwner);
        }

        $this->interactionService->createContribution($element, null, $editMode ? 1 : 0, null, false, $userEmail, $automatic);

        $element->setStatus($editMode ? ElementStatus::PendingModification : ElementStatus::PendingAdd);

        $config = $this->dm->get('Configuration')->findConfiguration();
        // If collaborative moderation is disabled, then it should directly go to moderation
        if (!$config->getCollaborativeModerationFeature()->getActive())
            $element->setModerationState(ModerationState::PendingForTooLong);
    }

    // In case of collaborative modification, we actually don't change the elements attributes.
    // Instead we save the modifications in the modifiedElement attributes.
    // The old element as just his status attribute modified, all the other modifications are saved in modifiedelement attribute
    public function savePendingModification($elementToCopy)
    {
        if ($elementToCopy->getModifiedElement()) {
            $oldModifiedElementId = $elementToCopy->getModifiedElement()->getId();
            $elementToCopy->setModifiedElement(null);          
        }
        $elementToCopy->resetContributions();
        $elementToCopy->resetReports();
        $modifiedElement = clone $elementToCopy;
        $modifiedElement->setId($oldModifiedElementId ?? null); // update previous mopdifiedElement or create a new one        
        $modifiedElement->setStatus(ElementStatus::ModifiedPendingVersion);

        // making a real refresh, calling refresh and getting again the element from DB (otherwise there were conflicts)
        $this->dm->refresh($elementToCopy);
        $id = $elementToCopy->getId();
        $oldElement = $this->dm->get('Element')->find($id);
        $oldElement->setModifiedElement($modifiedElement);

        return $oldElement;
    }

    // Action called to relsolve a pending element. This actions in triggered from both admin or collaborative resolve
    public function resolve($element, $isAccepted, $validationType = ValidationType::Admin, $message = null)
    {
        // Call specifics action depending of contribution type and validation or refusal
        if (ElementStatus::PendingAdd == $element->getStatus()) {
            if ($isAccepted) {
                $this->acceptNewElement($element, $message);
            } else {
                $this->refuseNewElement($element);
            }

            $this->updateStatusAfterValidationOrRefusal($element, $isAccepted, $validationType);
        } elseif (ElementStatus::PendingModification == $element->getStatus()) {
            if ($isAccepted) {
                $this->acceptModifiedElement($element, $message);
            } else {
                $this->refuseModifiedElement($element);
            }

            // For pending modification, both validation or refusal ends with validation status
            $element->setStatus(ValidationType::Collaborative == $validationType ? ElementStatus::CollaborativeValidate : ElementStatus::AdminValidate);
        }

        $this->interactionService->resolveContribution($element, $isAccepted, $validationType, $message);

        $this->sendMailToContributorAfterValidationOrRefusal($element, $isAccepted, $validationType, $message);
    }

    private function acceptNewElement($element, $message)
    {
        $this->mailService->sendAutomatedMail('add', $element, $message);
    }

    public function refuseNewElement($element)
    {
    }

    private function acceptModifiedElement($element, $message)
    {
        $modifiedElement = $element->getModifiedElement();
        if ($modifiedElement) {
            // copying following attributes
            $attributes = ['name', 'geo', 'address', 'optionValues', 'email', 'openHours', 'images', 'files', 'data'];
            foreach ($attributes as $key) {
                $getter = 'get'.ucfirst($key);
                $setter = 'set'.ucfirst($key);
                $element->$setter($modifiedElement->$getter());
            }
            $element->setModifiedElement(null);
        }

        $this->mailService->sendAutomatedMail('edit', $element, $message);
    }

    private function refuseModifiedElement($element)
    {
        $element->setModifiedElement(null);
    }

    private function updateStatusAfterValidationOrRefusal($element, $isAccepted, $validationType)
    {
        if (ValidationType::Collaborative == $validationType) {
            $element->setStatus($isAccepted ? ElementStatus::CollaborativeValidate : ElementStatus::CollaborativeRefused);
        } elseif (ValidationType::Admin == $validationType) {
            $element->setStatus($isAccepted ? ElementStatus::AdminValidate : ElementStatus::AdminRefused);
        }
    }

    private function sendMailToContributorAfterValidationOrRefusal($element, $isAccepted, $validationType, $message = null)
    {
        if (!$message && $element->getCurrContribution()) {
            $message = $element->getCurrContribution()->getResolvedMessage();
        }
        $this->mailService->sendAutomatedMail($isAccepted ? 'validation' : 'refusal', $element, $message);
    }
}
