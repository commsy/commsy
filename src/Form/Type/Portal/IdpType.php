<?php


namespace App\Form\Type\Portal;

use App\Form\DataTransformer\IdpTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\IdP;

class IdpType extends AbstractType
{

    private $transformer;

    public function __construct(IdpTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $idpOptions = $options['idps_options_array'];
        $builder
            ->add('idps', ChoiceType::class, [
                'choices' => $idpOptions,
            ])
            ->add('name', TextType::class, [
                'label' => 'Idp Name',
                'required' => false
            ])
            ->add('url', TextType::class, [
                'label' => 'Idp URI',
                'required' => false
            ])
            ->add('idps', ChoiceType::class, [
                'choices' => $idpOptions,
                'label' => 'Available idps',
            ])
            ->add('add', SubmitType::class, [
                'label' => 'Save',
            ])
            ->add('remove', SubmitType::class, [
                'label' => 'Remove',
            ])
            ->add('edit', SubmitType::class, [
                'label' => 'Edit',
            ])
            ->add('back', SubmitType::class, [
                'label' => 'Back',
            ])
        ;

        $builder->get('idps')
            ->addModelTransformer($this->transformer);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
            'translation_domain' => 'portal',
        ])
            ->setRequired('idps_options_array')
        ;
    }
}