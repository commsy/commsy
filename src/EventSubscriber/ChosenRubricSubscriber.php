<?php

namespace App\EventSubscriber;

use App\Model\SearchData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChosenRubricSubscriber implements EventSubscriberInterface
{
    private TranslatorInterface $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        );
    }

    /**
     * Injects rubric-specific fields into a form if a certain rubric gets chosen.
     * This callback method will get called if the user selects a value from
     * the `selectedRubric` dropdown (e.g. in the SearchFilterType form).
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var SearchData $searchData * */
        $searchData = $event->getData();
        $form = $event->getForm();

        if ($searchData->getSelectedRubric() === CS_TODO_TYPE) {
            $form->add('selectedTodoStatus', Types\ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function () use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');
                    return array_merge([$translatedTitleAny => 0], $this->buildTodoStatusChoices($searchData->getTodoStatuses()));
                }),
                'label' => 'todo status',
                'translation_domain' => 'todo',
                'required' => false,
                'placeholder' => false,
            ]);
        }
    }

    /**
     * Builds the array of choices for the todo status filter field.
     *
     * @param array|null $statuses associative array of todo statuses (key: status int, value: count)
     * @return array array of integer-type status codes keyed by their translated todo status name and result count
     */
    private function buildTodoStatusChoices(?array $statuses): array
    {
        if (!isset($statuses) || empty($statuses)) {
            return [];
        }

        $choices = [];
        foreach ($statuses as $code => $count) {
            switch ($code) {
                case 1:
                    // pending
                    $translatedTitle = ucfirst($this->translator->trans('pending', [], 'todo'));
                    break;
                case 2:
                    // in progress
                    $translatedTitle = ucfirst($this->translator->trans('in progress', [], 'todo'));
                    break;
                case 3:
                    // done
                    $translatedTitle = ucfirst($this->translator->trans('done', [], 'todo'));
                    break;
                default:
                    $translatedTitle = $code;
            }

            $status = $translatedTitle . " (" . $count . ")";
            $choices[$status] = $code;
        }

        return $choices;
    }
}
