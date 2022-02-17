<?php
namespace App\Form\Type\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use App\Services\LegacyEnvironment;

class DateSelectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateTimeType::class, array(
                'input'  => 'datetime',
                'label' => false,
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'required' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'autocomplete' => 'off',
                )
            ));
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