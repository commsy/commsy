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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioArticleType extends AbstractType
{
    private $legacyEnvironment;

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
            ->add('author', TextType::class, ['attr' => ['class' => 'uk-flex'], 'label' => 'author', 'translation_domain' => $translationDomain])
            ->add('publishing_date', TextType::class, ['label' => 'publishing date', 'translation_domain' => $translationDomain])
            ->add('pages', TextType::class, ['label' => 'pages', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('booktitle', TextType::class, ['label' => 'booktitle', 'translation_domain' => $translationDomain])
            ->add('editor', TextType::class, ['label' => 'editor', 'translation_domain' => $translationDomain])
            ->add('publisher', TextType::class, ['label' => 'publisher', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('address', TextType::class, ['label' => 'address', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('edition', TextType::class, ['label' => 'edition', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('pages', TextType::class, ['label' => 'pages', 'translation_domain' => $translationDomain])
            ->add('series', TextType::class, ['label' => 'series', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('volume', TextType::class, ['label' => 'volume', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('isbn', TextType::class, ['label' => 'isbn', 'translation_domain' => $translationDomain, 'required' => false])
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
