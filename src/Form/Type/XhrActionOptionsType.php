<?php
namespace App\Form\Type;

use App\Form\Type\Custom\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class XhrActionOptionsType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('choices', Select2ChoiceType::class, [
                'label' => $options['label'],
                'required' => false,
                'choices' => $options['choices'],
                'multiple' => true,
                'attr' => [
                    'class' => 'uk-width-1-1'
                ]
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['label', 'choices'])
            ->setDefaults([
                'translation_domain' => 'room',
                'csrf_protection' => false,
            ])
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
        return 'xhr_action';
    }
}