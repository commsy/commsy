<?php


namespace App\Helper;


use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionHelper
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function getSession(): SessionInterface
    {
        return $this->session;
    }
}