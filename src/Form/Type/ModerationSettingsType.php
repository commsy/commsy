<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Translation\TranslatorInterface;

use FOS\CKEditorBundle\Form\Type\CKEditorType;

use App\Services\LegacyEnvironment;
use App\Validator\Constraints\HomeNoticeConstraint;

class ModerationSettingsType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function __construct(LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     * 
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $this->roomItem = $roomManager->getItem($options['roomId']);
        $availableRubrics = array_merge(['home'], $this->roomItem->getAvailableRubrics());
        $rubricOptions = [];
        foreach ($availableRubrics as $rubric) {
            $translatedTitle = $this->translator->transChoice(ucfirst($rubric), 1, [], 'rubric');
            $rubricOptions[$translatedTitle] = $rubric;
        }

        $builder
            ->add(
                $builder->create('homenotice', FormType::class, array(
                    'required' => false
                ))
                ->add('item_id', TextType::class, array(
                    'label' => 'Content ID',
                    'constraints' => array(
                        new HomeNoticeConstraint(),
                    )
                )) 
                ->add('show_information_box', ChoiceType::class, array(
                    'label' => false,
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        'Show info' => '1',
                        'Do not show info' => '0',
                    ),
                ))
            )
            ->add(
                $builder->create('usernotice', FormType::class, array(
                    'required' => false,
                ))
                ->add('array_info_text_rubric', ChoiceType::class, array(
                    'expanded' => false,
                    'multiple' => false,
                    'choices' => $rubricOptions,
                ))

                ->add('moderation_title', TextType::class, array('label' => 'Title'))

                // TODO: replace this manually added, static list of hidden fields with a CollectionType containing HiddenFields (dynamically build from available rubrics)
                ->add('title_home', HiddenType::class, array())
                ->add('title_announcement', HiddenType::class, array())
                ->add('title_date', HiddenType::class, array())
                ->add('title_discussion', HiddenType::class, array())
                ->add('title_institution', HiddenType::class, array())
                ->add('title_group', HiddenType::class, array())
                ->add('title_material', HiddenType::class, array())
                ->add('title_project', HiddenType::class, array())
                ->add('title_todo', HiddenType::class, array())
                ->add('title_topic', HiddenType::class, array())
                ->add('title_user', HiddenType::class, array())


                ->add('message', CKEditorType::class, [
                    'inline' => false,
                     'attr' => array(
                        'class' => 'uk-form-width-large',
                    ),
                ])

                // TODO: replace this manually added, static list of hidden fields with a CollectionType containing HiddenFields (dynamically build from available rubrics)
                ->add('description_home', HiddenType::class, array())
                ->add('description_announcement', HiddenType::class, array())
                ->add('description_date', HiddenType::class, array())
                ->add('description_discussion', HiddenType::class, array())
                ->add('description_institution', HiddenType::class, array())
                ->add('description_group', HiddenType::class, array())
                ->add('description_material', HiddenType::class, array())
                ->add('description_project', HiddenType::class, array())
                ->add('description_todo', HiddenType::class, array())
                ->add('description_topic', HiddenType::class, array())
                ->add('description_user', HiddenType::class, array())

            )
            ->add(
                $builder->create('email_configuration', FormType::class, array(
                    'required' => false,
                ))
                ->add('array_mail_text_rubric', ChoiceType::class, array(
                    'expanded' => false,
                    'multiple' => false,
                    'choices' => $options['emailTextTitles'],
                    'data' => '-1',
                ))

                
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
                

                ->add('moderation_mail_body_de', TextareaType::class, array(
                    'label' => 'body_de',
                    'attr' => array(
                        'class' => 'uk-form-width-large',
                        'style' => 'width: 100%;',
                    ),
                ))
                ->add('moderation_mail_body_en', TextareaType::class, array(
                    'label' => 'body_en',
                    'attr' => array(
                        'class' => 'uk-form-width-large',
                        'style' => 'width: 100%;',
                    ),
                ))

                ->add('mail_body_hello_de', HiddenType::class, array())
                ->add('mail_body_hello_en', HiddenType::class, array())
                
                ->add('mail_body_ciao_de', HiddenType::class, array())
                ->add('mail_body_ciao_en', HiddenType::class, array())

                ->add('mail_body_user_account_delete_de', HiddenType::class, array())
                ->add('mail_body_user_account_delete_en', HiddenType::class, array())

                ->add('mail_body_user_account_lock_de', HiddenType::class, array())
                ->add('mail_body_user_account_lock_en', HiddenType::class, array())

                ->add('mail_body_user_status_user_de', HiddenType::class, array())
                ->add('mail_body_user_status_user_en', HiddenType::class, array())

                ->add('mail_body_user_status_moderator_de', HiddenType::class, array())
                ->add('mail_body_user_status_moderator_en', HiddenType::class, array())

                ->add('mail_body_user_make_contact_person_de', HiddenType::class, array())
                ->add('mail_body_user_make_contact_person_en', HiddenType::class, array())

                ->add('mail_body_user_unmake_contact_person_de', HiddenType::class, array())
                ->add('mail_body_user_unmake_contact_person_en', HiddenType::class, array())

                ->add('mail_body_user_status_user_read_only_de', HiddenType::class, array())
                ->add('mail_body_user_status_user_read_only_en', HiddenType::class, array())

                ->add('mail_body_user_account_password_de', HiddenType::class, array())
                ->add('mail_body_user_account_password_en', HiddenType::class, array())

                ->add('mail_body_user_account_merge_de', HiddenType::class, array())
                ->add('mail_body_user_account_merge_en', HiddenType::class, array())
            )
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                )                
            ))
        ;
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['roomId', 'emailTextTitles'])
            ->setDefaults(array('translation_domain' => 'settings'))
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
        return 'moderation_settings';
    }
}
