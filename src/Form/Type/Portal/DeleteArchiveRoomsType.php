<?php


namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ArchiveRoomsType extends AbstractType
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
            ->add('activated', Types\CheckboxType::class, [
                'label' => 'activate',
            ])
            ->add('archivingDaysUnused', Types\TextType::class, [
                'label' => 'Archivieren nach Tagen'
            ])
            ->add('sendArchivingMailInDaysAdvance', Types\TextType::class, [
                'label' => 'Moderation Tage vorher informieren'
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
                'data_class' => null,
                'translation_domain' => 'portal',
            ]);
    }
}