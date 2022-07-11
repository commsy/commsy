<?php
namespace App\Form\Type\Context;

use App\Form\Type\Custom\Select2ChoiceType;
use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_environment;
use cs_list;
use cs_user_item;
use Generator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

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
        // NOTE: opposed to the Type/ProjectType.php form, the `data` option somehow won't preselect
        // any default template that's defined in `preferredChoices`, maybe due to this form being
        // added as 'type_sub' in `AddContextFieldListener`? However, `empty_data` will work here.
        $builder->add('master_template', ChoiceType::class, [
                'choices' => $options['templates'],
                'placeholder' => false,
                'preferred_choices' => $options['preferredChoices'],
                'required' => false,
                'mapped' => false,
                'label' => 'Template',
                'empty_data' => (!empty($options['preferredChoices'])) ? $options['preferredChoices'][0] : '',
            ]);
        if (!empty($options['times'])) {
            $builder->add('time_interval', Select2ChoiceType::class, [
                'choices' => $options['times'],
                'required' => false,
                'mapped' => false,
                'expanded' => false,
                'multiple' => true,
                'label' => $options['timesDisplay'],
                'translation_domain' => 'room',
            ]);
        }
        $builder->add('community_rooms', Select2ChoiceType::class, [
            'choice_loader' => new CallbackChoiceLoader(function() {
                $currentPortalItem = $this->legacyEnvironment->getCurrentPortalItem();
                $currentUser = $this->legacyEnvironment->getCurrentUserItem();

                $communityManager = $this->legacyEnvironment->getCommunityManager();
                $communityManager->setContextLimit($currentPortalItem->getItemID());
                $communityManager->select();
                $communityList = $communityManager->get();

                function getChoices(cs_list $communityList, cs_user_item $currentUser): Generator
                {
                    foreach ($communityList as $communityRoom) {
                        /** @var cs_community_item $communityRoom */
                        if ($communityRoom->isAssignmentOnlyOpenForRoomMembers() === false ||
                            $communityRoom->isUser($currentUser)) {
                            yield html_entity_decode($communityRoom->getTitle()) => $communityRoom->getItemID();
                        }
                    }
                }

                return iterator_to_array(getChoices($communityList, $currentUser));
            }),
            'required' => $options['linkCommunitiesMandantory'],
            'mapped' => false,
            'multiple' => true,
            'expanded' => false,
            'label' => 'Community rooms',
            'help' => 'Community rooms tip',
            'translation_domain' => 'settings',
        ])
        ->add('createUserRooms', CheckboxType::class, [
            'label' => 'User room',
            'translation_domain' => 'settings',
            'required' => false,
            'label_attr' => array(
                'class' => 'uk-form-label',
            ),
            'help' => 'User room tooltip',
        ])
        ->add('userroom_template', ChoiceType::class, [
            'choices' => $options['templates'],
            'placeholder' => false,
            'required' => false,
            'mapped' => false,
            'label' => 'User room template',
            'translation_domain' => 'settings',
        ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['types', 'templates', 'preferredChoices', 'timesDisplay', 'times', 'linkCommunitiesMandantory', 'roomCategories', 'linkRoomCategoriesMandatory'])
            ->setDefaults(array('translation_domain' => 'form'))
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
        return 'project';
    }
}