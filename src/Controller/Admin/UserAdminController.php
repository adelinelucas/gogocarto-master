<?php

namespace App\Controller\Admin;

use App\Services\MailService;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserAdminController extends Controller
{
    public function __construct(MailService $mailService, TranslatorInterface $t)
    {
        $this->mailService = $mailService;
        $this->t = $t;
    }

    public function batchActionSendMail(ProxyQueryInterface $selectedModelQuery)
    {
        $selectedModels = $selectedModelQuery->execute();
        $nbreModelsToProceed = $selectedModels->count();
        $selectedModels->limit(5000);

        $request = $this->get('request_stack')->getCurrentRequest()->request;

        $mails = [];
        $usersWithoutEmail = 0;

        try {
            foreach ($selectedModels as $user) {
                $mail = $user->getEmail();
                if ($mail) {
                    $mails[] = $mail;
                } else {
                    ++$usersWithoutEmail;
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('sonata_flash_error', $this->trans('sonata.user.user.batch.error', [$e->getMessage()], 'admin'));

            return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
        }

        if (!$request->get('mail-subject') || !$request->get('mail-content')) {
            $this->addFlash('sonata_flash_error', $this->trans('sonata.user.user.batch.mailError', [], 'admin'));
        } elseif (count($mails) > 0) {
            $result = $this->mailService->sendMail(null, $request->get('mail-subject'), $request->get('mail-content'), $request->get('from'), $mails);
            if ($result['success']) {
                $this->addFlash('sonata_flash_success', $this->trans('sonata.user.user.batch.sendmails', ['%count%' => count($mails)], 'admin'));
            } else {
                $this->addFlash('sonata_flash_error', $result['message']);
            }
        }

        if ($usersWithoutEmail > 0) {
            $this->addFlash('sonata_flash_error', $this->trans('usersWithoutEmail', ['%count%' => $usersWithoutEmail], 'admin'));
        }

        $limit = 5000;
        if ($nbreModelsToProceed >= $limit) {
            $this->addFlash('sonata_flash_info', $this->trans('sonata.user.user.batch.tooMany', ['%limit%' => $limit], 'admin'));
        }

        return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
    }
}
