<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\ItemService;

class ItemLinksType extends AbstractType
{
    private $environment;
    private $roomService;
    private $itemService;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, ItemService $itemService)
    {
        $this->environment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->itemService = $itemService;
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     * 
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
                'translation_domain' => 'form',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
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
            ->setDefaults([
                'translation_domain' => 'item',
            ])
            ->setRequired([
                'filterRubric',
                'filterPublic',
                'items',
                'itemsLinked',
                'itemsLatest',
                'categories',
                'categoryConstraints',
                'hashtags',
                'hashtagConstraints',
                'hashtagEditUrl',
                'placeholderText',
            ])
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
        return 'itemLinks';
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
