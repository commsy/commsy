<?php
namespace App\Form\Type\Custom;

use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

/**
 * Custom form type based on the Lexik `CheckboxFilterType` which by default inserts a horizontal rule
 * after the form field (via the custom `AbstractTypeWithSeparator` form).
 */
class CheckboxFilterWithSeparatorType extends AbstractTypeWithSeparator
{
    /**
     * Returns the name of the parent type.
     *
     * @return string|null The name of the parent type if any, null otherwise
     */
    public function getParent()
    {
        return Filters\CheckboxFilterType::class;
    }
}