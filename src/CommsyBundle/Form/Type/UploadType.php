<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommsyBundle\Entity\Materials;

class UploadType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('files', 'file', array(
                'label' => 'Files',
                'attr' => array(
                     'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                ),
                'required' => false,
                'translation_domain' => 'material',
                //'image_path' => 'webPath',
            ))
            ->add('save', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'Save',
                'translation_domain' => 'form',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array('uploadUrl'))
        ;
    }

    public function getName()
    {
        return 'upload';
    }
}