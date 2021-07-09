<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin as SonataAbstractAdmin;
use Sonata\AdminBundle\Translator\LabelTranslatorStrategyInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GoGoAbstractAdmin extends SonataAbstractAdmin 
{
    public function getTranslationDomain()
    {
        return 'admin';
    }

    // as $translator is being remove from Admin class in version 4.0, we keep it ourself
    protected $gogoTranslator = null;

    public function setGoGoTranslator(TranslatorInterface $translator)
    {
        $this->gogoTranslator = $translator;
    }

    public function t_exists($id, $domain = null)
    {
        return $this->t($id, [], $domain) != $id;
    }

    public function t($id, array $parameters = [], $domain = null, $locale = null)
    {
        $domain = $domain ?: $this->getTranslationDomain();

        return $this->gogoTranslator->trans($id, $parameters, $domain, $locale);
    }

    public function t_label($label, $context = '', $type = '', $parameters = [], $domain = null, $locale = null)
    {
        $label = $this->getLabelTranslatorStrategy()->getLabel($label, $context, $type);
        return $this->t($label, $parameters, $domain, $locale);
    }

    // Overide to use GoGoFormMapper
    public function defineFormBuilder(FormBuilderInterface $formBuilder)
    {
        $mapper = new GoGoFormMapper($this->getFormContractor(), $formBuilder, $this);
        $this->configureFormFields($mapper);

        foreach ($this->getExtensions() as $extension) {
            $extension->configureFormFields($mapper);
        }

        $this->attachInlineValidator();
    }

    // overide to use GoGoLabelStrategy
    public function setLabelTranslatorStrategy(LabelTranslatorStrategyInterface $labelTranslatorStrategy)
    {
        $this->labelTranslatorStrategy = new GoGoLabelStrategy($this->code, $this);
    }

    
}