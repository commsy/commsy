<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class RoomFilterType extends AbstractType
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
            ->add('title', Filters\TextFilterType::class, [
                'label' => 'search-title-moderator-description',
                'translation_domain' => 'room',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }

                    $expr = $filterQuery->getExpr();

                    return $filterQuery->getQueryBuilder()
                        ->andWhere(
                            $expr->orX(
                                $expr->like('r.title', ':title'),
                                $expr->like('r.contactPersons', ':contactPersons'),
                                $expr->like('r.roomDescription', ':roomDescription')
                            )
                        )
                        ->setParameter('title', '%'.$values['value'].'%')
                        ->setParameter('contactPersons', '%'.$values['value'].'%')
                        ->setParameter('roomDescription', '%'.$values['value'].'%')
                    ;
                },
            ])
            ->add('membership', Filters\CheckboxFilterType::class, [
                'label' => 'hide-rooms-without-membership',
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'mapped' => false,
                'translation_domain' => 'room',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ])
            ->add('archived', Filters\CheckboxFilterType::class, [
                'label' => 'hide-archived-rooms',
                'apply_filter' => false, // disable filter
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'mapped' => false,
                'translation_domain' => 'room',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ])
            ->add('type', Filters\ChoiceFilterType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choices' => [
                    'Project Rooms' => 'project',
                    'Community Rooms' => 'community',
                ],
                'placeholder' => 'All',
                'translation_domain' => 'room',
            ])
            ->add('submit', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'Suchen',
            ));

        if ($options['showTime']) {
            $builder
                ->add('timePulses', Filters\ChoiceFilterType::class, [
                    'label' => 'time pulses',
                    'attr' => [
                        'onchange' => 'this.form.submit()',
                    ],
                    'choices' => $options['timePulses'],
                    'placeholder' => 'All',
                    'translation_domain' => 'room',
                ]);
        }
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
        return 'room_filter';
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['showTime', 'timePulses'])
            ->setDefaults([
                'csrf_protection'   => false,
                'validation_groups' => ['filtering'], // avoid NotBlank() constraint-related message
                'method'            => 'get',
            ])
        ;
    }
}