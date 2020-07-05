<?php
namespace App\Form\Type\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class LicenseType extends AbstractType
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
            ->add('title', Types\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Title',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('content', CKEditorType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Content',
                'required' => true,
                'translation_domain' => 'portal',
                'attr' => array(
                    'class' => 'uk-form-width-large',
                    'style' => 'width: 100%;',
                ),
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            $license = $event->getData();
            $form = $event->getForm();

            // check if this is a "new" object
            if (!$license->getId()) {
                $form->add('new', Types\SubmitType::class, [
                    'label' => 'Create new license',
                    'translation_domain' => 'portal',
                ]);
            } else {
                $form
                    ->add('update', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-primary',
                        ),
                        'label' => 'Update license',
                        'translation_domain' => 'portal',
                    ]);
                $form
                    ->add('cancel', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-secondary',
                        ),
                        'label' => 'Cancel',
                        'translation_domain' => 'portal',
                    ]);
            }
        });
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'portal',
        ]);
    }
}
