<?php
    namespace Application\Kernel\Controller;

    use Symfony\Component\HttpKernel\Controller\ControllerResolver as SymfonyControllerResolver;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Symfony\Component\DependencyInjection\ContainerAwareInterface;

    use Psr\Log\LoggerInterface;

    class ControllerResolver extends SymfonyControllerResolver
    {
        protected $container;

        public function __construct(ContainerInterface $container, LoggerInterface $logger = null)
        {
            $this->container = $container;

            parent::__construct($logger);
        }

        /**
         * Returns a callable for the given controller.
         *
         * @param string $controller A Controller string
         *
         * @return mixed A PHP callable
         *
         * @throws \InvalidArgumentException
         */
        protected function createController($controller)
        {
            if (false === strpos($controller, '::')) {
                throw new \InvalidArgumentException(sprintf('Unable to find controller "%s".', $controller));
            }

            list($class, $method) = explode('::', $controller, 2);

            if (!class_exists($class)) {
                throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
            }

            $controller = new $class();
            if ($controller instanceof ContainerAwareInterface) {
                $controller->setContainer($this->container);
            }

            return array($controller, $method);
        }
    }