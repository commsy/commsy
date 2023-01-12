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

class BiblioDocManagementType extends AbstractType
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
            ->add('document_editor', TextType::class, ['label' => 'editor', 'translation_domain' => $translationDomain])
            ->add('document_maintainer', TextType::class, ['label' => 'maintainer', 'translation_domain' => $translationDomain])
            ->add('document_release_number', TextType::class, ['label' => 'release number', 'translation_domain' => $translationDomain])
        ;

        if ('en' == $language) {
            $format = '{format:\'MM/DD/YYYY\'}';
        } else {
            $format = '{format:\'DD.MM.YYYY\'}';
        }

        $builder->add('document_release_date', TextType::class, ['label' => 'url date', 'translation_domain' => $translationDomain, 'required' => false, 'attr' => ['data-uk-datepicker' => $format]]);
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
        return 'biblio_docmanagement';
    }
}
