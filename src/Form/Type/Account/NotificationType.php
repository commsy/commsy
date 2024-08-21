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
use App\Security\Authorization\Voter\UserVoter;
use Symfony\Bundle\SecurityBundle\Security;
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

class NotificationType extends AbstractType implements DataMapperInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly AccountSettingsManager $settingsManager
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
            ])
            ->setDataMapper($this)
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();

            if ($this->security->isGranted(UserVoter::PORTAL_MODERATOR)) {
                $form
                    ->add(AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION->value, CheckboxType::class, [
                        'label' => 'settings.notifications.moderation_self_registration',
                        'required' => false,
                        'label_attr' => [
                            'class' => 'uk-form-label',
                        ],
                        'translation_domain' => 'settings',
                    ])
                    ->add(AccountSetting::NOTIFY_PORTAL_MOD_ON_WORKSPACE_CHANGE->value, CheckboxType::class, [
                        'label' => 'settings.notifications.moderation_workspace_change',
                        'required' => false,
                        'label_attr' => [
                            'class' => 'uk-form-label',
                        ],
                        'translation_domain' => 'settings',
                    ])
                ;
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => 'account_settings',
        ]);
    }

    public function mapDataToForms(mixed $viewData, Traversable $forms): void
    {
        if (!$viewData instanceof Account) {
            throw new UnexpectedTypeException($viewData, Account::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $notifyPortalModOnSelfRegistration = $this->settingsManager
            ->getSetting($viewData, AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION);
        $notifyPortalModOnWorkspaceChange = $this->settingsManager
            ->getSetting($viewData, AccountSetting::NOTIFY_PORTAL_MOD_ON_WORKSPACE_CHANGE);

        $forms[AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION->value]
            ?->setData($notifyPortalModOnSelfRegistration['enabled']);
        $forms[AccountSetting::NOTIFY_PORTAL_MOD_ON_WORKSPACE_CHANGE->value]
            ?->setData($notifyPortalModOnWorkspaceChange['enabled']);
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
            AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION,
            ['enabled' => $forms[AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION->value]?->getData() ?? true]
        );
        $this->settingsManager->storeSetting(
            $viewData,
            AccountSetting::NOTIFY_PORTAL_MOD_ON_WORKSPACE_CHANGE,
            ['enabled' => $forms[AccountSetting::NOTIFY_PORTAL_MOD_ON_WORKSPACE_CHANGE->value]?->getData() ?? true]
        );
    }
}
