<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class AccountIndexDetailType extends AbstractType
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
            ->add('previous', Types\SubmitType::class, [
                'label' => 'Previous',
                'translation_domain' => 'portal',
            ])
            ->add('next', Types\SubmitType::class, [
                'label' => 'Next',
                'translation_domain' => 'portal',
            ])
            ->add('hasNoPrevious', Types\SubmitType::class, [
                'label' => 'Previous',
                'translation_domain' => 'portal',
                'attr' => ['disabled' => 'true'],
            ])
            ->add('hasNoNext', Types\SubmitType::class, [
                'label' => 'Next',
                'translation_domain' => 'portal',
                'attr' => ['disabled' => 'true'],
            ])
            ->add('back', Types\SubmitType::class, [
                'label' => 'Back',
                'translation_domain' => 'portal',
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
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}
