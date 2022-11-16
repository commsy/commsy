<?php
namespace App\Form\Type\Portal;

use App\Entity\AccountIndex;
use App\Entity\AccountIndexUser;
use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class AccountIndexType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('accountIndexSearchString', Types\TextType::class, [
                'label' => 'Search for user IDs',
                'required' => false,
                'translation_domain' => 'portal',
                'help' => 'Account index search string help',
            ])
            ->add('userIndexFilterChoice', Types\ChoiceType::class, [
                'choices'  => [
                    'All' => 0,
                    'Members' => 1,
                    '-----------------' => 14,
                    'Locked' => 2,
                    'In activation' => 3,
                    'User' => 4,
                    'Moderator' => 5,
                    'Contact' => 6,
                    '------------------' => 15,
                    'Community moderators' => 7,
                    'Community contacts' => 8,
                    'Project moderators' => 9,
                    'Project contacts' => 10,
                    'Moderators of workspaces' => 11,
                    'Contacts of workspaces' => 12,
                    '-------------------' => 16,
                    'No workspaces participation' => 13,
                ],
                'required' => true,
                'label' => 'Status',
                'translation_domain' => 'portal',
            ])
            ->add('search', Types\SubmitType::class, [
                'label' => 'Search',
                'translation_domain' => 'portal',
            ])
            ->add('ids', Types\CollectionType::class, [
                // each entry in the array will be an "Checkbox" field
                'entry_type' => Types\CheckboxType::class,
                'required' => false,
            ])
            ->add('indexViewAction', Types\ChoiceType::class, [
                'choices'  => [
                    'No action' => 0,
                    '-----------------' => 16,
                    'Delete user id(s)' => 1,
                    'Lock user id(s)' => 2,
                    'Activate user id(s)' => 3,
                    'Email change login' => 4,
                    '------------------' => 17,
                    'Satus user' => 5,
                    'Status moderator' => 6,
                    '-------------------' => 18,
                    'Make contact' => 7,
                    'Remove contact' => 8,
                    '--------------------' => 19,
                    'Send mail' => 9,
                    'Mail combine ids' => 11,
                    '---------------------' => 20,
                    'Hide mail all wrks' => 13,
                    'Show mail all wrks' => 15,
                ],
                'required' => true,
                'label' => 'All',
                'translation_domain' => 'portal',
            ])
            ->add('execute', Types\SubmitType::class, [
                'label' => 'Execute',
                'translation_domain' => 'portal',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AccountIndex::class,
            'translation_domain' => 'portal',
        ]);
    }
}
