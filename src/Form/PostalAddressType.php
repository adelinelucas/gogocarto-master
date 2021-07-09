<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostalAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('streetAddress', HiddenType::class)
      ->add('addressLocality', HiddenType::class)
      ->add('postalCode', HiddenType::class)
      ->add('addressCountry', HiddenType::class)
      ->add('customFormatedAddress', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => 'App\Document\PostalAddress',
    ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'gogo_elementbundle_postal_address';
    }
}
