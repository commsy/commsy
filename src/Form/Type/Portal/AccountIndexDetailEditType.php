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

use App\Entity\PortalUserEdit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountIndexDetailEditType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', Types\TextType::class, [
                'label' => 'First name',
                'translation_domain' => 'portal',
            ])
            ->add('lastName', Types\TextType::class, [
                'translation_domain' => 'portal',
                'label' => 'Last name',
            ])
            ->add('academicDegree', Types\TextType::class, [
                'label' => 'AcademicDegree',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('birthday', Types\TextType::class, [
                'label' => 'Birthday',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('street', Types\TextType::class, [
                'label' => 'Street',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('zip', Types\TextType::class, [
                'label' => 'Zip',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('city', Types\TextType::class, [
                'label' => 'City',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('workspace', Types\TextType::class, [
                'label' => 'Workspace',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('telephone', Types\TextType::class, [
                'label' => 'Telephone',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('secondTelephone', Types\TextType::class, [
                'label' => 'Second Telephone',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('email', Types\TextType::class, [
                'label' => 'E-mail',
                'translation_domain' => 'portal',
            ])
            ->add('emailChangeAll', Types\CheckboxType::class, [
                'label' => 'Change mail everywhere',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('icq', Types\TextType::class, [
                'label' => 'ICQ',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('msn', Types\TextType::class, [
                'label' => 'MSN',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('skype', Types\TextType::class, [
                'label' => 'Skype',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('yahoo', Types\TextType::class, [
                'label' => 'Yahoo',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('homepage', Types\TextType::class, [
                'label' => 'Homepage',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('description', Types\TextareaType::class, [
                'label' => 'Description',
                'translation_domain' => 'portal',
                'required' => false,
            ])
//            ->add('picture', FileType::class, [
//                'label' => 'Picture',
//                'translation_domain' => 'portal',
//                'attr' => array(
//                    'data-upload' => '{"path": "' . 'uploadUrl' . '"}',
//                ),
//                'required' => false,
//            ])
//            ->add('overrideExistingPicture', Types\CheckboxType::class, [
//                'label' => 'Override existing picture',
//                'translation_domain' => 'portal',
//                'required' => false,
//            ])
            ->add('mayCreateContext', Types\ChoiceType::class, [
                'label' => 'May create context',
                'expanded' => true,
                'choices' => [
                    'User is allowed to create context' => 'standard',
                    'Yes' => '1',
                    'No' => '-1',
                ],
                'translation_domain' => 'portal',
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'Save',
                'translation_domain' => 'portal',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PortalUserEdit::class,
            'translation_domain' => 'portal',
        ]);
    }
}
