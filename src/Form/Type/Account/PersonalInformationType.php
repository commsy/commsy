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

use App\Entity\Account;
use App\Validator\Constraints\UniqueUserId;
use cs_user_item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;

class PersonalInformationType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Account $user */
        $user = $this->security->getUser();
        $changeUsername = null !== $user ? $user->getAuthSource()->isChangeUsername() : false;
        $changeUserdata = null !== $user ? $user->getAuthSource()->isChangeUserdata() : false;

        $emailConstraints = [];
        /** @var cs_user_item $portalUser */
        $portalUser = $options['portalUser'];

        if ($portalUser->hasToChangeEmail()) {
            $emailConstraints[] = new NotEqualTo(['value' => $portalUser->getEmail()]);
        }

        $builder
            ->add('userId', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new UniqueUserId([
                        'portalId' => $portalUser->getContextID(),
                    ]),
                ],
                'label' => 'userId',
                'required' => true,
                'disabled' => !$changeUsername,
            ])
            ->add('firstname', TextType::class, [
                'label' => 'firstname',
                'required' => false,
                'disabled' => !$changeUserdata,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'lastname',
                'required' => false,
                'disabled' => !$changeUserdata,
            ])
            ->add('emailAccount', EmailType::class, [
                'label' => 'email',
                'required' => true,
                'constraints' => $emailConstraints,
                'disabled' => !$changeUserdata,
            ])
            ->add('dateOfBirth', DateType::class, [
                'label' => 'dateOfBirth',
                'required' => false,
                'format' => 'dd.MM.yyyy',
                'attr' => [
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                ],
                'widget' => 'single_text',
                'html5' => false,
            ])
            ->add('dateOfBirthChangeInAllContexts', CheckboxType::class, [
                'label' => false,
                'required' => false,
                'label_attr' => [
                    'class' => 'uk-form-label',
                ],
                'data' => true,
                'attr' => [
                    'style' => 'display: none',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['portalUser'])
            ->setDefaults(['translation_domain' => 'profile']);
    }
}
