<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DailyTimeSlotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('slot1start', TimeType::class, [
                            'widget' => 'single_text',
                            'required' => 'false', ])
              ->add('slot1end', TimeType::class, [
                            'widget' => 'single_text',
                            'required' => 'false', ])
              ->add('slot2start', TimeType::class, [
                            'widget' => 'single_text',
                            'required' => 'false', ])
              ->add('slot2end', TimeType::class, [
                            'widget' => 'single_text',
                            'required' => 'false', ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
          'data_class' => 'App\Document\DailyTimeSlot',
      ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'gogo_elementbundle_dayhoraire';
    }
}
