<?php
namespace App\Form\Type\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form type which by default inserts a horizontal rule after the form's field(s)
 * (via the custom `use_separator` form option).
 */
class AbstractTypeWithSeparator extends AbstractType
{
    // NOTE: setting the custom `use_separator` form option to true (which is the default for this form) will allow a
    // `form_row` block override in a Twig template to append a horizontal rule as a separator after the form's field(s)
    // 
    // EXAMPLE:
    // 
    //    {%- block form_row -%}
    //        {{- parent() -}}
    //        {%- if use_separator is defined and use_separator == true -%}
    //            <hr/>
    //        {%- endif -%}
    //    {%- endblock -%}

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['use_separator'] = $options['use_separator'];

        parent::buildView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('use_separator', $options['use_separator']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'use_separator' => true,
            ]);
    }
}
