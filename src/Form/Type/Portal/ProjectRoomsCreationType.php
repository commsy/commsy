<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

class ProjectRoomsCreationType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('projectRoomCreationStatus', Types\ChoiceType::class, [
                'label' => 'Room creation',
                'expanded' => true,
                'choices'  => [
                    'Project room creation on portal' => 'portal',
                    'Project room creation in community rooms only' => 'communityroom',
                ],
            ])
            ->add('projectRoomLinkStatus', Types\ChoiceType::class, [
                'label' => 'Mandatory assignment',
                'expanded' => true,
                'choices' => [
                    'Yes' => 'mandatory',
                    'No' => 'optional',
                ],
                'choice_translation_domain' => 'form',
                'help' => 'Mandatory room assignment help text',
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
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}
