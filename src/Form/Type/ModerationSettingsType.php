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

use App\Services\LegacyEnvironment;
use App\Validator\Constraints\HomeNoticeConstraint;
use cs_environment;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModerationSettingsType extends AbstractType
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, private readonly TranslatorInterface $translator)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

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
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($options['roomId']);
        $availableRubrics = array_merge(['home'], $roomItem->getAvailableRubrics());
        $rubricOptions = [];
        foreach ($availableRubrics as $rubric) {
            $translatedTitle = $this->translator->trans(ucfirst((string) $rubric), ['%count%' => 1], 'rubric');
            $rubricOptions[$translatedTitle] = $rubric;
        }

        $builder
            ->add(
                $builder->create('homenotice', FormType::class, ['required' => false])
                    ->add('item_id', TextType::class, ['label' => 'Content ID', 'constraints' => [new HomeNoticeConstraint()]])
                    ->add('show_information_box', ChoiceType::class, ['label' => false, 'expanded' => true, 'multiple' => false, 'choices' => ['Show info' => '1', 'Do not show info' => '0']])
            )
            ->add(
                $builder->create('usernotice', FormType::class, ['required' => false])
                    ->add('array_info_text_rubric', ChoiceType::class, ['expanded' => false, 'multiple' => false, 'choices' => $rubricOptions])
                    ->add('moderation_title', TextType::class, ['label' => 'Title'])

                    // TODO: replace this manually added, static list of hidden fields with a CollectionType containing HiddenFields (dynamically build from available rubrics)
                    ->add('title_home', HiddenType::class, [])
                    ->add('title_announcement', HiddenType::class, [])
                    ->add('title_date', HiddenType::class, [])
                    ->add('title_discussion', HiddenType::class, [])
                    ->add('title_institution', HiddenType::class, [])
                    ->add('title_group', HiddenType::class, [])
                    ->add('title_material', HiddenType::class, [])
                    ->add('title_project', HiddenType::class, [])
                    ->add('title_todo', HiddenType::class, [])
                    ->add('title_topic', HiddenType::class, [])
                    ->add('title_user', HiddenType::class, [])
                    ->add('message', CKEditorType::class, [
                        'inline' => false,
                        'attr' => ['class' => 'uk-form-width-large'],
                    ])

                    // TODO: replace this manually added, static list of hidden fields with a CollectionType containing HiddenFields (dynamically build from available rubrics)
                    ->add('description_home', HiddenType::class, [])
                    ->add('description_announcement', HiddenType::class, [])
                    ->add('description_date', HiddenType::class, [])
                    ->add('description_discussion', HiddenType::class, [])
                    ->add('description_institution', HiddenType::class, [])
                    ->add('description_group', HiddenType::class, [])
                    ->add('description_material', HiddenType::class, [])
                    ->add('description_project', HiddenType::class, [])
                    ->add('description_todo', HiddenType::class, [])
                    ->add('description_topic', HiddenType::class, [])
                    ->add('description_user', HiddenType::class, [])
            )
            ->add(
                $builder->create('email_configuration', FormType::class, ['required' => false])
                    ->add('array_mail_text_rubric', ChoiceType::class, ['expanded' => false, 'multiple' => false, 'choices' => $options['emailTextTitles'], 'data' => '-1'])

                    // ->add('moderation_mail_body_de', CKEditorType::class, array(
                    //     'label' => 'body_de',
                    //     'inline' => false,
                    //     'attr' => array(
                    //         'class' => 'uk-form-width-large',
                    //     ),
                    // ))
                    // ->add('moderation_mail_body_en', CKEditorType::class, array(
                    //     'label' => 'body_en',
                    //     'inline' => false,
                    //     'attr' => array(
                    //         'class' => 'uk-form-width-large',
                    //     ),
                    // ))

                    ->add('moderation_mail_body_de', TextareaType::class, ['label' => 'body_de', 'attr' => ['class' => 'uk-form-width-large', 'style' => 'width: 100%;']])
                    ->add('moderation_mail_body_en', TextareaType::class, ['label' => 'body_en', 'attr' => ['class' => 'uk-form-width-large', 'style' => 'width: 100%;']])
                    ->add('mail_body_hello_de', HiddenType::class, [])
                    ->add('mail_body_hello_en', HiddenType::class, [])
                    ->add('mail_body_ciao_de', HiddenType::class, [])
                    ->add('mail_body_ciao_en', HiddenType::class, [])
                    ->add('mail_body_user_account_delete_de', HiddenType::class, [])
                    ->add('mail_body_user_account_delete_en', HiddenType::class, [])
                    ->add('mail_body_user_account_lock_de', HiddenType::class, [])
                    ->add('mail_body_user_account_lock_en', HiddenType::class, [])
                    ->add('mail_body_user_status_user_de', HiddenType::class, [])
                    ->add('mail_body_user_status_user_en', HiddenType::class, [])
                    ->add('mail_body_user_status_moderator_de', HiddenType::class, [])
                    ->add('mail_body_user_status_moderator_en', HiddenType::class, [])
                    ->add('mail_body_user_make_contact_person_de', HiddenType::class, [])
                    ->add('mail_body_user_make_contact_person_en', HiddenType::class, [])
                    ->add('mail_body_user_unmake_contact_person_de', HiddenType::class, [])
                    ->add('mail_body_user_unmake_contact_person_en', HiddenType::class, [])
                    ->add('mail_body_user_status_user_read_only_de', HiddenType::class, [])
                    ->add('mail_body_user_status_user_read_only_en', HiddenType::class, [])
                    ->add('mail_body_user_account_password_de', HiddenType::class, [])
                    ->add('mail_body_user_account_password_en', HiddenType::class, [])
                    ->add('mail_body_user_account_merge_de', HiddenType::class, [])
                    ->add('mail_body_user_account_merge_en', HiddenType::class, [])
            )
            ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary']]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['roomId', 'emailTextTitles'])
            ->setDefaults(['translation_domain' => 'settings']);
    }
}
