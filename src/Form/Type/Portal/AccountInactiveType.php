<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountInactiveType extends AbstractType
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
            ->add('clearInactiveAccountsFeatureEnabled', Types\CheckboxType::class, [
                'label' => 'portal.inactive.account.enable.label',
                'help' => 'portal.inactive.account.enable.help',
                'help_attr' => [
                    'class' => 'uk-text-warning',
                ],
                'required' => false,
            ])
            ->add('clearInactiveAccountsNotifyLockDays', Types\IntegerType::class, [
                'label' => 'portal.inactive.account.email_before_lock.label',
                'help' => 'portal.inactive.account.email_before_lock.help',
            ])
            ->add('clearInactiveAccountsLockDays', Types\IntegerType::class, [
                'label' => 'portal.inactive.account.lock.label',
                'help' => 'portal.inactive.account.lock.help',
            ])
            ->add('clearInactiveAccountsNotifyDeleteDays', Types\IntegerType::class, [
                'label' => 'portal.inactive.account.email_before_delete.label',
                'help' => 'portal.inactive.account.email_before_delete.help',
            ])
            ->add('clearInactiveAccountsDeleteDays', Types\IntegerType::class, [
                'label' => 'portal.inactive.account.delete.label',
                'help' => 'portal.inactive.account.delete.help'
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
