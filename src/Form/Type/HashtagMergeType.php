<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class HashtagMergeType extends AbstractType
{
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
        $builder
            ->add('first', EntityType::class, array(
                    'class' => 'App:Labels',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('l')
                                ->andWhere('l.contextId = :roomId')
                                ->andWhere('l.type = :type')
                                ->andWhere('l.deletionDate IS NULL')
                                ->andWhere('l.deleter IS NULL')
                                ->setParameter('roomId', $options['roomId'] )
                                ->setParameter('type', 'buzzword');
                                        },
                    'label' => false,
                    'choice_label' => 'name',

                ))

            ->add('second', EntityType::class, array(
                    'class' => 'App:Labels',
                    'query_builder' => function (EntityRepository $er) use ($options){
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

                ))



            ->add('combine', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-primary',
                        ),
                        'label' => 'Combine',
                        'translation_domain' => 'hashtag',
                        'validation_groups' => false,
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
        $resolver
            ->setRequired(['roomId'])
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
        return 'hashtag_merge';
    }
}