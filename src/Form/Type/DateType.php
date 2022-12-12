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

use App\Form\Type\Custom\CategoryMappingType;
use App\Form\Type\Custom\DateTimeSelectType;
use App\Form\Type\Custom\HashtagMappingType;
use App\Form\Type\Event\AddRecurringFieldListener;
use cs_context_item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class DateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()], 'label' => 'title', 'attr' => ['placeholder' => $options['placeholderText'], 'class' => 'uk-form-width-medium cs-form-title'], 'translation_domain' => 'material'])
            ->add('start', DateTimeSelectType::class, ['constraints' => [], 'label' => 'startdate', 'attr' => ['placeholder' => 'startdate', 'class' => 'uk-form-width-medium']])
            ->add('end', DateTimeSelectType::class, ['constraints' => [], 'label' => 'enddate', 'attr' => ['placeholder' => 'enddate', 'class' => 'uk-form-width-medium'], 'required' => false])
            ->add('whole_day', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
            ->add('place', TextType::class, ['label' => 'place', 'attr' => ['placeholder' => 'place', 'class' => 'uk-form-width-medium'], 'required' => false])
            ->add('calendar', ChoiceType::class, ['placeholder' => false, 'choices' => $options['calendars'], 'choice_attr' => $options['calendarsAttr'], 'label' => 'calendar', 'required' => true, 'expanded' => true, 'multiple' => false])
            ->add('permission', CheckboxType::class, ['label' => 'permission', 'label_attr' => ['class' => 'uk-form-label'], 'required' => false, 'translation_domain' => 'form'])
            ->add('hidden', CheckboxType::class, ['label' => 'hidden', 'label_attr' => ['class' => 'uk-form-label'], 'required' => false, 'translation_domain' => 'form'])
            ->add('hiddendate', DateTimeSelectType::class, ['label' => 'hidden until', 'label_attr' => ['class' => 'uk-form-label'], 'translation_domain' => 'form'])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $date = $event->getData();
                $form = $event->getForm();
                $formOptions = $form->getConfig()->getOptions();

                if ($date['external_viewer_enabled']) {
                    $form->add('external_viewer', TextType::class, [
                        'required' => false,
                    ]);
                }

                if ($date['draft']) {
                    /** @var cs_context_item $room */
                    $room = $formOptions['room'];

                    if ($room->withBuzzwords()) {
                        $hashtagOptions = array_merge($formOptions['hashtagMappingOptions'], [
                            'assignment_is_mandatory' => $room->isBuzzwordMandatory(),
                        ]);
                        $form->add('hashtag_mapping', HashtagMappingType::class, $hashtagOptions);
                    }

                    if ($room->withTags()) {
                        $categoryOptions = array_merge($formOptions['categoryMappingOptions'], [
                            'assignment_is_mandatory' => $room->isTagMandatory(),
                        ]);
                        $form->add('category_mapping', CategoryMappingType::class, $categoryOptions);
                    }
                }
            })
        ;

        if (!isset($options['attr']['unsetRecurrence'])) {
            $builder
                ->add('recurring_select', ChoiceType::class, ['choices' => ['RecurringDailyType' => 'RecurringDailyType', 'RecurringWeeklyType' => 'RecurringWeeklyType', 'RecurringMonthlyType' => 'RecurringMonthlyType', 'RecurringYearlyType' => 'RecurringYearlyType'], 'label' => 'recurring date', 'choice_translation_domain' => true, 'required' => false])
                ->addEventSubscriber(new AddRecurringFieldListener())
            ;
            $builder
                ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary'], 'label' => 'save', 'translation_domain' => 'form'])
                ->add('cancel', SubmitType::class, ['attr' => ['formnovalidate' => ''], 'label' => 'cancel', 'translation_domain' => 'form'])
            ;
        } else {
            $builder
                ->add('saveThisDate', SubmitType::class, ['attr' => ['class' => 'uk-button-primary'], 'label' => 'saveThisDate'])
                ->add('saveAllDates', SubmitType::class, ['attr' => ['class' => 'uk-button-primary'], 'label' => 'saveAllDates'])
                ->add('cancel', SubmitType::class, ['attr' => ['formnovalidate' => ''], 'label' => 'cancel', 'translation_domain' => 'form'])
            ;
        }
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['placeholderText', 'calendars', 'calendarsAttr', 'hashtagMappingOptions', 'categoryMappingOptions', 'room']);

        $resolver->setDefaults(['translation_domain' => 'date']);

        $resolver->setAllowedTypes('room', 'cs_context_item');
    }
}
