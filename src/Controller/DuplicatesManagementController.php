<?php

namespace App\Controller;

use App\Services\ElementActionService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Document\ModerationState;
use App\Services\ElementDuplicatesService;
use Symfony\Contracts\Translation\TranslatorInterface;

class DuplicatesManagementController extends GoGoController
{
    const DUPLICATE_BATH_SIZE = 15;

    public function indexAction(DocumentManager $dm, TokenStorageInterface $securityContext)
    {
        $userEmail = $securityContext->getToken()->getUser()->getEmail() ?? '';

        $qb = $dm->query('Element')->field('isDuplicateNode')->equals(true);
        $qb->addOr($qb->expr()->field('lockUntil')->lte(time()),                    
                   $qb->expr()->field('lockedByUserEmail')->equals($userEmail));                    
        $duplicatesNode = $qb->limit(self::DUPLICATE_BATH_SIZE)->getCursor();
        $duplicatesTotalCount = $dm->query('Element')->field('isDuplicateNode')->equals(true)->getCount();
        $leftDuplicatesToProceedCount = $duplicatesTotalCount - min($duplicatesNode->count(), self::DUPLICATE_BATH_SIZE);
        
        $lockUntil = time() + 10 * 60; // lock for 10 minutes
        foreach ($duplicatesNode as $key => $element) {
            $element->setLockUntil($lockUntil);
            $element->setLockedByUserEmail($userEmail);
        }
        $dm->flush();

        return $this->render('duplicates/duplicates-index.html.twig', [
            'duplicatesNode' => $duplicatesNode, 
            'config' => $dm->get('Configuration')->findConfiguration(), 
            'leftDuplicatesToProceedCount' => $leftDuplicatesToProceedCount
        ]);
    }

    public function mergeDuplicateAction(Request $request, ElementDuplicatesService $duplicateService,
                                         DocumentManager $dm, ElementActionService $elementActionService,
                                         TranslatorInterface $t)
    {
        if ($request->isXmlHttpRequest()) {
            if (!$request->get('elementId')) {
                return new Response('Missing elementId param');
            }
            $element = $dm->get('Element')->find($request->get('elementId'));
            $duplicates = array_merge([$element], $element->getPotentialDuplicates()->toArray());
            $duplicateService->automaticMerge($element, $element->getPotentialDuplicates());
            foreach($duplicates as $duplicate) {
                $duplicate->setIsDuplicateNode(false);
                $duplicate->clearPotentialDuplicates();
                $elementActionService->resolveReports($duplicate, $t->trans('duplicates.controller.merged'), true);
                $duplicate->setModerationState(ModerationState::NotNeeded);
            }            
            $dm->flush();
            
            return new Response('Elements successfully merged');
        } else {
            return new Response('Not valid ajax request');
        }
    }

    // Will mark all the
    public function markAsNonDuplicateAction(Request $request, DocumentManager $dm, ElementActionService $elementActionService,
                                             TranslatorInterface $t)
    {
        if ($request->isXmlHttpRequest()) {
            if (!$request->get('elementId')) {
                return new Response('Missing elementId param');
            }

            $element = $dm->get('Element')->find($request->get('elementId'));

            $element->setIsDuplicateNode(false);
            $duplicates = $element->getPotentialDuplicates() ? $element->getPotentialDuplicates()->toArray() : [];
            // if no potential duplicates, element has already been resolved, nothing to do
            if (count($duplicates) > 0) {
                $duplicates[] = $element;
                // Adds each other to non duplicates
                foreach ($duplicates as $key => $duplicate) {
                    foreach ($duplicates as $dup) {
                        if ($dup != $duplicate) {
                            $duplicate->addNonDuplicate($dup);
                        }
                    }
                }
                // resolve
                foreach ($duplicates as $key => $duplicate) {
                    $elementActionService->resolveReports($duplicate, $t->trans('duplicates.controller.marked'), true);
                }
                $element->clearPotentialDuplicates();
                $dm->flush();
            }

            return new Response($t->trans('duplicates.controller.marked_long'));
        } else {
            return new Response('Not valid ajax request');
        }
    }
}
