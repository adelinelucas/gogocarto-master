<?php

namespace App\Block;

use App\Document\NewsletterFrequencyOptions;
use Doctrine\ODM\MongoDB\DocumentManager;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Validator\ErrorElement;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class MonitoringElementsBlockService extends AbstractBlockService
{
    protected $dm;

    public function __construct(Environment $twig, DocumentManager $dm)
    {
        $this->dm = $dm;
        parent::__construct($twig);
    }

    public function getName()
    {
        return 'Monitoring';
    }

    public function getDefaultSettings()
    {
        return [];
    }

    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
    }

    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
    }

    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $pendings = $this->dm->get('Element')->findPendings(true);
        $moderationNeeded = $this->dm->get('Element')->findModerationNeeded(true);
        $duplicatesCount = $this->dm->query('Element')->field('isDuplicateNode')->equals(true)->getCount();
        $allVisibleElements = $this->dm->get('Element')->findVisibles(true, false);
        $visibleNonImportedElements = $this->dm->get('Element')->findVisibles(true, true);
        $activeUsersCount = $this->dm->query('User')->field('enabled')->equals(true)->count()->execute();
        $activeUsersNewsletterCount = $this->dm->query('User')->field('enabled')->equals(true)
                                            ->field('newsletterFrequency')->gt(NewsletterFrequencyOptions::Never)->count()->execute();

        $errors = $this->dm->get('GoGoLog')->findBy(['level' => 'error', 'hidden' => false]);
        usort($errors, function ($a, $b) { return $b->getCreatedAt()->getTimestamp() - $a->getCreatedAt()->getTimestamp(); });

        $messages = $this->dm->get('GoGoLog')->findBy(['type' => 'update', 'hidden' => false]);
        usort($errors, function ($a, $b) { return $b->getCreatedAt()->getTimestamp() - $a->getCreatedAt()->getTimestamp(); });

        // merge settings
        $settings = $blockContext->getSettings();

        return $this->renderResponse('admin/blocks/block_monitoring.html.twig', [
            'block' => $blockContext->getBlock(),
            'settings' => $settings,
            'pendingCount' => $pendings,
            'moderationNeededCount' => $moderationNeeded,
            'duplicatesCount' => $duplicatesCount,
            'allVisibleCount' => $allVisibleElements,
            'visibleNonImportedCount' => $visibleNonImportedElements,
            'activeUsersCount' => $activeUsersCount,
            'activeUsersNewsletterCount' => $activeUsersNewsletterCount,
            'errors' => $errors,
            'messages' => $messages,
        ], $response);
    }
}
