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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class AdditionalSettingsType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function __construct(EntityManager $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
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

        $builder
            ->add(
                $builder->create('structural_auxilaries', FormType::class, array('compound' => true))
                ->add('tags', ChoiceType::class, array(
                    'label_attr' => array('class' => 'uk-form-label'),
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => array(
                        'Activate' => 'activate',
                        'Mandatory assignment' => 'mandatory_assignment',
                    ),
                ))
                ->add('categories', ChoiceType::class, array(
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => array(
                        'Activate' => 'activate',
                        'Mandatory assignment' => 'mandatory_assignment',
                        'Access' => 'access_management',
                    ),
                    'choice_attr' => array(
                        'label_attr' => array('class' => 'uk-foobar'),
                    ),
                ))
            )
            ->add(
                $builder->create('tasks', FormType::class, array('compound' => true))
                ->add('new_status', TextType::class, array(
                ))
                ->add('add_status', ButtonType::class, array(
                ))
            )
            ->add('rss_feed', ChoiceType::class, array(
                'expanded' => true,
                'multiple' => false,
                'choices' => array(
                    'Switch on' => 'on',
                    'Switch off' => 'off',
                ),
            ))
            ->add(
                $builder->create('template', FormType::class, array('compound' => true))
                ->add('status', CheckboxType::class, array(
                    'label_attr' => array('class' => 'uk-form-label'),
                ))
                ->add('target_group', ChoiceType::class, array(
                    'expanded' => false,
                    'multiple' => false,
                    'choices' => array(
                        'All commsy users' => 'all_commsy',
                        'All workspace users' => 'all_workspace',
                        'Workspace mods only' => 'mods_only',
                    ),
                ))
                ->add('text', TextareaType::class, array(
                    'attr' => array(
                        'style' => 'width: 90%',
                    ),
                ))
            )
            ->add('archive_room', CheckboxType::class, array(
                'label_attr' => array('class' => 'uk-form-label'),
            ))
            ->add(
                $builder->create('terms', FormType::class, array('compound' => true))
                ->add('status', ChoiceType::class, array(
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        'Yes' => 'yes',
                        'No' => 'no',
                    ),
                ))
                ->add('language', ChoiceType::class, array(
                    'expanded' => false,
                    'multiple' => false,
                    'choices' => array(
                        'German' => 'german',
                        'English' => 'english',
                    ),
                ))
                ->add('text', CKEditorType::class, array(
                    'inline' => false,
                ))
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
        return 'general_settings';
    }
}
