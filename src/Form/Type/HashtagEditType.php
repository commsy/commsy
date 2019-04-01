<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class HashtagEditType extends AbstractType
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
        $builder
            ->add('name', Types\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Name',
                'translation_domain' => 'hashtag',
                'required' => true,
            ])

            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $hashtag = $event->getData();
                $form = $event->getForm();

                // check if this is a "new" object
                if (!$hashtag->getItemId()) {
                    $form->add('new', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-primary',
                        ),
                        'label' => 'Create new hashtag',
                        'translation_domain' => 'hashtag',
                    ]);
                } else {
                    $form
                        ->add('update', Types\SubmitType::class, [
                            'attr' => array(
                                'class' => 'uk-button-primary',
                            ),
                            'label' => 'Update hashtag',
                            'translation_domain' => 'hashtag',
                        ])
                        ->add('delete', Types\SubmitType::class, [
                            'attr' => array(
                                'class' => 'uk-button-danger',
                            ),
                            'label' => 'Delete hashtag',
                            'translation_domain' => 'hashtag',
                            'validation_groups' => false,   // disable validation
                        ])
                    ;
                }
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
        return 'hashtag_edit';
    }
}