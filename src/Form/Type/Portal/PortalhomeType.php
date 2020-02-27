<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

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
        $builder->add('configurationSelection', Types\ChoiceType::class, [
                'label' => 'Workspace display',
                'expanded' => true,
                'choices'  => [
                    'all open workspaces' => 0,
                    'preselected community workspaces from all' => 1,
                    'all community workspaces' => 2,
                    'nur Gemeinschaftsräume anzeigen (keine Projekträume)' => 3,
                ],
                'translation_domain' => 'portal',
            ])
            ->add('configurationRoomListTemplates', CheckboxType::class, [
                'label' => 'show template in workspace feed',
                'required' => true,
                'translation_domain' => 'portal',
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
