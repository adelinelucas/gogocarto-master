<?php

namespace App\Admin;

use Sonata\AdminBundle\Translator\LabelTranslatorStrategyInterface;

class GoGoLabelStrategy implements LabelTranslatorStrategyInterface
{
    public function __construct($code, $admin)
    {
        $this->code = str_replace('admin.', '', $code);
        $this->admin = $admin;
    }

    public function getLabel($label, $context = '', $type = '')
    {   
        // Fix Label
        if ($context == 'breadcrumb') {
            $exploded = explode('_', $label);
            $label = array_pop($exploded);
        }
        // Fix type
        if (in_array($type, ['label', 'link', '', null]))
            $type = '';
        else
            $type = "_$type";
        // Special case for breadcrumb, fallback to main label
        if ($context == 'breadcrumb' && $label == 'list')
            $defaultLabel = "{$this->code}._label";
        // Default label inside the fields section (used for list, form, filters etc...)
        else
            $defaultLabel = "{$this->code}.fields.$label$type";
        
        $contextLabel = "{$this->code}.$context.$label$type";

        if ($this->admin->t_exists($contextLabel)) 
            return $contextLabel;
        else if ($this->admin->t_exists($defaultLabel))
            return $defaultLabel;
        else {
            // return native value
            $label = str_replace(['_', '.'], ' ', $label);
            $label = strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $label));
            return trim(ucwords(str_replace('_', ' ', $label)));
        }
    }
}