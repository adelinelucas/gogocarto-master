<?php

namespace App\Form;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElementType extends AbstractType
{
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['required' => false])
            ->add('email', TextType::class, ['required' => false])
            ->add('fullAddress', TextType::class, ['mapped' => false])
            ->add('address', PostalAddressType::class)
            ->add('geo', CoordinatesType::class)
            ->add('files', CollectionType::class, [
                        'entry_type' => ElementFileType::class,
                        'allow_add' => true,
                        'allow_delete' => true,
                        'label' => '',
                    ])
            ->add('images', CollectionType::class, [
                        'entry_type' => ElementImageType::class,
                        'allow_add' => true,
                        'allow_delete' => true,
                        'label' => '',
                    ])
            ->add('openHours', OpenHoursType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
          'data_class' => 'App\Document\Element',
      ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'gogo_elementbundle_element';
    }
}
