<?php
namespace CommsyBundle\Form\Type;

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
                    $label = 'Create new hashtag';
                } else {
                    $label = 'Update hashtag';
                }

                $form->add('save', Types\SubmitType::class, [
                    'attr' => array(
                        'class' => 'uk-button-primary',
                    ),
                    'label' => $label,
                    'translation_domain' => 'hashtag',
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
        return 'hashtag_edit';
    }
}