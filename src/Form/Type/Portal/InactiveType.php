<?php
namespace App\Form\Type\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class InactiveType extends AbstractType
{

    private $securityContext;

    public function __construct(Security $securityContext)
    {
        $this->securityContext = $securityContext;
    }
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lock_user', Types\TextType::class, [
                'label' => 'Lock user',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('email_before_lock', Types\TextType::class, [
                'label' => 'Email before lock',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('delete_user', Types\TextType::class, [
                'label' => 'Delete user',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('email_before_delete', Types\TextType::class, [
                'label' => 'Email before delete',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ]);
    }

    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null
        ));
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'portal',
        ]);
    }
}
