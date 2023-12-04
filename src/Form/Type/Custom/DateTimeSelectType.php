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

namespace App\Form\Type\Custom;

use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

class DateTimeSelectType extends AbstractType
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $language = $this->legacyEnvironment->getSelectedLanguage();
        if ('en' == $language) {
            $builder->add('date', DateTimeType::class, ['input' => 'datetime', 'label' => false, 'widget' => 'single_text', 'format' => 'MM/dd/yyyy', 'required' => false, 'html5' => false, 'attr' => ['data-uk-datepicker' => '{
                        format:\'MM/DD/YYYY\',
                        }']]);
        } else {
            $builder->add('date', DateTimeType::class, ['input' => 'datetime', 'label' => false, 'widget' => 'single_text', 'format' => 'dd.MM.yyyy', 'html5' => false, 'required' => false, 'attr' => ['data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}']]);
        }

        $builder->add('time', DateTimeType::class, ['input' => 'datetime', 'label' => false, 'widget' => 'single_text', 'format' => 'HH:mm', 'html5' => false, 'required' => false, 'attr' => ['data-uk-timepicker' => '', 'style' => 'margin-left: 5px;']]);
    }

    /**
     * Returns the name of the parent type.
     *
     * @return string|null The name of the parent type if any, null otherwise
     */
    public function getParent(): ?string
    {
        return FormType::class;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string
    {
        return 'date_time';
    }
}
