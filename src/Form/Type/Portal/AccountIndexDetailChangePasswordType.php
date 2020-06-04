<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use App\Entity\Portalportal;
use App\Entity\PortalUserEdit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class AccountIndexDetailChangePasswordType extends AbstractType
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
            ->add('userName', Types\TextType::class, [
                'label' => 'Name',
                'translation_domain' => 'portal',
            ])
            ->add('userId', Types\TextType::class, [
                'label' => 'ID',
                'translation_domain' => 'portal',
            ])
            ->add('password', Types\TextType::class, [
                'label' => 'Password',
                'translation_domain' => 'portal',
            ])
            ->add('repeatPassword', Types\TextType::class, [
                'label' => 'Repeat password',
                'translation_domain' => 'portal',
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'Save',
                'translation_domain' => 'portal',
            ])
            ->add('cancel', Types\SubmitType::class, [
                'label' => 'Cancel',
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
            'translation_domain' => 'portal',
        ]);
    }
}
