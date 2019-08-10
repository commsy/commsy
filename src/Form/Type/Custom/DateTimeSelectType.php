<?php
namespace App\Form\Type\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use App\Services\LegacyEnvironment;

class DateTimeSelectType extends AbstractType
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $language = $this->legacyEnvironment->getSelectedLanguage();
        if($language == 'en'){
            $builder->add('date', DateTimeType::class, array(
                'input'  => 'datetime',
                'label' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD/MM/YYYY\'}',
                )
            ));
        }else{
            $builder->add('date', DateTimeType::class, array(
                'input'  => 'datetime',
                'label' => false,
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'required' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                )
            ));

            $builder->add('time', DateTimeType::class, array(
                'input'  => 'datetime',
                'label' => false,
                'widget' => 'single_text',
                'format' => 'HH:mm',
                'required' => false,
                'attr' => array(
                    'data-uk-timepicker' => '',
                    'style' => 'margin-left: 5px;',
                )
            ));
        }
    }

    /**
     * Returns the name of the parent type.
     * 
     * @return string|null The name of the parent type if any, null otherwise
     */
    public function getParent()
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
    public function getBlockPrefix()
    {
        return 'date_time';
    }
}