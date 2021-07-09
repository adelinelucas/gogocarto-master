<?php

namespace App\Controller\Admin\BulkActions;

use App\Document\ModerationState;
use App\Services\ElementDuplicatesService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DuplicatesDetectionController extends BulkActionsAbstractController
{
    public function detectDuplicatesAction(Request $request, SessionInterface $session, DocumentManager $dm,
                                           ElementDuplicatesService $duplicateService, TranslatorInterface $t)
    {
        $this->title = $t->trans('bulk.detectDuplicatesAction', [], 'admin');
        $this->automaticRedirection = false;
        $this->batchSize = 1000;
        $this->duplicateService = $duplicateService;
        $this->config = $dm->get('Configuration')->findConfiguration();

        if (!$request->get('batchFromStep')) {
            // reset previous detections
            $dm->query('Element')->updateMany()
                ->field('isDuplicateNode')->unsetField()
                ->field('potentialDuplicates')->unsetField()
                ->execute();
            $dm->query('Element')->updateMany()
                ->field('moderationState')->equals(ModerationState::PotentialDuplicate)
                ->field('moderationState')->set(ModerationState::NotNeeded)
                ->execute();
        }

        return $this->elementsBulkAction('detectDuplicates', $dm, $request, $session, $t);
    }

    protected function filterElements($qb)
    {
        if (count($this->config->getDuplicates()->getSourcesToDetectFrom()) > 0)
            $qb->field('sourceKey')->in($this->config->getDuplicates()->getSourcesToDetectFrom());
        return $qb;
    }

    public function detectDuplicates($element, $dm)
    {
        $result = $this->duplicateService->detectDuplicatesFor($element, $this->config->getDuplicates()->getSourcesToDetectWith());
        if ($result === null) return "";
        return $this->render('admin/pages/bulks/bulk_duplicates.html.twig', [
            'duplicates' => [$result['elementToKeep'], $result['duplicate']],
            'config' => $this->config,
            'automaticMerge' => $result['automaticMerge'],
         ]);
    }
}
