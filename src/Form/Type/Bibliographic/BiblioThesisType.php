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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioThesisType extends AbstractType
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
            ->add('author', TextType::class, ['label' => 'author', 'translation_domain' => $translationDomain])
            ->add('publishing_date', TextType::class, ['label' => 'publishing date', 'translation_domain' => $translationDomain])
            ->add('thesis_kind', ChoiceType::class, ['label' => 'thesis kind', 'translation_domain' => $translationDomain, 'choices' => ['term' => 'term', 'bachelor' => 'bachelor', 'master' => 'master', 'exam' => 'exam', 'diploma' => 'diploma', 'dissertation' => 'dissertation', 'postdoc' => 'postdoc'], 'choice_translation_domain' => true])
            ->add('address', TextType::class, ['label' => 'address', 'translation_domain' => $translationDomain])
            ->add('university', TextType::class, ['label' => 'university', 'translation_domain' => $translationDomain])
            ->add('faculty', TextType::class, ['label' => 'faculty', 'translation_domain' => $translationDomain, 'required' => false])
            ->add('editor', TextType::class, ['label' => 'editor', 'translation_domain' => $translationDomain, 'required' => false])
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
