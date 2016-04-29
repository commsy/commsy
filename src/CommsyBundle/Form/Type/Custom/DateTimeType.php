<?php
namespace CommsyBundle\Form\Type\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

// use CommsyBundle\Form\DataTransformer\DateTimeTransformer;

class DateTimeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', 'datetime', array(
                'input'  => 'datetime',
                'label' => false,
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'required' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}'
                )
            ))
            ->add('time', 'datetime', array(
                'input'  => 'datetime',
                'label' => false,
                'widget' => 'single_text',
                'format' => 'HH:mm',
                'required' => false,
                'attr' => array(
                    'data-uk-timepicker' => ''
                )
            ));

            // $builder->appendClientTransformer(new DateTimeTransformer());

    }

    public function getParent()
    {
        return 'form';
    }

    public function getName()
    {
        return 'date_time';
    }
}