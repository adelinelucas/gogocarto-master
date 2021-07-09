<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2016 Sebastian Castro - 90scastro@gmail.com
 * @license    MIT License
 * @Last Modified time: 2018-04-07 16:22:43
 */

namespace App\Controller;

use App\Document\UserInteractionReport;
use App\Services\ConfigurationService;
use App\Services\ElementActionService;
use App\Services\ElementVoteService;
use App\Services\MailService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ElementInteractionController extends Controller
{
    public function voteAction(Request $request, DocumentManager $dm, ConfigurationService $confService,
                               ElementVoteService $voteService, TranslatorInterface $t)
    {
        if (!$confService->isUserAllowed('vote', $request)) {
            return $this->returnResponse(false, $t->trans('action.element.vote.unallowed'));
        }

        // CHECK REQUEST IS VALID
        if (!$request->get('elementId') || null === $request->get('value')) {
            return $this->returnResponse(false, $t->trans('action.element.vote.uncomplete'));
        }

        $element = $dm->get('Element')->find($request->get('elementId'));

        $resultMessage = $voteService->voteForElement($element, $request->get('value'),
                                                      $request->get('comment'),
                                                      $request->get('userEmail'));

        return $this->returnResponse(true, $resultMessage, $element->getStatus());
    }

    public function reportErrorAction(Request $request, DocumentManager $dm, ConfigurationService $confService,
                                      TranslatorInterface $t)
    {
        if (!$confService->isUserAllowed('report', $request)) {
            return $this->returnResponse(false, $t->trans('action.element.report.unallowed'));
        }

        // CHECK REQUEST IS VALID
        if (!$request->get('elementId') || null === $request->get('value') || !$request->get('userEmail')) {
            return $this->returnResponse(false, $t->trans('action.element.report.uncomplete'));
        }

        $element = $dm->get('Element')->find($request->get('elementId'));

        $report = new UserInteractionReport();
        $report->setValue($request->get('value'));
        $report->updateUserInformation($this->container->get('security.token_storage'), $request->get('userEmail'));
        $comment = $request->get('comment');
        if ($comment) {
            $report->setComment($comment);
        }

        $element->addReport($report);

        $element->updateTimestamp();

        $dm->persist($element);
        $dm->flush();

        return $this->returnResponse(true, $t->trans('action.element.report.done'));
    }

    public function deleteAction(Request $request, DocumentManager $dm, ConfigurationService $confService,
                                 ElementActionService $elementActionService,
                                 TranslatorInterface $t)
    {
        if (!$confService->isUserAllowed('delete', $request)) {
            return $this->returnResponse(false, $t->trans('action.element.delete.unallowed'));
        }

        // CHECK REQUEST IS VALID
        if (!$request->get('elementId')) {
            return $this->returnResponse(false, $t->trans('action.element.delete.uncomplete'));
        }

        $element = $dm->get('Element')->find($request->get('elementId'));
        $dm->persist($element);

        $elementActionService->delete($element, true, $request->get('message'));

        $dm->flush();

        return $this->returnResponse(true, $t->trans('action.element.delete.done'));
    }

    public function resolveReportsAction(Request $request, DocumentManager $dm,
                                         ConfigurationService $confService,
                                         ElementActionService $elementActionService,
                                         TranslatorInterface $t)
    {
        if (!$confService->isUserAllowed('directModeration', $request)) {
            return $this->returnResponse(false, $t->trans('action.element.resolveReports.unallowed'));
        }

        // CHECK REQUEST IS VALID
        if (!$request->get('elementId')) {
            return $this->returnResponse(false, $t->trans('action.element.resolveReports.uncomplete'));
        }

        $element = $dm->get('Element')->find($request->get('elementId'));

        $elementActionService->resolveReports($element, $request->get('comment'), true);

        $dm->persist($element);
        $dm->flush();

        return $this->returnResponse(true, $t->trans('action.element.resolveReports.done'));
    }

    public function sendMailAction(Request $request, DocumentManager $dm, ConfigurationService $confService,
                                   MailService $mailService, TranslatorInterface $t)
    {
        // CHECK REQUEST IS VALID
        if (!$request->get('elementId') || !$request->get('subject') || !$request->get('content') || !$request->get('userEmail')) {
            return $this->returnResponse(false, $t->trans('action.element.sendMail.uncomplete'));
        }

        $element = $dm->get('Element')->find($request->get('elementId'));

        $senderMail = $request->get('userEmail');

        // TODO make it configurable
        $emailSubject = $t->trans('action.element.sendMail.emailSubject', ['%instance%' => $this->getParameter('instance_name')]);
        $emailContent = $t->trans('action.element.sendMail.emailContent', ['%element%' => $element->getName(),
                                                                             '%sender%' => $senderMail,
                                                                             '%subject%' => $request->get('subject'),
                                                                             '%content%' => $request->get('content') ]);
        $mailService->sendMail($element->getEmail(), $emailSubject, $emailContent);

        return $this->returnResponse(true, $t->trans('action.element.sendMail.done'));
    }

    public function sendEditLinkAction($elementId, DocumentManager $dm, MailService $mailService, TranslatorInterface $t)
    {
        $element = $dm->get('Element')->find($elementId);
        $emailSubject = $t->trans('action.element.sendMail.emailSubject', ['%instance%' => $this->getParameter('instance_name')]);
        $emailContent = $t->trans('action.element.sendEditLink.emailContent');
        $emailContent = $mailService->replaceMailsVariables($emailContent, $element, '', 'edit-link', null);

        $mailService->sendMail($element->getEmail(), $emailSubject, $emailContent);

        $this->addFlash('success', $t->trans('action.element.sendEditLink.done', ['%email%' => $element->getEmail()]));
        return $this->redirectToRoute('gogo_homepage');
    }

    public function stampAction(Request $request, DocumentManager $dm, TranslatorInterface $t)
    {
        // CHECK REQUEST IS VALID
        if (!$request->get('stampId') || null === $request->get('value') || !$request->get('elementId')) {
            return $this->returnResponse(false, $t->trans('action.element.stamp.uncomplete'));
        }

        $element = $dm->get('Element')->find($request->get('elementId'));
        $stamp = $dm->get('Stamp')->find($request->get('stampId'));
        $user = $this->getUser();

        if (!in_array($stamp, $user->getAllowedStamps()->toArray())) {
            return $this->returnResponse(false, $t->trans('action.element.stamp.unallowed'));
        }

        if ('true' == $request->get('value')) {
            if (!in_array($stamp, $element->getStamps()->toArray())) {
                $element->addStamp($stamp);
            }
        } else {
            $element->removeStamp($stamp);
        }

        $dm->persist($element);
        $dm->flush();

        return $this->returnResponse(true, $t->trans('action.element.stamp.done'), $element->getStampIds() );
    }

    private function returnResponse($success, $message, $data = null)
    {
        $response['success'] = $success;
        $response['message'] = $message;
        if (null !== $data) {
            $response['data'] = $data;
        }

        $responseJson = json_encode($response);
        $response = new Response($responseJson);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
