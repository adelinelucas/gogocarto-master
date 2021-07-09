<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpenHoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('Monday', DailyTimeSlotType::class)
              ->add('Tuesday', DailyTimeSlotType::class)
              ->add('Wednesday', DailyTimeSlotType::class)
              ->add('Thursday', DailyTimeSlotType::class)
              ->add('Friday', DailyTimeSlotType::class)
              ->add('Saturday', DailyTimeSlotType::class)
              ->add('Sunday', DailyTimeSlotType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
          'data_class' => 'App\Document\OpenHours',
      ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'gogo_elementbundle_openhours';
    }
}
