<?php

namespace App\Services;

use App\Document\Configuration;
use App\Document\Element;
use App\Document\News;
use App\Document\User;
use App\Document\UserInteractionReport;
use Doctrine\ODM\MongoDB\DocumentManager;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailService
{
    protected $dm;
    protected $config;
    private $newsRepository;
    protected $mailer;
    protected $twig;
    protected $baseUrl;
    protected $email;
    protected $instanceName;

    public function __construct(DocumentManager $dm, \Swift_Mailer $mailer, Environment $twig, 
                                UrlService $urlService,
                                $fromEmail, $instanceName, TranslatorInterface $t)
    {
        $this->dm = $dm;
        $this->newsRepository = $this->dm->getRepository(News::class);
        $this->mailer = $mailer;
        $this->urlService = $urlService;
        $this->twig = $twig;
        $this->email = $fromEmail;
        $this->instanceName = $instanceName;
        $this->t = $t;
    }

    private function trans($key, $params = [])
    {
        return $this->t->trans($key, $params, 'admin');
    }

    public function sendMail($to, $subject, $content, $from = null, $toBCC = null)
    {
        if (!$from) {
            $from = [$this->email => $this->instanceName];
        }
        try {
            $draftedContent = $this->draftTemplate($content);

            $message = (new \Swift_Message())
            ->setFrom($from)
            ->setSubject($subject)
            ->setBody(
                $draftedContent,
                'text/html'
            );

            if ($to) {
                $message->setTo($to);
            }
            if ($toBCC) {
                $message->setBcc($toBCC);
            }

            $this->mailer->send($message);
        } catch (\Swift_RfcComplianceException $e) {
            $error = $this->trans('emails.service.error').$e->getMessage();

            return ['success' => false, 'message' => $error];
        }
        
        return ['success' => true, 'message' => $this->trans('emails.service.success')];
    }

    public function sendAutomatedMail($mailType, $element, $customMessage = null, $option = null)
    {
        if ($element instanceof Element && $element->isDynamicImported()) {
            return;
        } // do not send email to dynamically imported elements

        if (!$customMessage) {
            $customMessage = $this->trans('emails.service.no_specific_message');
        }
        $mailConfig = $this->getAutomatedMailConfigFromType($mailType);
        if (!$mailConfig) {
            return [ 'success' => false, 'message' => $this->trans('emails.service.unknown_config', ['%config%' => $mailType]) ];
        }
        if (!$mailConfig->getActive()) {
            return [ 'success' => false, 'message' => $this->trans('emails.service.inactive_config', ['%config%' => $mailType]) ];
        }

        $draftResponse = $this->draftEmail($mailType, $element, $customMessage, $option);

        if ($draftResponse['success']) {
            if (in_array($mailType, ['validation', 'refusal'])) {
                $mailTo = $element->getCurrContribution() ? $element->getCurrContribution()->getUserEmail() : null;
            } elseif ('report' == $mailType && $option && $option instanceof UserInteractionReport) {
                $mailTo = $option->getUserEmail();
            } else {
                $mailTo = $element->getEmail();
            }

            if ($mailTo && 'no email' != $mailTo) {
                return $this->sendMail($mailTo, $draftResponse['subject'], $draftResponse['content']);
            } else {
                return ['success' => false, 'message' => $this->trans('emails.service.no_email')];
            }
        } else {
            return $draftResponse;
        }
    }

    public function draftEmail($mailType, $element, $customMessage, $option = null)
    {
        $mailConfig = $this->getAutomatedMailConfigFromType($mailType);

        if (null == $mailConfig) {
            return [ 'success' => false, 'message' => $this->trans('emails.service.no_automatic_mail', ['%config%' => $mailType]) ];
        }

        $subject = $mailConfig->getSubject();
        $content = $mailConfig->getContent();

        if (!$mailConfig->getSubject() || !$mailConfig->getContent()) {
            return [ 'success' => false, 'message' => $this->trans('emails.service.no_subject_or_content', ['%config%' => $mailType]) ];
        }

        if ('newsletter' == $mailType) {
            $content = $this->replaceNewElementsList($content, $option, $element);
        }

        $subject = $this->replaceMailsVariables($subject, $element, $customMessage, $mailType, $option);
        $content = $this->replaceMailsVariables($content, $element, $customMessage, $mailType, $option);

        return ['success' => true, 'subject' => $subject, 'content' => $content];
    }

    public function draftTemplate($content, $template = 'base')
    {
        return $this->twig->render('emails/layout.html.twig', [
            'content' => $content, 
            'config' => $this->getConfig(), 
            'homeUrl' => $this->urlService->generateUrl('gogo_homepage')]);
    }

    public function getConfig()
    {
        if (!$this->config) $this->config = $this->dm->get('Configuration')->findConfiguration();
        return $this->config;
    }

    public function getAutomatedMailConfigFromType($mailType)
    {
        $mailConfig = null;

        switch ($mailType) {
            case 'add':            $mailConfig = $this->getConfig()->getAddMail(); break;
            case 'edit':           $mailConfig = $this->getConfig()->getEditMail(); break;
            case 'delete':         $mailConfig = $this->getConfig()->getDeleteMail(); break;
            case 'validation':     $mailConfig = $this->getConfig()->getValidationMail(); break;
            case 'refusal':        $mailConfig = $this->getConfig()->getRefusalMail(); break;
            case 'report':         $mailConfig = $this->getConfig()->getReportResolvedMail(); break;
            case 'newsletter':     $mailConfig = $this->getConfig()->getNewsletterMail(); break;
        }

        return $mailConfig;
    }

    public function replaceMailsVariables($string, $element = null, $customMessage, $mailType, $option)
    {
        if (null !== $element && $element) {
            if ($element instanceof Element) {
                $showElementUrl = $this->urlService->elementShowUrl($element->getId());
                $editElementUrl = $this->urlService->generateUrl('gogo_element_edit', ['id' => $element->getId()]);
                $elementName = $element->getName();
                $contribution = $element->getCurrContribution();
                $directEditElementUniqueUrl = $this->urlService->generateUrl('gogo_element_edit', ['id' => $element->getId(), 'hash' => $element->getRandomHash()]);

                if ('report' == $mailType && $option && $option instanceof UserInteractionReport) {
                    $user = $option->getUserDisplayName();
                } else {
                    $user = $contribution ? $contribution->getUserDisplayName() : $this->trans('emails.service.unknown');
                }

                $string = preg_replace('/({{((?:\s)+)?element((?:\s)+)?}})/i', $elementName, $string);
                $string = preg_replace('/({{((?:\s)+)?user((?:\s)+)?}})/i', $user, $string);
                $string = preg_replace('/({{((?:\s)+)?customMessage((?:\s)+)?}})/i', $customMessage, $string);
                $string = preg_replace('/({{((?:\s)+)?showUrl((?:\s)+)?}})/i', $showElementUrl, $string);
                $string = preg_replace('/({{((?:\s)+)?editUrl((?:\s)+)?}})/i', $editElementUrl, $string);
                $string = preg_replace('/({{((?:\s)+)?directEditElementUniqueUrl((?:\s)+)?}})/i', $directEditElementUniqueUrl, $string);
            } elseif ($element instanceof User) {
                $string = preg_replace('/({{((?:\s)+)?user((?:\s)+)?}})/i', $element->getDisplayName(), $string);
            }
        }

        if ('newsletter' === $mailType && $element instanceof User) {
            $lastNews = $this->newsRepository->findLastPublishedNews($element->getLastNewsletterSentAt());
            $content = '';
            foreach ($lastNews as $news) {
                $content .= $this->twig->render('emails/news.html.twig',
                    ['news' => $news, 'config' => $this->getConfig()]);
            }
            $string = preg_replace('/({{((?:\s)+)?news((?:\s)+)?}})/i', $content, $string);
        }

        $homeUrl = $this->urlService->generateUrl('gogo_homepage');
        $userContributionsUrl = $this->urlService->generateUrl('gogo_user_contributions');
        $userProfileUrl = $this->urlService->generateUrl('gogo_user_profile');

        $string = preg_replace('/({{((?:\s)+)?homeUrl((?:\s)+)?}})/i', $homeUrl, $string);
        $string = preg_replace('/({{((?:\s)+)?customMessage((?:\s)+)?}})/i', $customMessage, $string);
        $string = preg_replace('/({{((?:\s)+)?userContributionsUrl((?:\s)+)?}})/i', $userContributionsUrl, $string);
        $string = preg_replace('/({{((?:\s)+)?userProfileUrl((?:\s)+)?}})/i', $userProfileUrl, $string);

        $string = str_replace('http://http://', 'http://', $string);
        $string = str_replace('http://', 'https://', $string);
        $string = str_replace('https://https://', 'https://', $string);

        return $string;
    }

    private function replaceNewElementsList($string, $elements, $user)
    {
        if (!is_array($elements)) {
            $elements = $elements->toArray();
        }

        $pendingElements = array_filter($elements, function ($el) { return $el->isPending(); });
        $newElements = array_filter($elements, function ($el) { return !$el->isPending(); });

        $pendingElementsHtml = $this->twig->render('emails/newsletter-new-elements.html.twig',
            ['elements' => $pendingElements, 'config' => $this->getConfig(), 'baseUrl' => $this->baseUrl]
        );

        $newElementsHtml = $this->twig->render('emails/newsletter-new-elements.html.twig',
            ['elements' => $newElements, 'config' => $this->getConfig(), 'baseUrl' => $this->baseUrl]
        );

        $showOnMapBtnHtml = $this->twig->render('emails/newsletter-show-on-map-button.html.twig',
            ['config' => $this->getConfig(), 'user' => $user, 'directoryUrl' => $this->urlService->generateUrl('gogo_directory')]
        );

        $string = preg_replace('/({{((?:\s)+)?pendingElements((?:\s)+)?}})/i', $pendingElementsHtml, $string);
        $string = preg_replace('/({{((?:\s)+)?newElements((?:\s)+)?}})/i', $newElementsHtml, $string);
        $string = preg_replace('/({{((?:\s)+)?showOnMapBtn((?:\s)+)?}})/i', $showOnMapBtnHtml, $string);

        return $string;
    }
}
