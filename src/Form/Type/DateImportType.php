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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateImportType extends AbstractType
{
    public function __construct(
        /**
         * The Symfony translator.
         */
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $uploadErrorMessage = $this->translator->trans('upload error', [], 'error');
        $noFileIdsMessage = $this->translator->trans('upload error', [], 'error');

        $builder
            ->add('files', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => CheckedFileType::class,
                'entry_options' => [
                ],
            ])
            ->add('upload', FileType::class, ['label' => 'upload', 'attr' => ['data-uk-csupload' => '{"path": "'.$options['uploadUrl'].'", "errorMessage": "'.$uploadErrorMessage.'", "noFileIdsMessage": "'.$noFileIdsMessage.'"}', 'accept' => 'text/calendar'], 'required' => false, 'translation_domain' => 'date', 'multiple' => false])
            ->add('calendar', ChoiceType::class, ['placeholder' => false, 'choices' => $options['calendars'], 'choice_attr' => $options['calendarsAttr'], 'label' => 'calendar', 'required' => true, 'expanded' => false, 'multiple' => false])
            ->add('calendartitle', TextType::class, ['label' => 'Title', 'translation_domain' => 'calendar', 'required' => false])
            ->add('calendarcolor', TextType::class, [
                'label' => 'Color',
                'translation_domain' => 'calendar',
                'required' => false,
                'attr' => ['class' => 'jscolor {hash:true}'],
            ])
            ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary'], 'label' => 'import dates', 'translation_domain' => 'date']);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['uploadUrl', 'calendars', 'calendarsAttr'])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'upload';
    }
}
