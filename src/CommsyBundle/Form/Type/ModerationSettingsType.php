<?php
namespace CommsyBundle\Form\Type;

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

use Ivory\CKEditorBundle\Form\Type\CKEditorType;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class ModerationSettingsType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
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

        $builder
            ->add(
                $builder->create('homenotice', FormType::class, array(
                    'required' => false
                ))
                ->add('item_id', TextType::class, array(
                    'label' => 'Content ID',
                )) 
                ->add('show_information_box', ChoiceType::class, array(
                    'label' => false,
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        'Show' => '1',
                        'Do not show' => '0',
                    ),
                    'data' => '0',
                ))
            )
            ->add(
                $builder->create('usernotice', FormType::class, array(
                    'required' => false,
                ))
                ->add('array_info_text_rubric', ChoiceType::class, array(
                    'expanded' => false,
                    'multiple' => false,
                    // TODO: load real rubrics here
                    'choices' => array(
                        "Rubric 1" => "rubric1",
                        "Rubric 2" => "rubric2",
                    ),
                ))
                // TODO: instead of a single "moderation_title" input field, we need an individual title input fields for each rubric!
                ->add('moderation_title', TextType::class, array('label' => 'Title'))

                
                ->add('message', CKEditorType::class, [
                    'inline' => false,
                     'attr' => array(
                        'class' => 'uk-form-width-large',
                    ),
                ])

                // ->add('message', TextareaType::class, [
                //      'attr' => array(
                //         'class' => 'uk-form-width-large',
                //     ),
                // ])

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
                    'choices' => array(
                        'Select e-mail text' => '-1',
                        '------------------' => 'disabled',
                        'Address' => 'MAIL_BODY_HELLO',                                               // 2
                        'Salutation' => 'MAIL_BODY_CIAO',                                             // 3
                        'Delete account' => 'MAIL_BODY_USER_ACCOUNT_DELETE',                          // 5
                        'Lock account' => 'MAIL_BODY_USER_ACCOUNT_LOCK',                              // 6
                        'Approve membership' => 'MAIL_BODY_USER_STATUS_USER',                         // 7
                        'Change status: moderator' => 'MAIL_BODY_USER_STATUS_MODERATOR',              // 8
                        'Change status: contact person' => 'MAIL_BODY_USER_MAKE_CONTACT_PERSON',      // 9
                        'Change status: no contact person' => 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', // 10
                        'Change status: read only user' => 'MAIL_BODY_USER_STATUS_USER_READ_ONLY',    // 11
                    ),
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
                    ),
                ))
                ->add('moderation_mail_body_en', TextareaType::class, array(
                    'label' => 'body_en',
                    'attr' => array(
                        'class' => 'uk-form-width-large',                        
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
                'position' => 'last',
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
            ->setRequired(['roomId'])
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
