<?php

namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CommsyBundle\Form\Type\Event\AddContextFieldListener;

use CommsyBundle\Entity\Room;

class ContextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'uk-form-width-large',
                ],
            ])
            ->add('type_select', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['types'],
                'label' => 'context type',
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'translation_domain' => 'room',
            ))
            ->addEventSubscriber(new AddContextFieldListener())
            ->add('language', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => ['de' => 'de', 'en' => 'en'],
                'label' => 'language',
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'translation_domain' => 'room',
            ))
            ->add('room_description', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => 'Room description...',
                ],
                'required' => false,
                'translation_domain' => 'room',
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
                'translation_domain' => 'form',
            ])
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
            ));
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([
                'types',
                'templates',
                'preferredChoices',
                'timesDisplay',
                'times',
                'communities',
                'linkCommunitiesMandantory',
            ])
            ->setDefaults([
                'translation_domain' => 'project',
            ]);
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
        return 'context';
    }
}