<?php

namespace App\Block;

use Doctrine\ODM\MongoDB\DocumentManager;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\CoreBundle\Model\Metadata;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class RecentElementsBlockService extends AbstractBlockService
{
    protected $manager;
    /**
     * @var Pool
     */
    private $adminPool;

    /**
     * @param string          $name
     * @param EngineInterface $templating
     * @param Pool            $adminPool
     */
    public function __construct(Environment $twig, DocumentManager $dm, Pool $adminPool = null, TranslatorInterface $t)
    {
        $this->manager = $dm;
        $this->adminPool = $adminPool;
        parent::__construct($twig);
        $this->t = $t;
    }

    private function trans($key, $params = [])
    {
        return $this->t->trans($key, $params, 'admin');
    }


    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $parameters = [
            'context' => $blockContext,
            'settings' => $blockContext->getSettings(),
            'block' => $blockContext->getBlock(),
            'results' => $this->manager->query('Element')
                            ->field('status')->equals($blockContext->getSettings()['filterStatus'])
                            ->sort('updatedAt', 'DESC')
                            ->limit($blockContext->getSettings()['number'])
                            ->getQuery()
                            ->execute(),
            'admin_pool' => $this->adminPool,
        ];

        return $this->renderResponse($blockContext->getTemplate(), $parameters, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'number' => 5,
            'title' => $this->trans('elements.recent_elements'),
            'class' => '',
            'filterStatus' => 0,
            'template' => 'admin/blocks/block_recent_elements.html.twig',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (!is_null($code) ? $code : $this->getName()), false, 'BiopenGeoDirectoryBundle', [
            'class' => 'fa fa-pencil',
        ]);
    }
}
