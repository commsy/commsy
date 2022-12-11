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

class RecurringYearlyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recurrenceDayOfMonth', TextType::class, ['constraints' => [new NotBlank()], 'label' => 'recurrenceDayOfMonth', 'attr' => ['style' => 'margin: 0px 3px;', 'size' => '2'], 'translation_domain' => 'date'])
            ->add('recurrenceMonthOfYear', ChoiceType::class, ['constraints' => [new NotBlank()], 'placeholder' => false, 'choices' => ['january' => 'january', 'february' => 'february', 'march' => 'march', 'april' => 'april', 'may' => 'may', 'june' => 'june', 'july' => 'july', 'august' => 'august', 'september' => 'september', 'october' => 'october', 'november' => 'november', 'december' => 'december'], 'label' => 'recurrenceMonthOfYear', 'translation_domain' => 'date', 'required' => false, 'expanded' => false, 'multiple' => false])
            ->add('untilDate', DateType::class, ['constraints' => [new NotBlank()], 'label' => 'untilDate', 'widget' => 'single_text', 'format' => 'dd.MM.yyyy', 'html5' => false, 'attr' => ['data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}', 'style' => 'margin: 0px 3px;'], 'translation_domain' => 'date'])
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
            ->setRequired([])
        ;
    }
}
