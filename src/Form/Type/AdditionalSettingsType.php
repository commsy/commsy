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

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdditionalSettingsType extends AbstractType
{
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
            ->add(
                $builder->create('structural_auxilaries', FormType::class, ['required' => false])
                ->add(
                    $builder->create('associations', FormType::class, [])
                        ->add('show_expanded', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
                )
                ->add(
                    $builder->create('buzzwords', FormType::class, [])
                    ->add('activate', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
                    ->add('show_expanded', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
                    ->add('mandatory', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
                )
                ->add(
                    $builder->create('categories', FormType::class, [])
                    ->add('activate', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
                    ->add('show_expanded', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
                    ->add('mandatory', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
                    ->add('edit', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
                )
                ->add(
                    $builder->create('calendars', FormType::class, ['label' => 'calendars', 'translation_domain' => 'date'])
                    ->add('edit', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes', 'label' => 'users_can_edit_calendars', 'translation_domain' => 'settings'])
                    ->add('external', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes', 'label' => 'users_can_set_external_calendars_url', 'translation_domain' => 'settings'])
                )
            )
            ->add(
                $builder->create('tasks', FormType::class, ['required' => false, 'compound' => true])
                ->add('status', TextType::class, ['required' => false])
                ->add('status_option', ButtonType::class, [])
                ->add('additional_status', CollectionType::class, [
                    // 'label' => false,
                    'entry_type' => TextType::class,
                    'entry_options' => [
                        // 'disabled' => true,
                        'label_attr' => ['class' => 'uk-form-label'],
                    ],
                    'allow_add' => true,
                    'allow_delete' => true,
                ])
            )
            ->add(
                $builder->create('rss', FormType::class, ['required' => false])
                    ->add('status', ChoiceType::class, ['expanded' => true, 'multiple' => false, 'choices' => ['rss_enabled' => '1', 'rss_disabled' => '2']])
            )
        ;

        // user rooms use project room templates, so a user room shouldn't be made available as a template
        $isUserroom = $options['isUserroom'];
        if (!$isUserroom) {
            $builder
                ->add(
                    $builder->create('template', FormType::class, [])
                        ->add('status', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'attr' => ['style' => 'vertical-align: baseline;']])
                        ->add('template_availability', ChoiceType::class, ['required' => true, 'expanded' => false, 'multiple' => false, 'choices' => ['All commsy users' => 0, 'All workspace users' => 1, 'Workspace mods only' => 2]])
                        ->add('template_description', TextareaType::class, ['required' => false, 'attr' => ['style' => 'width: 90%']])
                );
        }

        $builder
            ->add(
                $builder->create('archived', FormType::class, [])
                ->add('active', CheckboxType::class, [
                    'required' => false,
                    'label' => 'Archived',
                    'label_attr' => [
                        'class' => 'uk-form-label',
                    ],
                ])
            )
            /*
            ->add('room_status', CheckboxType::class, array(
                'required' => false,
                'label_attr' => array('class' => 'uk-form-label'),
            ))
            */
            ->add(
                $builder->create('terms', FormType::class, ['required' => false])
                ->add('portalTerms', ChoiceType::class, ['required' => true, 'expanded' => false, 'multiple' => false, 'choices' => $options['portalTerms'], 'label' => 'Portal terms', 'translation_domain' => 'settings'])

                ->add('status', ChoiceType::class, ['expanded' => true, 'multiple' => false, 'choices' => ['Yes' => '1', 'No' => '2']])
                ->add('language', ChoiceType::class, ['required' => true, 'expanded' => false, 'multiple' => false, 'choices' => ['German' => 'de', 'English' => 'en']])
                ->add('agb_text_editor', CKEditorType::class, ['required' => false, 'inline' => false, 'label' => 'Text'])
                ->add('agb_text_de', HiddenType::class, [])
                ->add('agb_text_en', HiddenType::class, [])
            )
            ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary']])
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
            // TODO: add new task status list as required parameter here!
            ->setRequired(['roomId', 'isUserroom', 'newStatus', 'portalTerms'])
            ->setDefaults(['translation_domain' => 'settings'])
        ;
    }
}
