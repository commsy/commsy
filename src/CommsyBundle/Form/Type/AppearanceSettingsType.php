<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class AppearanceSettingsType extends AbstractType
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

        $themeChoices = array_combine($options['themes'], $options['themes']);

        $builder
            ->add('theme', 'choice', array(
                'label' => 'theme',
                'choices' => $themeChoices,
                'choices_as_values' => true,
                'constraints' => array(
                    new NotBlank(),
                ),
                'translation_domain' => 'form'
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
            ->setRequired(array('roomId', 'themes'))
        ;
    }

    public function getName()
    {
        return 'appearance_settings';
    }
}