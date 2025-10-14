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

use App\Form\Trait\CategoryTagValidatorTrait;
use App\Form\Type\Custom\DateTimeSelectType;
use App\Form\Type\Event\AddBibliographicFieldListener;
use App\Form\Type\Event\AddEtherpadFormListener;
use App\Security\Authorization\Voter\ItemVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;

class MaterialType extends AbstractType
{
    use CategoryTagValidatorTrait;

    public function __construct(
        private readonly Security $security,
        private readonly AddEtherpadFormListener $etherpadFormListener
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [new NotBlank()],
                'label' => 'title',
                'attr' => [
                    'placeholder' => $options['placeholderText'],
                    'class' => 'uk-form-width-medium cs-form-title',
                ],
                'translation_domain' => 'material',
            ]);

        $builder
            ->add('biblio_select', ChoiceType::class, [
                'choices' => [
                    'plain' => 'BiblioPlainType',
                    'book' => 'BiblioBookType',
                    'collection' => 'BiblioCollectionType',
                    'article' => 'BiblioArticleType',
                    'journal' => 'BiblioJournalType',
                    'chapter' => 'BiblioChapterType',
                    'newspaper' => 'BiblioNewspaperType',
                    'thesis' => 'BiblioThesisType',
                    'manuscript' => 'BiblioManuscriptType',
                    'website' => 'BiblioWebsiteType',
                    'document management' => 'BiblioDocManagementType',
                    'picture' => 'BiblioPictureType',
                ],
                'label' => 'bib reference',
                'choice_translation_domain' => true,
                'required' => false,
            ])
            ->addEventSubscriber($this->etherpadFormListener)
            ->addEventSubscriber(new AddBibliographicFieldListener())
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $material = $event->getData();
                $form = $event->getForm();
                $formOptions = $form->getConfig()->getOptions();

                if ($this->security->isGranted(ItemVoter::OWN, $formOptions['itemId']) ||
                    $this->security->isGranted(ItemVoter::MODERATE))
                {
                    $form
                        ->add('permission', CheckboxType::class, [
                            'label' => 'permission',
                            'required' => false,
                            'label_attr' => ['class' => 'uk-form-label'],
                        ])
                        ->add('hidden', CheckboxType::class, [
                            'label' => 'hidden',
                            'required' => false,
                        ])
                        ->add('hiddendate', DateTimeSelectType::class, [
                            'label' => 'hidden until',
                        ]);
                }

                if ($material['external_viewer_enabled']) {
                    $form->add('external_viewer', TextType::class, [
                        'required' => false,
                    ]);
                }
            })
            ->add('license_id', ChoiceType::class, [
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'choices' => $options['licenses'],
                'translation_domain' => 'material',
            ])
            ->add('save', SubmitType::class, [
                'attr' => ['class' => 'uk-button-primary'],
                'label' => 'save',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => ['formnovalidate' => ''],
                'label' => 'cancel',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'placeholderText',
                'hashtagMappingOptions',
                'categoryMappingOptions',
                'licenses',
                'room',
                'itemId',
            ])
            ->setDefaults([
                'translation_domain' => 'form',
                'lock_protection' => true,
                'constraints' => [
                    new Callback($this->validate(...)),
                ],
            ])
            ->setAllowedTypes('room', 'cs_context_item')
        ;
    }
}
