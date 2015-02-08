<?php
namespace CommSy\RoomBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RoomFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', 'filter_choice', array(
            'choices' => array(
                '' => 'Raumtyp',
                'all' => 'Alle Räume',
                'project' => 'Projekträume',
                'community' => 'Gemeinschaftsräume',
            ),
            'multiple' => true,
            'placeholder' => 'Raumtyp',
        ));
    }

    public function getName()
    {
        return 'room_filter';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'validation_groups' => array('filtering')
        ));
    }
}