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

namespace App\Form\Type\Account;

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

class MergeAccountsType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
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
                'query_builder' => fn (AuthSourceRepository $er) => $er->createQueryBuilder('a')
                    ->where('a.portal = :portal')
                    ->andWhere('a.enabled = true')
                    ->andWhere('a NOT INSTANCE OF :type')
                    ->setParameter('portal', $portal)
                    ->setParameter('type', 'guest'),
                'choice_label' => fn (AuthSource $authSource) => $authSource->getTitle().'('.$authSource->getType().')',
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
    public function getBlockPrefix(): string
    {
        return 'profile_mergeaccounts';
    }
}
