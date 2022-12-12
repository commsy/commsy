<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Services\LegacyEnvironment;
use cs_environment;
use cs_room_item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtensionSettingsType extends AbstractType
{
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('assessment', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label']])
            ->add(
                $builder->create('workflow', FormType::class, [])
                ->add('resubmission', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label']])
                ->add('validity', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label']])
                ->add(
                    $builder->create('traffic_light', FormType::class, [])
                    ->add('status_view', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label']])
                    ->add('default_status', ChoiceType::class, ['label_attr' => ['class' => 'uk-form-label'], 'expanded' => true, 'multiple' => false, 'choices' => ['GreenSymbol' => '0_green', 'YellowSymbol' => '1_yellow', 'RedSymbol' => '2_red', 'NoDefault' => '3_none']])
                    ->add('green_text', TextType::class, ['required' => true])
                    ->add('yellow_text', TextType::class, ['required' => true])
                    ->add('red_text', TextType::class, ['required' => true])
                )

                ->add('reader', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label']])
                ->add('reader_group', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label']])
                ->add('reader_person', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label']])
                ->add('resubmission_show_to', ChoiceType::class, ['label' => false, 'expanded' => true, 'multiple' => false, 'choices' => ['Moderators only' => 'moderator', 'All users' => 'all']])
            )
            ->add('save', SubmitType::class, ['label' => 'Save', 'attr' => ['class' => 'uk-button-primary']])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                $form = $event->getForm();

                /** @var cs_room_item $roomItem */
                $roomItem = $options['room'];

                if ($roomItem->isProjectRoom()) {
                    $form->add('createUserRooms', CheckboxType::class, [
                            'label' => 'User room',
                            'required' => false,
                            'label_attr' => [
                                'class' => 'uk-form-label',
                            ],
                        ])
                        ->add('userroom_template', ChoiceType::class, [
                            'choices' => $options['userroomTemplates'],
                            'preferred_choices' => $options['preferredUserroomTemplates'],
                            'placeholder' => false,
                            'required' => false,
                            'mapped' => true,
                            'label' => 'User room template',
                        ])
                        ->add('deleteUserRooms', SubmitType::class, [
                            'label' => 'Delete user rooms',
                            'attr' => [
                                'class' => 'uk-button-danger',
                            ],
                        ])
                    ;
                }
            })
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([
                'room',
                'userroomTemplates',
                'preferredUserroomTemplates',
            ])
            ->setRequired(['room'])
            ->setDefaults(['translation_domain' => 'settings'])
        ;
    }
}
