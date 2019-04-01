<?php
namespace App\Form\Type\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Form\Type\Bibliographic\BiblioArticleType;
use App\Form\Type\Bibliographic\BiblioBookType;
use App\Form\Type\Bibliographic\BiblioChapterType;
use App\Form\Type\Bibliographic\BiblioCollectionType;
use App\Form\Type\Bibliographic\BiblioDocManagementType;
use App\Form\Type\Bibliographic\BiblioJournalType;
use App\Form\Type\Bibliographic\BiblioManuscriptType;
use App\Form\Type\Bibliographic\BiblioNewspaperType;
use App\Form\Type\Bibliographic\BiblioPictureType;
use App\Form\Type\Bibliographic\BiblioPlainType;
use App\Form\Type\Bibliographic\BiblioThesisType;
use App\Form\Type\Bibliographic\BiblioWebsiteType;
use App\Form\Type\Bibliographic\BiblioNoneType;


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
            $form->add('type_sub', 'App\Form\Type\Context\ProjectType', $formOptions);
        } else if (isset($data['type_select']) && $data['type_select'] == 'community') {
            $form->add('type_sub', 'App\Form\Type\Context\CommunityType', $formOptions);
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $formOptions = $form->getConfig()->getOptions();

        if (isset($data['type_select']) && $data['type_select'] == 'project') {
            $form->add('type_sub', 'App\Form\Type\Context\ProjectType', $formOptions);
        } else if (isset($data['type_select']) && $data['type_select'] == 'community') {
            $form->add('type_sub', 'App\Form\Type\Context\CommunityType', $formOptions);
        }
    }
}
