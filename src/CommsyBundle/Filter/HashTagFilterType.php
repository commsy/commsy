<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityRepository;

class HashTagFilterType extends AbstractType
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // extract room id from request and build filter accordingly
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $attributes = $currentRequest->attributes;
            if ($attributes->has('roomId')) {
                $roomId = $attributes->getInt('roomId');

                $builder
                    ->add('hashtag', 'hashtag', array(
                        'class' => 'CommsyBundle:Labels',
                        'query_builder' => function (EntityRepository $er) use ($roomId) {
                            return $er->createQueryBuilder('l')
                                ->andWhere('l.contextId = :roomId')
                                ->andWhere('l.type = :type')
                                ->andWhere('l.deletionDate IS NULL')
                                ->andWhere('l.deleter IS NULL')
                                ->setParameter('roomId', $roomId)
                                ->setParameter('type', 'buzzword');
                        },
                        'choice_label' => 'name',
                        'placeholder' => false,
                        'translation_domain' => 'form',
                        'expanded' => true,
                        'label' => false,
                    ))
                ;
            }
        }
    }

    public function getName()
    {
        return 'hashtag_filter';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'validation_groups' => array('filtering') // avoid NotBlank() constraint-related message
        ));
    }
}