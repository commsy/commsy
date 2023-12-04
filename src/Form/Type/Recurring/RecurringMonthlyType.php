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

namespace App\Form\Type\Recurring;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RecurringMonthlyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recurrenceMonth', TextType::class, ['constraints' => [new NotBlank()], 'label' => 'recurrenceMonth', 'attr' => ['style' => 'margin: 0px 3px;', 'size' => '2'], 'translation_domain' => 'date'])
            ->add('recurrenceDayOfMonthInterval', ChoiceType::class, ['constraints' => [new NotBlank()], 'placeholder' => false, 'choices' => ['first' => '1', 'second' => '2', 'third' => '3', 'fourth' => '4', 'fifth' => '5', 'last' => 'last'], 'label' => 'recurrenceDayOfMonthInterval', 'translation_domain' => 'date', 'required' => false, 'expanded' => false, 'multiple' => false])
            ->add('recurrenceDayOfMonth', ChoiceType::class, ['constraints' => [new NotBlank()], 'placeholder' => false, 'choices' => ['monday' => 'monday', 'tuesday' => 'tuesday', 'wednesday' => 'wednesday', 'thursday' => 'thursday', 'friday' => 'friday', 'saturday' => 'saturday', 'sunday' => 'sunday'], 'label' => 'recurrenceDayOfMonth', 'translation_domain' => 'date', 'required' => false, 'expanded' => false, 'multiple' => false])
            ->add('untilDate', DateType::class, ['constraints' => [new NotBlank()], 'label' => 'untilDate', 'widget' => 'single_text', 'format' => 'dd.MM.yyyy', 'html5' => false, 'attr' => ['data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}', 'style' => 'margin: 0px 3px;'], 'translation_domain' => 'date'])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([])
        ;
    }
}
