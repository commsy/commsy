<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class ExtensionSettingsType extends AbstractType
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($options['roomId']);

        $builder
            ->add('wikiEnabled', 'checkbox', array(
                'label' => 'wikiEnabled',
                'required' => false,
                'translation_domain' => 'form',
            ))
            ->add('save', 'submit', array(
                'position' => 'last',
                'label' => 'save',
                'translation_domain' => 'form'
            ));
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array('roomId'))
        ;
    }

    public function getName()
    {
        return 'extension_settings';
    }
}