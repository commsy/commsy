<?php
namespace App\Form\Type;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use App\Form\Type\Custom\DateTimeSelectType;
use App\Form\Type\Custom\MandatoryCategoryMappingType;
use App\Form\Type\Custom\MandatoryHashtagMappingType;

use App\Form\Type\Event\AddBibliographicFieldListener;
use App\Form\Type\Event\AddEtherpadFormListener;
use cs_environment;

class MaterialType extends AbstractType
{
    /**
     * @var cs_environment
     */
    private cs_environment $environment;

    /**
     * @var RoomService
     */
    private RoomService $roomService;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    private $etherpadFormListener;

    public function __construct(
        AddEtherpadFormListener $etherpadListener,
        LegacyEnvironment $legacyEnvironment,
        RoomService $roomService,
        ItemService $itemService
    )
    {
        $this->environment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->itemService = $itemService;
        $this->etherpadFormListener = $etherpadListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'title',
                'attr' => array(
                    'placeholder' => $options['placeholderText'],
                    'class' => 'uk-form-width-medium cs-form-title',
                ),
                'translation_domain' => 'material',
            ))
            ->add('permission', CheckboxType::class, array(
                'label' => 'permission',
                'required' => false,
                'label_attr' => array('class' => 'uk-form-label'),
            ))
            ->add('hidden', CheckboxType::class, array(
                'label' => 'hidden',
                'required' => false,
            ))
            ->add('hiddendate', DateTimeSelectType::class, array(
                'label' => 'hidden until',
            ))
            ->addEventSubscriber($this->etherpadFormListener)
            ->add('biblio_select', ChoiceType::class, array(
                'choices'  => array(
                    'plain' => 'BiblioPlainType',
                    'book' => 'BiblioBookType',
                    'collection' => 'BiblioCollectionType',
                    'article' => 'BiblioArticleType',
                    'journal' => 'BiblioJournalType',
                    'chapter' => 'BiblioChapterType',
                    'newspaper' => 'BiblioNewspaperType',
                    'thesis' => 'BiblioThesisType',
                    'manuscript' => 'BiblioManuscriptType',
                    'website' => 'BiblioWebsiteType',
                    'document management' => 'BiblioDocManagementType',
                    'picture' => 'BiblioPictureType'
                ),
                'label' => 'bib reference',
                'choice_translation_domain' => true,
                'required' => false,
            ))
            ->addEventSubscriber(new AddBibliographicFieldListener())
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $material = $event->getData();
                $form = $event->getForm();
                $formOptions = $form->getConfig()->getOptions();

                if ($material['external_viewer_enabled']) {
                    $form->add('external_viewer', TextType::class, [
                        'required' => false,
                    ]);
                }

                if ($material['draft']) {
                    if ($material['hashtagsMandatory'] && $formOptions['hashtagMappingOptions']) {
                        $form->add('hashtag_mapping', MandatoryHashtagMappingType::class, $formOptions['hashtagMappingOptions']);
                    }
                    if ($material['categoriesMandatory'] && $formOptions['categoryMappingOptions']) {
                        $form->add('category_mapping', MandatoryCategoryMappingType::class, $formOptions['categoryMappingOptions']);
                    }
                }
            })
            ->add('license_id', ChoiceType::class, array(
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'choices' => $options['licenses'],
                'translation_domain' => 'material',
            ))
            ->add('itemsLinked', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'required' => false,
                'entry_type' => CheckboxType::class,
                'entry_options' => [
//                    'choices' => $options['itemsLinked'],
                ],
            ])
            ->add('itemsLatest', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['itemsLatest'],
                'required' => false,
                'expanded' => true,
                'multiple' => true
            ))
            ->add('categories', TreeChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['categories'],
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'constraints' => $options['categoryConstraints'],
            ))
            ->add('hashtags', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['hashtags'],
                'label' => 'hashtags',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'constraints' => $options['hashtagConstraints'],
            ))
            ->add('newHashtag', TextType::class, array(
                'attr' => array(
                    'placeholder' => $options['placeholderText'],
                ),
                'label' => 'newHashtag',
                'required' => false
            ))
            ->add('newHashtagAdd', ButtonType::class, array(
                'attr' => array(
                    'id' => 'addNewHashtag',
                    'data-cs-add-hashtag' => $options['hashtagEditUrl'],
                ),
                'label' => 'addNewHashtag',
                'translation_domain' => 'form',
            ))
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
            ))
        ;
        
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([
                'placeholderText',
                'hashtagMappingOptions',
                'categoryMappingOptions',
                'licenses',
                'categories',
                'categoryConstraints',
                'filterRubric',
                'filterPublic',
                'hashtagConstraints',
                'hashtagEditUrl',
                'hashtags',
                'items',
                'itemsLatest',
                'itemsLinked',
            ])
            ->setDefaults(array('translation_domain' => 'form'))
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     * 
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'material';
    }

    private function getTempData ($filterData) {
        // get all items that are linked, temporary linked (i.e. already selected in the form) or can be linked
        $optionsData = array();

        if (empty($filterData['filterRubric']) || $filterData['filterRubric'] == 'all') {
            $rubricInformation = $this->roomService->getRubricInformation($this->environment->getCurrentContextId());
        } else {
            $rubricInformation = array($filterData['filterRubric']);
        }

        $itemManager = $this->environment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($this->environment->getCurrentContextId());
        $itemManager->setTypeArrayLimit($rubricInformation);

        if (isset($filterData['feedAmount'])) {
            $itemManager->setIntervalLimit($filterData['feedAmount']);
        }
        $itemManager->select();
        $itemList = $itemManager->get();

        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $this->itemService->getTypedItem($tempItem->getItemId());
            if ($tempTypedItem) {
                if ($tempTypedItem->getItemType() != 'user') {
                    $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                } else {
                    $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getFullname();
                }
            }
            $tempItem = $itemList->getNext();
        }

        if (empty($optionsData['items'])) {
            $optionsData['items'] = array();
        }

        $tempData = array();
        if (isset($filterData['items'])) {
            $tempData = $filterData['items'];
        }

        if (isset($filterData['itemsLinked'])) {
            $tempData = array_merge($tempData, $filterData['itemsLinked']);
        }

        $itemManager->reset();
        $itemLinkedList = $itemManager->getItemList($tempData);

        $tempLinkedItem = $itemLinkedList->getFirst();
        while ($tempLinkedItem) {
            $tempTypedLinkedItem = $this->itemService->getTypedItem($tempLinkedItem->getItemId());
            if ($tempTypedLinkedItem->getItemType() != 'user') {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getTitle();
            } else {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getFullname();
            }
            $tempLinkedItem = $itemLinkedList->getNext();
        }

        $itemManager->reset();

        //$optionsData['itemsLinked'] = $filterData['items'];
        if (empty($optionsData['itemsLinked'])) {
            $optionsData['itemsLinked'] = array();
        }

        if (isset($filterData['remove'])) {
            if(isset($optionsData['items'][$filterData['remove']])) {
                unset($optionsData['items'][$filterData['remove']]);
            }
            if(isset($optionsData['itemsLinked'][$filterData['remove']])) {
                unset($optionsData['itemsLinked'][$filterData['remove']]);
            }
        }
        return $optionsData;
    }
}