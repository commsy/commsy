<?php
    namespace Application\Controller;

    use Symfony\Component\DependencyInjection\ContainerAware;

    class Controller extends ContainerAware
    {
        public function get($id)
        {
            return $this->container->get($id);
        }
    }