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

namespace App\Form\Type;

use App\Entity\Translation;
use App\Repository\TranslationRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class TranslationType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('translation', EntityType::class, [
                'class' => Translation::class,
                'query_builder' => fn (TranslationRepository $repository): QueryBuilder =>
                    $repository->createQueryBuilder('t')
                        ->where('t.contextId = :contextId')
                        ->setParameter('contextId', $options['portalId']),
                'choice_label' => fn (Translation $translation) =>
                    $this->translator->trans($translation->getTranslationKey(), [], 'translation'),
                'label' => 'Translations',
                'translation_domain' => 'portal',
                'placeholder' => new TranslatableMessage('no entry selected', [], 'item'),
                'attr' => [
                    'data-action' => 'live#action',
                    'data-live-action-param' => 'select',
                ]
            ])
        ;

        $builder->addDependent('translationDe', ['translation'], function (DependentField $field, ?Translation $translation) {
            if (!$translation) return;

            $field->add(TextareaType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Translation german',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                ],
            ]);
        });

        $builder->addDependent('translationEn', ['translation'], function (DependentField $field, ?Translation $translation) {
            if (!$translation) return;

            $field->add(TextareaType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Translation english',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                ],
            ]);
        });

        $builder->addDependent('update', ['translation'], function (DependentField $field, ?Translation $translation) {
            if (!$translation) return;

            $field->add(SubmitType::class, [
                'label' => 'Update translation',
                'attr' => [
                    'data-action' => 'live#action:prevent',
                    'data-live-action-param' => 'save',
                    'data-loading' => 'action(save)|addAttribute(disabled)',
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['portalId'])
            ->setDefaults([
                //'data_class' => Translation::class,
                'translation_domain' => 'translation',
            ])
        ;
    }
}
