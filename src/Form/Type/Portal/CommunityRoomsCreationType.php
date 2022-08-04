<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunityRoomsCreationType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('communityRoomCreationStatus', Types\ChoiceType::class, [
                'label' => 'Room creation',
                'expanded' => true,
                'choices'  => [
                    'Community room creation by all' => 'all',
                    'Community room creation by moderators only' => 'moderator',
                ],
            ])
            ->add('defaultCommunityTemplateID', Types\ChoiceType::class, [
                'choices' => $options['templateChoices'] ?? [],
                'placeholder' => false,
                'required' => false,
                'label' => 'Default template',
                'help' => 'Default template help text',
            ])
            ->add('communityShowDeactivatedEntriesTitle', Types\ChoiceType::class, [
                'label' => 'portal.form.workspaces.show_deactivated_entries_label',
                'expanded' => true,
                'choices'  => [
                    'portal.form.workspaces.show_deactivated_entries_label.show_title' => true,
                    'portal.form.workspaces.show_deactivated_entries_label.hide_title' => false,
                ],
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
            ->setRequired([
                'templateChoices',
            ])
            ->setDefaults([
                'data_class' => Portal::class,
                'translation_domain' => 'portal',
            ]);
    }
}
