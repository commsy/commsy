<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use CommsyBundle\Form\Type\Custom\DateTimeSelectType;

use CommsyBundle\Form\Type\Event\AddBibliographicFieldListener;
use CommsyBundle\Form\Type\Custom\MandatoryCategoryMappingType;
use CommsyBundle\Form\Type\Custom\MandatoryHashtagMappingType;

class PortfolioEditCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = $this->buildChoices($options['categories']);
        $disabledCategories = $this->buildChoices($options['disabledCategories']);

        $builder
            ->add('category', ChoiceType::class, array(
                'choices' => $choices,
                'label' => 'category',
                'translation_domain' => 'portfolio',
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'choice_attr' => function($key, $val, $index) use ($disabledCategories) {
                    $result = [];
                    if (isset($disabledCategories[$key])) {
                        $result[$key] ? ['disabled' => 'disabled'] : [];
                    }
                    return $result;
                },
            ))
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => $options['placeholderDescription'],
                ],
                'required' => false,
            ])
            ->add('delete-category', HiddenType::class, [
                'label' => false,
                'required' => true,
            ])
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
            ))
            ->add('delete', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'delete',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
            ))
        ;

    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['categories', 'placeholderDescription', 'disabledCategories'])
            ->setDefaults(array('translation_domain' => 'form'))
        ;
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
        return 'portfolio';
    }

    private function buildChoices($categories) {
        $choices = [];

        foreach ($categories as $category) {
            $choices[$category['title']] = $category['item_id'];

            if (!empty($category['children'])) {
                $choices[$category['title'] . 'sub'] = $this->buildChoices($category['children']);
            }
        }

        return $choices;
    }
}