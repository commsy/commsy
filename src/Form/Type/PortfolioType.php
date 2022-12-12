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

use App\Utils\PortfolioService;
use cs_item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\Translation\TranslatorInterface;

class PortfolioType extends AbstractType
{
    /**
     * PortfolioType constructor.
     */
    public function __construct(private TranslatorInterface $translator, private PortfolioService $portfolioService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'title',
                'attr' => [
                    'placeholder' => $this->translator->trans('insert title'),
                    'class' => 'uk-form-width-medium cs-form-title',
                ],
                'translation_domain' => 'portfolio',
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => $this->translator->trans('Insert description here', [], 'portfolio'),
                ],
                'required' => false,
                'translation_domain' => 'portfolio',
            ])
            ->add('is_template', CheckboxType::class, [
                'label' => 'Template',
                'translation_domain' => 'portfolio',
                'required' => false,
            ])
            ->add('external_template', TextType::class, [
                'label' => 'Unlock template for users',
                'translation_domain' => 'portfolio',
                'required' => false,
            ])
            ->add('external_viewer', TextType::class, [
                'label' => 'Give access to users',
                'translation_domain' => 'portfolio',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => '',
                ],
                'label' => 'cancel',
                'validation_groups' => false,
            ])
        ;

        // Event listener for modifications based on the underlying data
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();

            /** @var cs_item $item */
            $item = $options['item'];

            if (!$item->isDraft()) {
                $form
                    ->add('delete', SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button-danger',
                        ],
                    ])
                ;
            } else {
                $templates = $this->portfolioService->getPortfolioTemplates();
                $choices = [
                    'None' => 'none',
                ];

                foreach ($templates as $template) {
                    $choices[$template['title']] = $template['id'];
                }

                $form
                    ->add('from_template', ChoiceType::class, [
                        'choices' => $choices,
                        'translation_domain' => 'portfolio',
                    ])
                ;
            }
        });
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['item'])
            ->setDefaults(['translation_domain' => 'form'])
        ;
    }
}
