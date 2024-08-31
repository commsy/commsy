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

namespace App\Form\Type\Context;

use App\Form\Model\RoomData;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunityType extends AbstractType
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly LegacyEnvironment $legacyEnvironment
    ) {}
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
        // NOTE: opposed to the Type/ProjectType.php form, the `data` option somehow won't preselect
        // any default template that's defined in `preferredChoices`, maybe due to this form being
        // added as 'type_sub' in `AddContextFieldListener`? However, `empty_data` will work here.
        $builder
            ->add('generic', RoomType::class, [
                'label' => false,
            ])
            ->add('masterTemplate', ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function(): array {
                    $templates = $this->roomService->getAvailableTemplates('community');
                    uasort($templates, fn ($a, $b) => $a <=> $b);

                    return $templates;
                }),
                'preferred_choices' => function ($choice): bool {
                    $currentPortalItem = $this->legacyEnvironment->getEnvironment()->getCurrentPortalItem();

                    // NOTE: `getDefault...TemplateID()` may also return '-1' (if no default template is defined)
                    $defaultId = $currentPortalItem->getDefaultProjectTemplateID();

                    return $choice == $defaultId;
                },
                'placeholder' => 'No template',
                'required' => false,
                'mapped' => false,
                'label' => 'Template',
                'empty_data' => (!empty($options['preferredChoices'])) ? $options['preferredChoices'][0] : '',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-default',
                    'formnovalidate' => '',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
                'validation_groups' => false,
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
            ->setDefaults([
                'data_class' => RoomData::class,
                'translation_domain' => 'project',
            ])
        ;
    }
}
