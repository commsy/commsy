<?php


namespace App\Form\EventListener;


use App\Search\SearchManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type as Types;

class AddRubricSearchListener implements EventSubscriberInterface
{
    private $searchManager;

    public function __construct(SearchManager $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();

        $form = $event->getForm();

        $contextId = $form->getConfig()->getOption('contextId');
        $this->searchManager->setContext($contextId);
        $searchResults = $this->searchManager->getResults();
        $aggregations = $searchResults->getAggregations();

        if (isset($aggregations['rubric'])) {
            if (isset($aggregations['rubric']['buckets'])) {
                $choices = [];
                foreach ($aggregations['rubric']['buckets'] as $bucket) {
                    $choices[$bucket['key']] = $bucket['key'];
                }

                $form->add('rubric', Types\ChoiceType::class, [
                    'choices' => $choices,
                ]);
            }
        }
    }

    public function onPostSubmit(FormEvent $event)
    {

    }
}