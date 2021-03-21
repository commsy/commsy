<?php


namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\OptionsResolver\OptionsResolver;


class DeleteArchiveRoomsType extends AbstractType
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
            ->add('statusDeletingUnusedRooms', Types\CheckboxType::class, [
                'label' => 'Activate',
                'required' => false,
            ])
            ->add('daysUnusedBeforeDeletingRooms', Types\TextType::class, [
                'label' => 'Delete rooms after x days',
                'required' => false,
            ])
            ->add('daysSendMailBeforeDeletingRooms', Types\TextType::class, [
                'label' => 'Send mail x days before deletion',
                'required' => false,
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