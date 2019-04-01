<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

class CategoryEditType extends AbstractType
{
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
        $choices = $this->buildChoices($options['categories']);

        $builder
            ->add('category', CategoryType::class, array(
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'label' => false,
            ))

            ->add('structure', Types\HiddenType::class, [
            ])

            ->add('update', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
                'translation_domain' => 'form',
            ])
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
            ->setRequired(['categories'])
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
        return 'category_edit';
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