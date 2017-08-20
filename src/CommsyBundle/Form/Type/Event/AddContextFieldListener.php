<?php
namespace CommsyBundle\Form\Type\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CommsyBundle\Form\Type\Bibliographic\BiblioArticleType;
use CommsyBundle\Form\Type\Bibliographic\BiblioBookType;
use CommsyBundle\Form\Type\Bibliographic\BiblioChapterType;
use CommsyBundle\Form\Type\Bibliographic\BiblioCollectionType;
use CommsyBundle\Form\Type\Bibliographic\BiblioDocManagementType;
use CommsyBundle\Form\Type\Bibliographic\BiblioJournalType;
use CommsyBundle\Form\Type\Bibliographic\BiblioManuscriptType;
use CommsyBundle\Form\Type\Bibliographic\BiblioNewspaperType;
use CommsyBundle\Form\Type\Bibliographic\BiblioPictureType;
use CommsyBundle\Form\Type\Bibliographic\BiblioPlainType;
use CommsyBundle\Form\Type\Bibliographic\BiblioThesisType;
use CommsyBundle\Form\Type\Bibliographic\BiblioWebsiteType;
use CommsyBundle\Form\Type\Bibliographic\BiblioNoneType;


/**
* 
*/
class AddContextFieldListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::POST_SUBMIT => 'onPostSubmit',
            FormEvents::PRE_SUBMIT => 'onPreSubmit'
        );
    }

    public function onPostSubmit(FormEvent $event)
    {
        $event->stopPropagation();
    }

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $formOptions = $form->getConfig()->getOptions();

        if (isset($data['type_select']) && $data['type_select'] == 'project') {
            $form->add('type_sub', 'CommsyBundle\Form\Type\Context\ProjectType', $formOptions);
        } else if (isset($data['type_select']) && $data['type_select'] == 'community') {
            $form->add('type_sub', 'CommsyBundle\Form\Type\Context\CommunityType', $formOptions);
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $formOptions = $form->getConfig()->getOptions();

        if (isset($data['type_select']) && $data['type_select'] == 'project') {
            $form->add('type_sub', 'CommsyBundle\Form\Type\Context\ProjectType', $formOptions);
        } else if (isset($data['type_select']) && $data['type_select'] == 'community') {
            $form->add('type_sub', 'CommsyBundle\Form\Type\Context\CommunityType', $formOptions);
        }
    }
}
