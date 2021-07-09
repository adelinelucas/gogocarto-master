<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategoryLiteType extends AbstractType
{
    public function __construct(TranslatorInterface $t)
    {
        $this->t = $t;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['required' => true, 'label' => $this->t->trans('categories.fields.name', [], 'admin')])
            ->add('index', null, ['required' => false, 'label' => $this->t->trans('categories.fields.index', [], 'admin')]) 
            ->add('pickingOptionText', null, ['required' => true, 'label' => $this->t->trans('options.fields.pickingOptionText', [], 'admin')])
            ->add('id', null, ['required' => false, 'label' => $this->t->trans('categories.fields.option.id', [], 'admin'), 'attr' => ['class' => 'gogo-route-id', 'data-route-id' => 'admin_app_category_edit']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
          'data_class' => 'App\Document\Category',
      ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'gogo_form_category_lite';
    }
}
