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

use App\Form\Type\Custom\DateTimeSelectType;
use App\Form\Type\Event\AddRecurringFieldListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start', DateTimeSelectType::class, ['constraints' => [], 'label' => 'startdate', 'attr' => ['placeholder' => 'startdate', 'class' => 'uk-form-width-medium']])
            ->add('end', DateTimeSelectType::class, ['constraints' => [], 'label' => 'enddate', 'attr' => ['placeholder' => 'enddate', 'class' => 'uk-form-width-medium']])
            ->add('whole_day', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'value' => 'yes'])
            ->add('place', TextType::class, ['label' => 'place', 'attr' => ['placeholder' => 'place', 'class' => 'uk-form-width-medium'], 'required' => false])
            ->add('calendar', ChoiceType::class, ['placeholder' => false, 'choices' => $options['calendars'], 'choice_attr' => $options['calendarsAttr'], 'label' => 'calendar', 'required' => true, 'expanded' => true, 'multiple' => false])
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
        $resolver
            ->setRequired([])
            ->setDefaults(['translation_domain' => 'date'])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'date';
    }
}
