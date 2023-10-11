<?php

namespace App\Filter;

use App\Entity\AuthSource;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\ChoiceFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\EntityFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('search', TextFilterType::class, [
                'label' => 'Search for user IDs',
                'required' => false,
                'translation_domain' => 'portal',
                'help' => 'Account index search string help',
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }

                    $tokens = explode(' ', $values['value']);

                    $expr = $filterQuery->getExpr();
                    $qb = $filterQuery->getQueryBuilder();

                    foreach ($tokens as $num => $token) {
                        $fieldOr = $expr->orX();
                        foreach (['username', 'email', 'firstname', 'lastname'] as $field) {
                            $fieldOr->add($expr->like('a.' . $field, ':token' . $num));
                        }

                        $qb->andWhere($fieldOr);
                    }

                    foreach ($tokens as $num => $token) {
                        $qb->setParameter("token$num", "%$token%");
                    }

                    return $qb;
                },
            ])
            ->add('status', ChoiceFilterType::class, [
                'choices'  => [
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
                'placeholder' => 'All',
                'label' => 'Status',
                'translation_domain' => 'portal',
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }

                    $status = $values['value'];

                    /** @var Expr $expression */
                    $expr = $filterQuery->getExpr();

                    /** @var QueryBuilder $qb */
                    $qb = $filterQuery->getQueryBuilder();

                    switch ($status) {
                        // Members
                        case 1:
                            $qb
                                ->leftJoin(User::class, 'ru', Join::WITH, 'ru.userId = a.username AND ru.authSource = a.authSource')
                                ->innerJoin(Room::class, 'r', Join::WITH, 'r.itemId = ru.contextId')
                                ->andWhere('r.itemId IS NOT NULL')
                                ->andWhere('ru.isNotDeleted = :notDeleted')
                                ->andWhere('r.deleter IS NULL')
                                ->andWhere('r.deletionDate IS NULL')
                                ->andWhere('(r.type = :project OR r.type = :community)')
                                ->setParameter('notDeleted', true)
                                ->setParameter('project', 'project')
                                ->setParameter('community', 'community');
                            break;

                        // Locked
                        case 2:
                            $qb
                                ->andWhere($expr->eq('a.locked', ':locked'))
                                ->setParameter('locked', true);
                            break;

                        // Requesting
                        case 3:
                            $qb
                                ->innerJoin(User::class, 'pu', Join::WITH, 'pu.contextId = a.contextId AND pu.userId = a.username AND pu.authSource = a.authSource')
                                ->andWhere($expr->eq('pu.status', ':status'))
                                ->setParameter('status', 1);
                            break;

                        // User
                        case 4:
                            $qb
                                ->innerJoin(User::class, 'pu', Join::WITH, 'pu.contextId = a.contextId AND pu.userId = a.username AND pu.authSource = a.authSource')
                                ->andWhere($expr->eq('pu.status', ':status'))
                                ->setParameter('status', 2);
                            break;

                        // Moderator
                        case 5:
                            $qb
                                ->innerJoin(User::class, 'pu', Join::WITH, 'pu.contextId = a.contextId AND pu.userId = a.username AND pu.authSource = a.authSource')
                                ->andWhere($expr->eq('pu.status', ':status'))
                                ->setParameter('status', 3);
                            break;

                        // Contact
                        case 6:
                            $qb
                                ->innerJoin(User::class, 'pu', Join::WITH, 'pu.contextId = a.contextId AND pu.userId = a.username AND pu.authSource = a.authSource')
                                ->andWhere($expr->eq('pu.isContact', ':contact'))
                                ->setParameter('contact', true);
                            break;

                        // Community workspace moderator
                        case 7:
                            $qb
                                ->leftJoin(User::class, 'ru', Join::WITH, 'ru.userId = a.username AND ru.authSource = a.authSource')
                                ->innerJoin(Room::class, 'r', Join::WITH, 'r.itemId = ru.contextId')
                                ->andWhere('ru.isNotDeleted = :notDeleted')
                                ->andWhere('ru.status = :status')
                                ->andWhere('r.deleter IS NULL')
                                ->andWhere('r.deletionDate IS NULL')
                                ->andWhere('r.type = :type')
                                ->setParameter('notDeleted', true)
                                ->setParameter('type', 'community')
                                ->setParameter('status', 3);
                            break;

                        // Community workspace contact
                        case 8:
                            $qb
                                ->leftJoin(User::class, 'ru', Join::WITH, 'ru.userId = a.username AND ru.authSource = a.authSource')
                                ->innerJoin(Room::class, 'r', Join::WITH, 'r.itemId = ru.contextId')
                                ->andWhere('ru.isNotDeleted = :notDeleted')
                                ->andWhere('ru.isContact = :contact')
                                ->andWhere('r.deleter IS NULL')
                                ->andWhere('r.deletionDate IS NULL')
                                ->andWhere('r.type = :type')
                                ->setParameter('notDeleted', true)
                                ->setParameter('type', 'community')
                                ->setParameter('contact', true);
                            break;

                        // Project workspace moderator
                        case 9:
                            $qb
                                ->leftJoin(User::class, 'ru', Join::WITH, 'ru.userId = a.username AND ru.authSource = a.authSource')
                                ->innerJoin(Room::class, 'r', Join::WITH, 'r.itemId = ru.contextId')
                                ->andWhere('ru.isNotDeleted = :notDeleted')
                                ->andWhere('ru.status = :status')
                                ->andWhere('r.deleter IS NULL')
                                ->andWhere('r.deletionDate IS NULL')
                                ->andWhere('r.type = :type')
                                ->setParameter('notDeleted', true)
                                ->setParameter('type', 'project')
                                ->setParameter('status', 3);
                            break;

                        // project workspace contact
                        case 10:
                            $qb
                                ->leftJoin(User::class, 'ru', Join::WITH, 'ru.userId = a.username AND ru.authSource = a.authSource')
                                ->innerJoin(Room::class, 'r', Join::WITH, 'r.itemId = ru.contextId')
                                ->andWhere('ru.isNotDeleted = :notDeleted')
                                ->andWhere('ru.isContact = :contact')
                                ->andWhere('r.deleter IS NULL')
                                ->andWhere('r.deletionDate IS NULL')
                                ->andWhere('r.type = :type')
                                ->setParameter('notDeleted', true)
                                ->setParameter('type', 'project')
                                ->setParameter('contact', true);
                            break;

                        // moderator of any workspace
                        case 11:
                            $qb
                                ->leftJoin(User::class, 'ru', Join::WITH, 'ru.userId = a.username AND ru.authSource = a.authSource')
                                ->innerJoin(Room::class, 'r', Join::WITH, 'r.itemId = ru.contextId')
                                ->andWhere('ru.status = :status')
                                ->andWhere('ru.isNotDeleted = :notDeleted')
                                ->andWhere('r.deleter IS NULL')
                                ->andWhere('r.deletionDate IS NULL')
                                ->setParameter('notDeleted', true)
                                ->setParameter('status', 3);
                            break;

                        // contact of any workspace
                        case 12:
                            $qb
                                ->leftJoin(User::class, 'ru', Join::WITH, 'ru.userId = a.username AND ru.authSource = a.authSource')
                                ->innerJoin(Room::class, 'r', Join::WITH, 'r.itemId = ru.contextId')
                                ->andWhere('ru.isContact = :contact')
                                ->andWhere('ru.isNotDeleted = :notDeleted')
                                ->andWhere('r.deleter IS NULL')
                                ->andWhere('r.deletionDate IS NULL')
                                ->setParameter('notDeleted', true)
                                ->setParameter('contact', true);
                            break;

                        // no workspace membership
                        case 13:
                            $qb
                                ->leftJoin(User::class, 'ru', Join::WITH, 'ru.userId = a.username AND ru.authSource = a.authSource')
                                ->leftJoin(Room::class, 'r', Join::WITH, 'r.itemId = ru.contextId')
                                ->andWhere('ru.isNotDeleted = :notDeleted')
                                ->groupBy('ru.userId')
                                ->having('COUNT(ru.userId) = 2')
                                ->setParameter('notDeleted', true);
                            break;
                    }

                    return $qb;
                },
            ])
            ->add('authSource', EntityFilterType::class, [
                'class' => AuthSource::class,
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('a')
                        ->where('a.portal = :portalId')
                        ->setParameter('portalId', 1);
                },
                'choice_label' => 'title',
                'label' => 'authSource',
                'translation_domain' => 'portal',
                'placeholder' => 'All',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Search',
                'translation_domain' => 'portal',
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'account_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['portalId'])
            ->setAllowedTypes('portalId', 'int')
            ->setDefaults([
                'csrf_protection' => false,
                'validation_groups' => ['filtering'], // avoid NotBlank() constraint-related message
                'method' => 'get',
                'translation_domain' => 'form',
            ]
        );
    }
}
