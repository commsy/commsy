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
use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\ItemService;
use CommsyBundle\Entity\Materials;

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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filterRubric', 'choice', array(
                'placeholder' => false,
                'choices' => $options['filterRubric'],
                'label' => 'filterRubric',
                'choice_translation_domain' => true,
                'translation_domain' => 'form',
                'required' => false
            ))
            ->add('filterPublic', 'choice', array(
                'placeholder' => false,
                'choices' => $options['filterPublic'],
                'label' => 'filterPublic',
                'translation_domain' => 'form',
                'required' => false
            ))
            ->add('categories', 'treechoice', array(
                'placeholder' => false,
                'choices' => $options['categories'],
                'label' => 'categories',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'choices_as_values' => true
            ))
            ->add('hashtags', 'choice', array(
                'placeholder' => false,
                'choices' => $options['hashtags'],
                'label' => 'hashtags',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => true
            ))
            ->add('newHashtag', 'text', array(
                'label' => 'newHashtag',
                'translation_domain' => 'item',
                'required' => false
            ))
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
        
        $formModifier = function (FormInterface $form, array $options) {
            $form->add('items', 'choice', array(
                'placeholder' => false,
                'choices' => $options['items'],
                'label' => 'items',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => true
            ));
            
            $form->add('itemsLinked', 'choice', array(
                'placeholder' => false,
                'choices' => $options['itemsLinked'],
                'label' => 'itemsLinked',
                'translation_domain' => 'item',
                'required' => false,
                'expanded' => true,
                'multiple' => true
            ));
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier, $options) {
                $formModifier($event->getForm(), $options);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $tempData = $this->getTempData($data);
                
                $formModifier($event->getForm(), $tempData);

                $eventData = array();
                if (isset($tempData['itemsLinked'])) {
                    if (!empty(array_keys($tempData['itemsLinked']))) {
                        $eventData['items'] = array_keys($tempData['itemsLinked']);
                        $eventData['itemsLinked'] = array_keys($tempData['itemsLinked']);
                    } else {
                        $eventData['items'] = $tempData['itemsLinked'];
                        $eventData['itemsLinked'] = $tempData['itemsLinked'];
                    }
                }
                
                $event->setData($eventData);
                if (isset($eventData['items'])) {
                    $event->getForm()->get('items')->setData($eventData['items']);
                }
                if (isset($eventData['itemsLinked'])) {
                    $event->getForm()->get('itemsLinked')->setData($eventData['itemsLinked']);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array('filterRubric', 'filterPublic', 'items', 'itemsLinked', 'categories', 'hashtags'))
        ;
    }

    public function getName()
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
        //$itemManager->setNoIntervalLimit();
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