<?php

namespace App\Admin;

class ConfigurationAbstractAdmin extends GoGoAbstractAdmin
{
    public function getTemplate($name)
    {
        switch ($name) {
            // overwrite edit template so we hide delete button in actions menu
            case 'edit': return 'admin/edit/edit_configuration.html.twig';
            break;
            default: return parent::getTemplate($name);
            break;
        }
    }
}
