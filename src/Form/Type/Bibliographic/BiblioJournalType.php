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

namespace App\Form\Type\Bibliographic;

use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioJournalType extends AbstractType
{
    private readonly cs_environment $legacyEnvironment;

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
        $translationDomain = 'form';
        $language = $this->legacyEnvironment->getSelectedLanguage();

        $builder
            ->add('author', TextType::class, ['label' => 'author', 'translation_domain' => $translationDomain])
            ->add('publishing_date', TextType::class, ['label' => 'publishing date', 'translation_domain' => $translationDomain])
            ->add('journal', TextType::class, ['label' => 'journal', 'translation_domain' => $translationDomain])
            ->add('issue', TextType::class, ['label' => 'issue', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('volume', TextType::class, ['label' => 'volume', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('pages', TextType::class, ['label' => 'pages', 'translation_domain' => $translationDomain])
            ->add('issn', TextType::class, ['label' => 'issn', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('publisher', TextType::class, ['label' => 'publisher', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('address', TextType::class, ['label' => 'address', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('url', TextType::class, ['label' => 'url', 'translation_domain' => $translationDomain, 'required' => false])
        ;

        if ('en' == $language) {
            $format = '{format:\'MM/DD/YYYY\'}';
        } else {
            $format = '{format:\'DD.MM.YYYY\'}';
        }

        $builder->add('url_date', TextType::class, ['label' => 'url date', 'translation_domain' => $translationDomain, 'required' => false, 'attr' => ['data-uk-datepicker' => $format]]);
    }
}
