<?php

namespace App\Controller\Admin;

use Doctrine\ODM\MongoDB\DocumentManager;
use Sonata\AdminBundle\Controller\CRUDController as Controller;

class CategoryAdminController extends Controller
{
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function listAction()
    {
        return $this->treeAction();
    }

    public function treeAction()
    {
        $this->admin->checkAccess('list');
        $config = $this->dm->get('Configuration')->findConfiguration();
        $rootCategories = $this->dm->get('Category')->findRootCategories();

        return $this->render('admin/list/tree_category.html.twig', [
            'categories' => $rootCategories, 'config' => $config,
        ], null);
    }

    // overide CRUDController method to fix strange issue
    protected function redirectTo($object)
    {
        if ('DELETE' === $this->getRestMethod()) {
            return $this->redirectToList();
        }
        return $this->redirectToRoute('admin_app_category_edit', ['id' => $object->getId()]);
    }
}
