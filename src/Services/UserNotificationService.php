<?php

namespace App\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserNotificationService
{
    public function __construct(DocumentManager $dm, MailService $mailService,
                                UrlService $urlService, TranslatorInterface $t)
    {
        $this->dm = $dm;
        $this->mailService = $mailService;
        $this->urlService = $urlService;
        $this->t = $t;
    }

    function sendModerationNotifications()
    {
        $users = $this->dm->get('User')->findByWatchModeration(true);
        $usersNotified = 0;
        foreach ($users as $user) {
            $elementsCount = $this->dm->get('Element')
                                  ->findModerationElementToNotifyToUser($user);
            if ($elementsCount > 0) {
                // strange bug, if using doctrine we get the config of the root project... so using MongoClient instead
                // Update: I think the bug does not occurs anymore...
                $config = $this->dm->getCollection('Configuration')->findOne();
                $subject = $this->t->trans('notifications.moderation.subject', [ 'appname' => $config['appName'] ]);
                $content = $this->t->trans('notifications.moderation.content', [
                    'count' => $elementsCount,
                    'element_singular' => $config['elementDisplayName'],
                    'element_plural' => $config['elementDisplayNamePlural'],
                    'appname' => $config['appName'],
                    'url' => $this->urlService->generateUrlFor($config['dbName'], 'gogo_directory'),
                    'edit_url' => $this->urlService->generateUrlFor($config['dbName'], 'admin_app_user_edit', ['id' => $user->getId()])
                ]);
                $this->mailService->sendMail($user->getEmail(), $subject, $content);
                $usersNotified++;
            }
        }   
        return $usersNotified; 
    }

    function notifyImportError($import)
    {
        if (!$import->isDynamicImport()) return;
        foreach($import->getUsersToNotify() as $user) {
            $config = $this->dm->get('Configuration')->findConfiguration();
            $subject = $this->t->trans('notifications.import_error.subject', [ 'appname' => $config['appName'] ]);
            $content = $this->t->trans('notifications.import_error.content', [ 
                'import' => $import->getSourceName(),
                'url' => $this->urlService->generateUrlFor($config, 'admin_app_import_edit', ['id' => $import->getId()])
            ]);
            $this->mailService->sendMail($user->getEmail(), $subject, $content);
        }
    }

    function notifyImportMapping($import)
    {
        if (!$import->isDynamicImport()) return;
        foreach($import->getUsersToNotify() as $user) {
            $config = $this->dm->get('Configuration')->findConfiguration();
            $subject = $this->t->trans('notifications.import_mapping.subject', [ 'appname' => $config['appName'] ]);
            $content = $this->t->trans('notifications.import_mapping.content', [
                'import' => $import->getSourceName(),
                'url' => $this->urlService->generateUrlFor($config, 'admin_app_import_edit', ['id' => $import->getId()])
            ]);
            $this->mailService->sendMail($user->getEmail(), $subject, $content);
        }
    }
}