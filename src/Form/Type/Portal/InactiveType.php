<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints as Assert;

class InactiveType extends AbstractType
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
            ->add('inactivityLockDays', Types\IntegerType::class, [
                'label' => 'Lock user',
                'constraints' => [
                    new Assert\Positive([
                        'message' => 'This value should be positive or empty.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('inactivitySendMailBeforeLockDays', Types\IntegerType::class, [
                'label' => 'Email before lock',
                'constraints' => [
                    new Assert\Positive([
                        'message' => 'This value should be positive or empty.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('inactivityDeleteDays', Types\IntegerType::class, [
                'label' => 'Delete user',
                'constraints' => [
                    new Assert\Positive([
                        'message' => 'This value should be positive or empty.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('inactivitySendMailBeforeDeleteDays', Types\IntegerType::class, [
                'label' => 'Email before delete',
                'constraints' => [
                    new Assert\Positive([
                        'message' => 'This value should be positive or empty.',
                    ]),
                ],
                'required' => false,
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ]);
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
