<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

class PortalhomeType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('showRoomsOnHome', Types\ChoiceType::class, [
                'label' => 'Show',
                'expanded' => true,
                'choices'  => [
                    'All open workspaces' => 'normal',
                    'Only community workspaces' => 'onlycommunityrooms',
                    'Only project workspaces' => 'onlyprojectrooms',
                ],
            ])
            ->add('showTemplatesInRoomList', Types\CheckboxType::class, [
                'label' => 'Show templates in workspace feed',
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
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}
