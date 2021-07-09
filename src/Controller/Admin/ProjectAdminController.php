<?php

namespace App\Controller\Admin;

use Doctrine\ODM\MongoDB\DocumentManager;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectAdminController extends Controller
{
    public function __construct(DocumentManager $dm, LoggerInterface $projectsLogger, TokenStorageInterface $securityContext, TranslatorInterface $t)
    {
        $this->dm = $dm;
        $this->logger = $projectsLogger;
        $this->security = $securityContext;
        $this->t = $t;
    }

    /**
     * Delete action.
     *
     * @param int|string|null $id
     *
     * @return Response|RedirectResponse
     *
     * @throws NotFoundHttpException If the object does not exist
     * @throws AccessDeniedException If access is not granted
     */
    public function deleteAction($id)
    {
        $request = $this->getRequest();
        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id : %s', $id));
        }
        $this->admin->checkAccess('delete', $object);

        $preResponse = $this->preDelete($request, $object);
        if (null !== $preResponse) {
            return $preResponse;
        }

        if ('DELETE' == $this->getRestMethod()) {
            // check the csrf token
            $this->validateCsrfToken('sonata.delete');
            $objectName = $this->admin->toString($object);

            try {
                $this->admin->delete($object);
                $this->dropDatabase($object);
                $this->addFlash('sonata_flash_success', $this->trans('flash_delete_success', ['%name%' => $this->escapeHtml($objectName)], 'SonataAdminBundle'));
            } catch (\Sonata\AdminBundle\Exception\ModelManagerException $e) {
                $this->handleModelManagerException($e);
                $this->addFlash('sonata_flash_error', $this->trans('flash_delete_error', ['%name%' => $this->escapeHtml($objectName)], 'SonataAdminBundle'));
            }

            return $this->redirectTo($object);
        }

        return $this->render($this->admin->getTemplate('delete'), [
            'object' => $object,
            'action' => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.delete'),
        ], null);
    }

    private function dropDatabase($project)
    {
        $user = $this->security->getToken()->getUser();
        $this->logger->info("FROM MAIN PROJECT : Project {$project->getDbName()} being deleted by {$user->getUsername()}");
        // Drop the database of this project
        $mongo = $this->dm->getConnection()->getMongo();
        $mongo->selectDB($project->getDbName())->command(['dropDatabase' => 1]);
    }

    /**
     * Execute a batch delete.
     *
     * @return RedirectResponse
     *
     * @throws AccessDeniedException If access is not granted
     */
    public function batchActionDelete(ProxyQueryInterface $query)
    {
        $this->admin->checkAccess('batchDelete');

        try {
            $this->batchDelete($this->admin->getClass(), $query);
            $this->addFlash('sonata_flash_success', 'flash_batch_delete_success');
        } catch (Exception $e) {
            $this->addFlash('sonata_flash_error', 'flash_batch_delete_error');
        }

        return new RedirectResponse($this->admin->generateUrl(
            'list',
            ['filter' => $this->admin->getFilterParameters()]
        ));
    }

    private function batchDelete($class, ProxyQueryInterface $queryProxy)
    {
        $queryBuilder = $queryProxy->getQuery();

        $i = 0;
        foreach ($queryBuilder->execute() as $object) {
            $this->dm->remove($object);
            $this->dropDatabase($object);

            if (0 == (++$i % 20)) {
                $this->dm->flush();
                $this->dm->clear();
            }
        }

        $this->dm->flush();
        $this->dm->clear();
    }
}
