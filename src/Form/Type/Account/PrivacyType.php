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

namespace App\Form\Type\Account;

use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
use App\Entity\Account;
use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

class PrivacyType extends AbstractType implements DataMapperInterface
{
    public function __construct(
        private AccountSettingsManager $settingsManager
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
        $builder
            ->setDataMapper($this)
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            /** @var Portal $portal */
            $portal = $options['portal'];
            $form = $event->getForm();

            if ($portal->isAllowUserDefinedDeletionStrategy()) {
                $form
                    ->add(AccountSetting::USER_DELETION_CASCADING_ITEMS->value, CheckboxType::class, [
                        'label' => 'profile.privacy.cascading_items_deletion_strategy.label',
                        'help' => 'profile.privacy.cascading_items_deletion_strategy.help',
                        'required' => false,
                        'label_attr' => [
                            'class' => 'uk-form-label',
                        ],
                    ])
                    ->add('save', SubmitType::class, [
                        'label' => 'save',
                        'translation_domain' => 'form',
                        'attr' => ['style' => 'margin-top: 20px;'],
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('portal')
            ->setAllowedTypes('portal', Portal::class)
            ->setDefaults([
                'validation_groups' => 'account_settings',
                'translation_domain' => 'profile',
            ]);
    }

    public function mapDataToForms(mixed $viewData, Traversable $forms): void
    {
        if (!$viewData instanceof Account) {
            throw new UnexpectedTypeException($viewData, Account::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $userDeletionCascadingItems = $this->settingsManager
            ->getSetting($viewData, AccountSetting::USER_DELETION_CASCADING_ITEMS);

        $forms[AccountSetting::USER_DELETION_CASCADING_ITEMS->value]
            ?->setData($userDeletionCascadingItems['enabled']);
    }

    public function mapFormsToData(Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof Account) {
            throw new UnexpectedTypeException($viewData, Account::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $this->settingsManager->storeSetting(
            $viewData,
            AccountSetting::USER_DELETION_CASCADING_ITEMS,
            ['enabled' => $forms[AccountSetting::USER_DELETION_CASCADING_ITEMS->value]?->getData() ?? true]
        );
    }
}
