<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class TranslationType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     * 
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $form = $event->getForm();
                $form
                    ->add('translationDe', Types\TextareaType::class, [
                        'constraints' => [
                            new Constraints\NotBlank(),
                        ],
                        'label' => 'Translation german',
                        'required' => true,
                        'translation_domain' => 'translation',
                    ]);
                $form
                    ->add('translationEn', Types\TextareaType::class, [
                        'constraints' => [
                            new Constraints\NotBlank(),
                        ],
                        'label' => 'Translation english',
                        'required' => true,
                        'translation_domain' => 'translation',
                    ]);
                $form
                    ->add('update', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-primary',
                        ),
                        'label' => 'Update translation',
                        'translation_domain' => 'translation',
                    ]);
                $form
                    ->add('cancel', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-secondary',
                        ),
                        'label' => 'Cancel',
                        'translation_domain' => 'translation',
                    ]);
            });
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
            ->setRequired([])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     * 
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'translation';
    }
}