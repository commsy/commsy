<?php
namespace App\Form\Type\Portal;

use App\Entity\AccountIndex;
use App\Entity\AccountIndexUser;
use App\Entity\Portal;
use App\Form\DataTransformer\AccountsToPortalUserIdsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class AccountIndexType extends AbstractType
{
    /**
     * @var AccountsToPortalUserIdsTransformer
     */
    private AccountsToPortalUserIdsTransformer $transformer;

    public function __construct(AccountsToPortalUserIdsTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('accounts', Types\CollectionType::class, [
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
                    'Mail id password' => 10,
                    'Mail combine ids' => 11,
                    '---------------------' => 20,
                    'Hide mail' => 12,
                    'Hide mail all wrks' => 13,
                    'Show mail' => 14,
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

        $builder->get('accounts')->addModelTransformer($this->transformer);
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
