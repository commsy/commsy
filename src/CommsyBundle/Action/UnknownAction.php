<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.04.18
 * Time: 19:26
 */

namespace CommsyBundle\Action;


use Symfony\Component\Translation\TranslatorInterface;

class UnknownAction implements ActionInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function execute($items)
    {
        return [
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> ' . $this->translator->trans('action error'),
        ];
    }
}