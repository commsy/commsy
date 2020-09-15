<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

class TimePulsesType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('showTimePulses', Types\ChoiceType::class, [
                'label' => 'Show time pulses',
                'expanded' => true,
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'choice_translation_domain' => 'form',
                'help' => 'Show time pulses help text',
            ])
            ->add('timePulseNameGerman', Types\TextType::class, [
                'label' => 'Time pulses name',
                'attr' => array(
                    'placeholder' => 'de'
                ),
                'required' => false,
            ])
            ->add('timePulseNameEnglish', Types\TextType::class, [
                'label' => false,
                'attr' => array(
                    'placeholder' => 'en'
                ),
                'required' => false,
            ])
            ->add('numberOfFutureTimePulses', Types\ChoiceType::class,[
                'label' => 'Number of future time pulses',
                'expanded' => false,
                'choices'  => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                    '9' => 9,
                ],
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => Portal::class,
                'translation_domain' => 'portal',
            ]);
    }
}
