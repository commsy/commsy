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

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthWorkspaceMembershipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('authMembershipEnabled', CheckboxType::class, [
                'label' => 'portal.auth.membership_enabled',
                'required' => false,
            ])
            ->add('authMembershipIdentifier', TextType::class, [
                'label' => 'portal.auth.membership_identifier',
                'help' => 'portal.auth.membership_identifier_help',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
            'validation_groups' => function (FormInterface $form) {
                /** @var Portal $portal */
                $portal = $form->getData();

                // only validate the `authMembershipIdentifier` if `authMembershipEnabled` has been activated
                if ($portal->getAuthMembershipEnabled()) {
                    return ['Default', 'authMembershipValidation'];
                }

                return ['Default'];
            },
        ]);
    }
}
