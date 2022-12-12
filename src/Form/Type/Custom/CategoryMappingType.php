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

namespace App\Form\Type\Custom;

use App\Form\Type\TreeChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CategoryMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('categories', TreeChoiceType::class, [
                'placeholder' => false,
                'choices' => $options['categories'],
                'choice_label' => fn ($choice, $key, $value) => // remove the trailing category ID from $key (which was used in LabelService->transformTagArray() to uniquify the key)
implode('_', explode('_', $key, -1)),
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('newCategory', TextType::class, [
                'attr' => [
                    'placeholder' => $options['categoryPlaceholderText'],
                ],
                'label' => 'newCategory',
                'required' => false,
            ])
            ->add('newCategoryAdd', ButtonType::class, [
                'attr' => [
                    'id' => 'addNewCategory',
                    'data-cs-add-category' => $options['categoryEditUrl'],
                ],
                'label' => 'addNewCategory',
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['categoryPlaceholderText', 'categories', 'categoryEditUrl']);

        $resolver->setDefaults([
            'translation_domain' => 'form',
            'constraints' => [
                new Callback([$this, 'validate']),
            ],
            'assignment_is_mandatory' => true,
        ]);

        $resolver->setAllowedTypes('assignment_is_mandatory', 'bool');
    }

    public function validate(array $data, ExecutionContextInterface $context): void
    {
        /** @var Form $form */
        $form = $context->getObject();
        $assignmentIsMandatory = $form->getConfig()->getOption('assignment_is_mandatory');

        if ($assignmentIsMandatory && !$data['categories'] && !$data['newCategory']) {
            $context->buildViolation('Please select at least one category')
                ->atPath('categories')
                ->addViolation();
        }
    }
}
