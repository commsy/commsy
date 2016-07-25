<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CategoryNewType extends AbstractType
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
            ->add('title', Types\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Title',
                'translation_domain' => 'category',
                'required' => true,
            ])

            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $category = $event->getData();
                $form = $event->getForm();

                // check if this is a "new" object
                if (!$category->getItemId()) {
                    $form->add('new', Types\SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button-primary',
                        ],
                        'label' => 'Create new category',
                        'translation_domain' => 'category',
                    ]);
                } else {
                    $form->add('update', Types\SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button-primary',
                        ],
                        'label' => 'Update category',
                        'translation_domain' => 'category',
                    ]);
                }
            })
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
        return 'category_new';
    }
}