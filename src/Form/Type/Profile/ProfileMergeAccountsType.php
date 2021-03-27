<?php

namespace App\Form\Type\Profile;

use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Repository\AuthSourceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileMergeAccountsType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $portal = $options['portal'];

        $builder
            ->add('combineUserId', TextType::class, [
                'label' => 'combineUserId',
                'required' => true,
            ])
            ->add('combinePassword', PasswordType::class, [
                'label' => 'combinePassword',
                'required' => true,
            ])
            ->add('auth_source', EntityType::class, [
                'class' => AuthSource::class,
                'query_builder' => function (AuthSourceRepository $er) use ($portal) {
                    return $er->createQueryBuilder('a')
                        ->where('a.portal = :portal')
                        ->andWhere('a.enabled = true')
                        ->setParameter('portal', $portal);
                },
                'choice_label' => function (AuthSource $authSource) {
                    return $authSource->getTitle() . '(' . $authSource->getType() . ')';
                },
                'label' => 'authSource',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['portal'])
            ->setAllowedTypes('portal', Portal::class)
            ->setDefaults(['translation_domain' => 'profile']);
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
        return 'profile_mergeaccounts';
    }

}
