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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'title', 'attr' => ['placeholder' => 'title', 'class' => ''], 'required' => false])
            ->add('dateOfBirth', DateType::class, ['label' => 'dateOfBirth', 'required' => false, 'format' => 'dd.MM.yyyy', 'html5' => false, 'attr' => ['data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}'], 'widget' => 'single_text'])
            ->add('emailRoom', EmailType::class, ['constraints' => [new NotBlank()], 'label' => 'email', 'attr' => ['placeholder' => 'email', 'class' => ''], 'required' => true])
            ->add('hideEmailInThisRoom', CheckboxType::class, ['label' => 'hideEmailInThisRoom', 'required' => false])
            ->add('phone', TextType::class, ['label' => 'phone', 'attr' => ['placeholder' => 'phone', 'class' => ''], 'required' => false])
            ->add('mobile', TextType::class, ['label' => 'mobile', 'attr' => ['placeholder' => 'mobile', 'class' => ''], 'required' => false])
            ->add('street', TextType::class, ['label' => 'street', 'attr' => ['placeholder' => 'street', 'class' => ''], 'required' => false])
            ->add('zipCode', TextType::class, ['label' => 'zipCode', 'attr' => ['placeholder' => 'zipCode', 'class' => ''], 'required' => false])
            ->add('city', TextType::class, ['label' => 'city', 'attr' => ['placeholder' => 'city', 'class' => ''], 'required' => false])
            ->add('room', TextType::class, ['label' => 'room', 'attr' => ['placeholder' => 'room', 'class' => ''], 'required' => false])
            ->add('organisation', TextType::class, ['label' => 'organisation', 'attr' => ['placeholder' => 'organisation', 'class' => ''], 'required' => false])
            ->add('position', TextType::class, ['label' => 'position', 'attr' => ['placeholder' => 'position', 'class' => ''], 'required' => false])
            ->add('homepage', TextType::class, ['label' => 'homepage', 'attr' => ['placeholder' => 'homepage', 'class' => ''], 'required' => false])
            ->add('skype', TextType::class, ['label' => 'skype', 'attr' => ['placeholder' => 'skype', 'class' => ''], 'required' => false])
            ->add('language', ChoiceType::class, ['placeholder' => false, 'choices' => ['browser' => 'browser', 'german' => 'de', 'english' => 'en'], 'label' => 'language', 'required' => false, 'expanded' => false, 'multiple' => false])
        ;

        $builder
            ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary'], 'label' => 'save', 'translation_domain' => 'form'])
            ->add('cancel', SubmitType::class, ['attr' => ['formnovalidate' => ''], 'label' => 'cancel', 'translation_domain' => 'form'])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['uploadUrl'])
            ->setDefaults(['translation_domain' => 'user'])
        ;
    }
}
