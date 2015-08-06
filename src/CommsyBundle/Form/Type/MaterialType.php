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

class MaterialType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'Title',
                'attr' => array(
                    'placeholder' => 'Material title',
                    'class' => 'uk-form-width-medium',
                ),
                'translation_domain' => 'material',
            ))
            ->add('description', 'textarea', array(
                'label' => 'Description',
                'attr' => array(
                    'placeholder' => 'Description',
                    'class' => 'uk-form-width-large',
                ),
                'translation_domain' => 'material',
            ))
            ->add('biblio_select', 'choice', array(
                'choices'  => array(
                    'thesis' => 'Thesis',
                    'term' => 'Term paper'
                ),
                'required' => false,
            ))
            ->add('biblio', 'textarea', array(
                'label' => 'Biblio',
                'attr' => array(
                    'placeholder' => 'Biblio',
                    'class' => 'uk-form-width-large',
                ),
                'translation_domain' => 'material',
            ))
            ->add('save', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'Save',
                'translation_domain' => 'form',
            ))
        ;
        
        
        $formModifier = function (FormInterface $form, Materials $material = null) {
            $form->add('biblio', 'textarea', array(
                'label' => 'Biblio',
                'attr' => array(
                    'placeholder' => 'Biblio',
                    'class' => 'uk-form-width-large',
                ),
                'translation_domain' => 'material',
            ));
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event->getForm(), new Materials());
            }
        );

        $builder->get('biblio_select')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $material = $event->getForm()->getData();

                // since we've added the listener to the child, we'll have to pass on
                // the parent to the callback functions!
                $formModifier($event->getForm()->getParent(), $material);
            }
        );
        
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array())
        ;
    }

    public function getName()
    {
        return 'material';
    }
}