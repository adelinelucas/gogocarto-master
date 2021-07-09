<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username')
              ->add('email', EmailType::class, ['required' => true])
              ->add('newsletterFrequency', ChoiceType::class, [
                   'choices' => [
                        'commons.every.week' => 1,
                        'commons.every.month' => 2,
                        'commons.never' => 0,
                   ],
                   'expanded' => true,  'multiple' => false,
                   'required' => false, 'placeholder' => false, ])
              ->add('location', TextType::class, ['required' => false])
              ->add('newsletterRange', RangeType::class, [
                   'required' => false,
                   'attr' => [
                       'min' => 1,
                       'max' => 250,
                   ],
                  ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
          'data_class' => 'App\Document\User',
      ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'gogo_corebundle_user_profile';
    }
}
