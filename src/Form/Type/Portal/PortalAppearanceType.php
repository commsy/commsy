<?php


namespace App\Form\Type\Portal;


use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;

class PortalAppearanceType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Portal $portal */
        $portal = $options['data'];

        $builder
            ->add('logoFile', VichImageType::class, [
                'download_uri' => false,
                'image_uri' => $this->router->generate('app_file_portallogo', [
                    'portalId' => $portal->getId(),
                ]),
                'required' => false,
                'label' => 'Logo',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Portal::class,
        ]);
    }
}