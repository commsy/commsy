<?php


namespace App\Form\Type\Custom;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlatPickrType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new DateTimeToStringTransformer(
            null,
            null,
            \Datetime::ATOM
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $flatPickrOptions = [];
        $flatPickrOptions['enableTime'] = ($options['input'] === 'datetime' || $options['input'] === 'time');
        $flatPickrOptions['noCalendar'] = ($options['input'] === 'time');

        $view->vars['flatPickrOptions'] = $flatPickrOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'input' => 'datetime',
        ]);

        $resolver->setAllowedValues('input', [
            'datetime',
            'time',
            'date'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'flatpickr';
    }
}