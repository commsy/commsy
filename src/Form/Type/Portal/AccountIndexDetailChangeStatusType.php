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

namespace App\Form\Type\Portal;

use App\Entity\PortalUserChangeStatus;
use App\Security\Authorization\Voter\RootVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountIndexDetailChangeStatusType extends AbstractType
{
    /**
     * AccountIndexDetailChangeStatusType constructor.
     */
    public function __construct(private readonly Security $security)
    {
    }

    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Name', Types\TextType::class, [
                'label' => 'Name',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('UserID', Types\TextType::class, [
                'label' => 'User ID',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('lastLogin', Types\TextType::class, [
                'label' => 'Last login',
                'translation_domain' => 'portal',
                'required' => false,
                'disabled' => true,
            ])
            ->add('currentStatus', Types\TextType::class, [
                'label' => 'Current state',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('newStatus', Types\ChoiceType::class, [
                'label' => 'New state',
                'expanded' => true,
                'choices' => [
                    'Close' => 'close',
                    'User' => 'user',
                    'Moderator' => 'moderator',
                ],
                'translation_domain' => 'portal',
            ])
            ->add('contact', Types\CheckboxType::class, [
                'label' => 'Contact',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('loginIsDeactivated', Types\ChoiceType::class, [
                'label' => 'Is login deactivated?',
                'expanded' => true,
                'placeholder' => false,
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'translation_domain' => 'portal',
                'required' => false,
                'disabled' => !$this->security->isGranted('ROLE_ROOT'),
            ])
            ->add('impersonateExpiryDate', Types\DateType::class, [
                'label' => 'Login as for x days activated',
                'translation_domain' => 'portal',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'input' => 'datetime_immutable',
                'disabled' => !$this->security->isGranted(RootVoter::ROOT),
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'Save',
                'translation_domain' => 'portal',
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PortalUserChangeStatus::class,
            'translation_domain' => 'portal',
        ]);
    }
}
