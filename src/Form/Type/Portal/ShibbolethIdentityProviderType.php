<?php


namespace App\Form\Type\Portal;

use App\Entity\ShibbolethIdentityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShibbolethIdentityProviderType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'help' => 'Name',
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL',
                'required' => true,
                'help' => 'Initiator entityID',
            ])
        ;
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
                'data_class' => ShibbolethIdentityProvider::class,
                'translation_domain' => 'portal',
            ])
        ;
    }
}