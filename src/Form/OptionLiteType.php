<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OptionLiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['required' => true])
            ->add('index')
            ->add('color', null, ['attr' => ['class' => 'gogo-color-picker']])
            ->add('icon', null, ['attr' => ['class' => 'gogo-icon-picker']])
            ->add('id', null, ['attr' => ['class' => 'gogo-route-id', 'data-route-id' => 'admin_app_option_edit']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
          'data_class' => 'App\Document\Option',
      ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'gogo_form_option_lite';
    }
}
