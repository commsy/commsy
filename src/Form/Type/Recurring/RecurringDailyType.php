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
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RecurringDailyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recurrenceDay', NumberType::class, ['constraints' => [new NotBlank()], 'label' => 'recurrenceDay', 'attr' => ['class' => 'cs-form-input-inline', 'style' => 'margin: 0px 3px;', 'size' => '2'], 'translation_domain' => 'date'])
            ->add('untilDate', DateType::class, ['constraints' => [new NotBlank()], 'label' => 'untilDate', 'widget' => 'single_text', 'format' => 'dd.MM.yyyy', 'html5' => false, 'attr' => ['data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}', 'class' => 'cs-form-input-inline', 'style' => 'margin: 0px 3px;'], 'translation_domain' => 'date'])
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
