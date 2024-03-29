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
use App\Security\Authorization\Voter\CategoryVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CategoryMappingType extends AbstractType
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categories', TreeChoiceType::class, [
                'placeholder' => false,
                'choices' => $options['categories'],
                'choice_label' => fn ($choice, $key, $value) => // remove the trailing category ID from $key (which was used in LabelService->transformTagArray() to uniquify the key)
                    implode('_', explode('_', (string) $key, -1)),
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('newCategoryAdd', ButtonType::class, [
                'attr' => [
                    'id' => 'addNewCategory',
                    'data-cs-add-category' => $options['categoryEditUrl'],
                ],
                'label' => 'addNewCategory',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            // Only add the form for new categories if the user is allowed to create them
            if ($this->security->isGranted(CategoryVoter::EDIT)) {
                $form = $event->getForm();

                $form->add('newCategory', TextType::class, [
                    'attr' => [
                        'placeholder' => $options['categoryPlaceholderText'],
                    ],
                    'label' => 'newCategory',
                    'required' => false,
                ]);
            }
        });
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['categoryPlaceholderText', 'categories', 'categoryEditUrl']);

        $resolver->setDefaults([
            'translation_domain' => 'form',
            'constraints' => [
                new Callback($this->validate(...)),
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
