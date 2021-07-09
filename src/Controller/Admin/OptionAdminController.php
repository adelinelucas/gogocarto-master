<?php

namespace App\Controller\Admin;

use Sonata\AdminBundle\Controller\CRUDController as Controller;

class OptionAdminController extends Controller
{
    public function listAction()
    {
        return $this->redirectToRoute('admin_app_category_list');
    }

    // overide CRUDController method to fix strange issue
    protected function redirectTo($object)
    {
        if ('DELETE' === $this->getRestMethod()) {
            return $this->redirectToList();
        }
        return $this->redirectToRoute('admin_app_option_edit', ['id' => $object->getId()]);
    }
}
