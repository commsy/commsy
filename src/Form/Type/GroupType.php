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

use App\Form\Trait\CategoryTagValidatorTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;

class GroupType extends AbstractType
{
    use CategoryTagValidatorTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => 'title',
                'attr' => [
                    'placeholder' => $options['placeholderText'],
                    'class' => 'uk-form-width-medium cs-form-title',
                ],
                'translation_domain' => 'material',
            ])
            ->add('master_template', ChoiceType::class, [
                'choices' => $options['templates'],
                'placeholder' => 'Choose a template',
                'required' => false,
                'mapped' => false,
                'label' => 'template for group workspace',
                'translation_domain' => 'group',
            ])
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
            ->setRequired([
                'placeholderText',
                'hashtagMappingOptions',
                'categoryMappingOptions',
                'room',
                'templates',
            ])
            ->setDefaults([
                'translation_domain' => 'form',
                'lock_protection' => true,
                'constraints' => [
                    new Callback($this->validate(...)),
                ],
            ])
            ->setAllowedTypes('room', 'cs_context_item')
        ;
    }
}
