<?php
namespace App\Form\Type\Bibliographic;

use App\Services\LegacyEnvironment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BiblioJournalType extends AbstractType
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
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationDomain = 'form';
        $language = $this->legacyEnvironment->getSelectedLanguage();

        $builder
            ->add('author', TextType::class, array(
                'label' => 'author',
                'translation_domain' => $translationDomain,
                ))
            ->add('publishing_date', TextType::class, array(
                'label' => 'publishing date',
                'translation_domain' => $translationDomain,
                ))
            ->add('journal', TextType::class, array(
                'label' => 'journal',
                'translation_domain' => $translationDomain,
                ))
            ->add('issue', TextType::class, array(
                'label' => 'issue',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('volume', TextType::class, array(
                'label' => 'volume',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('pages', TextType::class, array(
                'label' => 'pages',
                'translation_domain' => $translationDomain,
                ))
            ->add('issn', TextType::class, array(
                'label' => 'issn',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('publisher', TextType::class, array(
                'label' => 'publisher',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('address', TextType::class, array(
                'label' => 'address',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('url', TextType::class, array(
                'label' => 'url',
                'translation_domain' => $translationDomain,
                'required' => false,
            ))
        ;

        if($language == 'en'){
            $format = '{format:\'MM/DD/YYYY\'}';
        } else{
            $format = '{format:\'DD.MM.YYYY\'}';
        }

        $builder->add('url_date', TextType::class, array(
            'label' => 'url date',
            'translation_domain' => $translationDomain,
            'required' => false,
            'attr' => array(
                'data-uk-datepicker' => $format
            )
        ));
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
        return 'biblio_journal';
    }

}