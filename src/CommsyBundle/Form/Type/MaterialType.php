<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommsyBundle\Entity\Materials;
use CommsyBundle\Form\Type\Event\AddBibliographicFieldListener;

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
                'label' => 'title',
                'attr' => array(
                    'placeholder' => 'title',
                    'class' => 'uk-form-width-medium cs-form-title',
                ),
                'translation_domain' => 'material',
            ))
            ->add('editor_switch', 'checkbox', array(
                'label' => 'editor_switch',
                'required' => false,
                'translation_domain' => 'form',
            ))
            ->add('permission', 'checkbox', array(
                'label' => 'permission',
                'required' => false,
                'translation_domain' => 'form'
            ))
            ->add('biblio_select', 'choice', array(
                'choices'  => array(
                    'BiblioPlainType' => 'plain',
                    'BiblioBookType' => 'book',
                    'BiblioCollectionType' => 'collection',
                    'BiblioArticleType' => 'article',
                    'BiblioJournalType' => 'journal',
                    'BiblioChapterType' => 'chapter',
                    'BiblioNewspaperType' => 'newspaper',
                    'BiblioThesisType' => 'thesis',
                    'BiblioManuscriptType' => 'manuscript',
                    'BiblioWebsiteType' => 'website',
                    'BiblioDocManagementType' => 'document management',
                    'BiblioPictureType' => 'picture',

                ),
                'label' => 'bib reference',
                'choice_translation_domain' => true,
                'required' => false,
                'translation_domain' => 'form'
            ))
            ->addEventSubscriber(new AddBibliographicFieldListener())
            // ->get('biblio_select')->addEventSubscriber() // post submit
            ->add('save', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
                'translation_domain' => 'form',
            ))
            ->add('cancel', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
            ))
        ;
        
        
//         $formModifier = function (FormInterface $form, Materials $material = null) {
//             $form->add('biblio', 'textarea', array(
//                 'label' => 'Biblio',
//                 'attr' => array(
//                     'placeholder' => 'Biblio',
//                     'class' => 'uk-form-width-large',
//                 ),
//                 'translation_domain' => 'material',
//                 'required' => false,
//             ));
//         };

//         $builder->addEventListener(
//             FormEvents::PRE_SET_DATA,
//             function (FormEvent $event) use ($formModifier) {
//                 $formModifier($event->getForm(), new Materials());
//             }
//         );

//         $builder->get('biblio_select')->addEventListener(
//             FormEvents::POST_SUBMIT,
//             function (FormEvent $event) use ($formModifier) {
//                 // It's important here to fetch $event->getForm()->getData(), as
//                 // $event->getData() will get you the client data (that is, the ID)
//                 $material = $event->getForm()->getData();
// var_dump($material);
//                 // since we've added the listener to the child, we'll have to pass on
//                 // the parent to the callback functions!
//                 $formModifier($event->getForm()->getParent(), $material);
//             }
//         );
        
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