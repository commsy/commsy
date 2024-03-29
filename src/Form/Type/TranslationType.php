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
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class TranslationType extends AbstractType
{
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
            ->add('translationDe', Types\TextareaType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Translation german',
                'required' => true,
            ])
            ->add('translationEn', Types\TextareaType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Translation english',
                'required' => true,
            ])
            ->add('update', Types\SubmitType::class, [
                'label' => 'Update translation',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $translation = $event->getData();
            $form = $event->getForm();

            if ($translation->getId()) {
                $form->add('cancel', Types\SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button uk-button-default',
                    ],
                    'label' => 'Cancel',
                    'translation_domain' => 'portal',
                ]);
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
            ->setDefaults([
                'translation_domain' => 'translation',
            ])
        ;
    }
}
