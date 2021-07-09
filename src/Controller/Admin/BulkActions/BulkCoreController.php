<?php

namespace App\Controller\Admin\BulkActions;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;

class BulkCoreController extends Controller
{
    public function indexAction()
    {
        return $this->render('admin/pages/bulks/bulk_index.html.twig');
    }
}
