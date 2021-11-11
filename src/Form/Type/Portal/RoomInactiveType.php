<?php


namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class RoomInactiveType extends AbstractType
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
            ->add('clearInactiveRoomsFeatureEnabled', Types\CheckboxType::class, [
                'label' => 'portal.inactive.room.enable.label',
                'help' => 'portal.inactive.room.enable.help',
                'help_attr' => [
                    'class' => 'uk-text-warning',
                ],
                'required' => false,
            ])
            ->add('clearInactiveRoomsNotifyLockDays', Types\TextType::class, [
                'label' => 'portal.inactive.room.email_before_lock.label',
                'help' => 'portal.inactive.room.email_before_lock.help',
            ])
            ->add('clearInactiveRoomsLockDays', Types\TextType::class, [
                'label' => 'portal.inactive.room.lock.label',
                'help' => 'portal.inactive.room.lock.help',
            ])
            ->add('clearInactiveRoomsNotifyDeleteDays', Types\TextType::class, [
                'label' => 'portal.inactive.room.email_before_delete.label',
                'help' => 'portal.inactive.room.email_before_delete.help',
            ])
            ->add('clearInactiveRoomsDeleteDays', Types\TextType::class, [
                'label' => 'portal.inactive.room.delete.label',
                'help' => 'portal.inactive.room.delete.help',
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
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
        $resolver
            ->setDefaults([
                'data_class' => Portal::class,
                'translation_domain' => 'portal',
            ]);
    }
}