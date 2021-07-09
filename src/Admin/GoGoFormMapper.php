<?php

namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;

class GoGoFormMapper extends FormMapper
{   
    // Override to create magic shortcuts : if translation exists with suffix _hint or _help, we directly
    // Adds it to the config
    public function add($name, $type = null, array $options = [], array $fieldDescriptionOptions = [])
    {
        if (!isset($options['required'])) {
            $options['required'] = false;
        }
        
        if (!isset($options['label'])) {
            $options['label'] = $this->admin->getLabelTranslatorStrategy()->getLabel($name, 'form', 'label');
        }
        if (isset($options['label_trans_params'])) {
            $options['label'] = $this->admin->t($options['label'], $options['label_trans_params'] ?? []); 
            unset($options['label_trans_params']);
        }

        // Handles shortcuts like "_help"
        foreach(['help', 'hint', 'placeholder'] as $key) {
            $labelKey = $this->admin->getLabelTranslatorStrategy()->getLabel($name, 'form', $key);
            if ($this->admin->t_exists($labelKey)) {
                $options[$key] = $this->admin->t($labelKey, $options["{$key}_trans_params"] ?? []); 
            } 
            unset($options["{$key}_trans_params"]); 
        }

        if (isset($options['placeholder']) && $type !== ModelType::class) {
            $options['attr']['placeholder'] = $options['placeholder'];
            unset($options['placeholder']);
        }

        if (isset($options['help'])) {
            $options['label_attr']['title'] = $options['help'];
            unset($options['help']);
        }               
        if (isset($options['hint'])) {
            $options['help'] = $options['hint'];
            unset($options['hint']);                
        }

        if (isset($options['choices'])) {
            $newChoices = [];
            foreach($options['choices'] as $key => $value) {
                $labelKey = $this->admin->getLabelTranslatorStrategy()->getLabel($name, 'form', "choices.$key");
                if ($this->admin->t_exists($labelKey)) $key = $this->admin->t($labelKey);
                $valueKey = $this->admin->getLabelTranslatorStrategy()->getLabel($name, 'form', "choices.$value");
                if ($this->admin->t_exists($valueKey)) $value = $this->admin->t($valueKey);
                $newChoices[$key] = $value;
            }
            $options['choices'] = $newChoices;
        }

        return parent::add($name, $type, $options, $fieldDescriptionOptions);
    }

    // Override to add custom translation
    public function with($name, array $options = [])
    {
        // Handles shortcuts like "_help"
        foreach(['description'] as $key) {
            $labelKey = $this->admin->getLabelTranslatorStrategy()->getLabel($name, 'form.groups', $key);
            if ($this->admin->t_exists($labelKey)) {
                $options[$key] = $this->admin->t($labelKey, $options["{$key}_trans_params"] ?? []); 
                unset($options["{$key}_trans_params"]);
            }  
        }

        if ($name != 'default') {
            if ($this->admin->t_exists($name))
                $name = $this->admin->t($name, $options["label_trans_params"] ?? []);
            else
                $name = $this->admin->t_label($name, 'form.groups', '', $options["label_trans_params"] ?? []);
        }
        return parent::with($name, $options);        
    }

    // Adds some shortcuts
    public function panel($name, array $options = [])
    {
        return $this->with($name, $options);
    }

    public function panelDefault($name, array $options = [])
    {
        $options['box_class'] = 'box box-default';
        return $this->with($name, $options);
    }

    public function panelDanger($name, array $options = [])
    {
        $options['box_class'] = 'box box-danger';
        return $this->with($name, $options);
    }

    public function halfPanel($name, array $options = [])
    {
        $options['class'] = ($options['class'] ?? '') . ' col-md-6';
        return $this->with($name, $options);
    }

    public function halfPanelDefault($name, array $options = [])
    {
        $options['class'] = ($options['class'] ?? '') . ' col-md-6';
        return $this->panelDefault($name, $options);
    }

    public function halfPanelDanger($name, array $options = [])
    {
        $options['class'] = ($options['class'] ?? '') . ' col-md-6';
        return $this->panelDanger($name, $options);
    }
}
