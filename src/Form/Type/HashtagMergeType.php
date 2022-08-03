<?php

namespace App\Form\Type;

use App\Entity\Labels;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HashtagMergeType extends AbstractType
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
        $builder
            ->add('first', EntityType::class, [
                'class' => Labels::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('l')
                        ->andWhere('l.contextId = :roomId')
                        ->andWhere('l.type = :type')
                        ->andWhere('l.deletionDate IS NULL')
                        ->andWhere('l.deleter IS NULL')
                        ->setParameter('roomId', $options['roomId'])
                        ->setParameter('type', 'buzzword');
                },
                'label' => false,
                'choice_label' => 'name',
                'placeholder' => 'Choose a hashtag',
                'translation_domain' => 'hashtag',
            ])
            ->add('second', EntityType::class, [
                'class' => Labels::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('l')
                        ->andWhere('l.contextId = :roomId')
                        ->andWhere('l.type = :type')
                        ->andWhere('l.deletionDate IS NULL')
                        ->andWhere('l.deleter IS NULL')
                        ->setParameter('roomId', $options['roomId'])
                        ->setParameter('type', 'buzzword');
                },
                'label' => false,
                'choice_label' => 'name',
                'placeholder' => 'Choose a hashtag',
                'translation_domain' => 'hashtag',
            ])
            ->add('combine', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'Combine',
                'translation_domain' => 'hashtag',
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
            ->setRequired(['roomId']);
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
        return 'hashtag_merge';
    }
}
