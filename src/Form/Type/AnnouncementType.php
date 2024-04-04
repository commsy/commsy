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

use App\Form\Type\Custom\CategoryMappingType;
use App\Form\Type\Custom\DateTimeSelectType;
use App\Form\Type\Custom\HashtagMappingType;
use App\Services\LegacyEnvironment;
use cs_context_item;
use cs_environment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AnnouncementType extends AbstractType
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $formData = $builder->getData();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()], 'label' => 'title', 'attr' => ['placeholder' => $options['placeholderText'], 'class' => 'uk-form-width-medium cs-form-title'], 'translation_domain' => 'announcement'])
            // add custom datetime picker
            ->add('validdate', DateTimeSelectType::class, ['label' => 'valid until', 'translation_domain' => 'announcement']);

        if ($formData['creatorId'] === $currentUser->getItemID() || $currentUser->isModerator()) {
            $builder
                ->add('permission', CheckboxType::class, ['label' => 'permission', 'required' => false])
                ->add('hidden', CheckboxType::class, ['label' => 'hidden', 'required' => false])
                ->add('hiddendate', DateTimeSelectType::class, ['label' => 'hidden until']);
        }

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $announcement = $event->getData();
                $form = $event->getForm();
                $formOptions = $form->getConfig()->getOptions();

                if ($announcement['draft']) {
                    /** @var cs_context_item $room */
                    $room = $formOptions['room'];

                    if ($room->withBuzzwords()) {
                        $hashtagOptions = array_merge($formOptions['hashtagMappingOptions'], [
                            'assignment_is_mandatory' => $room->isBuzzwordMandatory(),
                        ]);
                        $form->add('hashtag_mapping', HashtagMappingType::class, $hashtagOptions);
                    }

                    if ($room->withTags()) {
                        $categoryOptions = array_merge($formOptions['categoryMappingOptions'], [
                            'assignment_is_mandatory' => $room->isTagMandatory(),
                        ]);
                        $form->add('category_mapping', CategoryMappingType::class, $categoryOptions);
                    }
                }
            })
            ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary'], 'label' => 'save'])
            ->add('cancel', SubmitType::class, ['attr' => ['formnovalidate' => ''], 'label' => 'cancel'])
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
            ->setRequired(['placeholderText', 'hashtagMappingOptions', 'categoryMappingOptions', 'room'])
            ->setDefaults([
                'translation_domain' => 'form',
                'lock_protection' => true,
            ])
            ->setAllowedTypes('room', 'cs_context_item');
    }
}
