<?php
namespace App\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Services\LegacyEnvironment;

class RoomFilterType extends AbstractType
{
    private $legacyEnvironment;

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
        $builder
            ->add('submit', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button uk-button-primary',
                ),
                'label' => 'Restrict',
                'translation_domain' => 'form',
            ))
            ->add('title', Filters\TextFilterType::class, [
                'label' => 'search-filter',
                'translation_domain' => 'room',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'attr' => [
                    'placeholder' => 'search-filter-placeholder',
                    'class' => 'cs-form-horizontal-full-width',
                ],
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }

                    $tokens = explode(' ', $values['value']);

                    $expr = $filterQuery->getExpr();
                    /** @var QueryBuilder $qb */
                    $qb = $filterQuery->getQueryBuilder();

                    foreach ($tokens as $num => $token) {
                        $fieldOr = $expr->orX();
                        foreach (['title', 'contactPersons', 'roomDescription'] as $field) {
                            $fieldOr->add($expr->like('r.' . $field, ':token' . $num));
                        }

                        $qb->andWhere($fieldOr);
                    }

                    foreach ($tokens as $num => $token) {
                        $qb->setParameter("token$num", "%$token%");
                    }

                    return $qb;
                },
            ])
            ->add('membership', Filters\CheckboxFilterType::class, [
                'label' => 'hide-rooms-without-membership',
                'mapped' => false,
                'translation_domain' => 'room',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ])
            ->add('archived', Filters\CheckboxFilterType::class, [
                'label' => 'hide-archived-rooms',
                'apply_filter' => false, // disable filter
                'mapped' => false,
                'translation_domain' => 'room',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ]);

        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $showRooms = $portalItem->getShowRoomsOnHome();
        if ($showRooms !== 'onlyprojectrooms' && $showRooms !== 'onlycommunityrooms') {
            $builder
                ->add('type', Filters\ChoiceFilterType::class, [
                    'choices' => [
                        'Project Rooms' => 'project',
                        'Community Rooms' => 'community',
                    ],
                    'placeholder' => 'All',
                    'translation_domain' => 'room',
                ]);
        }

        if ($options['showTime']) {
            $builder
                ->add('timePulses', Filters\ChoiceFilterType::class, [
                    'label' => $options['timePulsesDisplayName'],
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
            ->setRequired(['showTime', 'timePulses', 'timePulsesDisplayName'])
            ->setDefaults([
                'csrf_protection'   => false,
                'validation_groups' => ['filtering'], // avoid NotBlank() constraint-related message
                'method'            => 'get',
            ])
        ;
    }
}