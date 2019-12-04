<?php
namespace App\Form\Type\Bibliographic;

use App\Services\LegacyEnvironment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BiblioWebsiteType extends AbstractType
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
            ->add('editor', TextType::class, array(
                'label' => 'editor',
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
        return 'biblio_website';
    }

}