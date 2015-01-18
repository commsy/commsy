<?php
	use Symfony\Component\HttpKernel\HttpKernelInterface;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\HttpKernel\HttpKernel;
    use Symfony\Component\EventDispatcher\EventDispatcher;
	use Application\Kernel\Controller\ControllerResolver;
	use Symfony\Component\HttpKernel\EventListener\ResponseListener;
	use Symfony\Component\Config\FileLocator;
	use Symfony\Component\Routing\Matcher\UrlMatcher;
	use Symfony\Component\Routing\RequestContext;
	use Symfony\Component\Routing\Loader\YamlFileLoader;
	use Symfony\Component\HttpKernel\EventListener\RouterListener;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
    use Symfony\Component\DependencyInjection\Loader\YamlFileLoader as DIYamlFileLoader;
    use Symfony\Component\Filesystem\Filesystem;
    use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
    use Symfony\Component\Config\ConfigCache;
    use Symfony\Component\Debug\Debug;

    use Application\Kernel\EventListener\LegacyListener;

	class MainKernel implements HttpKernelInterface
	{
		private $environment;
		private $httpKernel;
        private $container;

        private $rootDir;
        private $debug;

		public function __construct($environment, $debug)
		{
			$this->environment = $environment;
            $this->debug = $debug;

            if ($this->debug) {
                Debug::enable();
            }


			$legacyKernel = new LegacyKernel();
			$responseListener = new ResponseListener('UTF-8');

			$locator = new FileLocator(array(__DIR__ . '/config'));
			$loader = new YamlFileLoader($locator);

			$context = new RequestContext();
			$matcher = new UrlMatcher($loader->load('routing.yml'), $context);

			$dispatcher = new EventDispatcher();
            $dispatcher = $this->getContainer()->get('event_dispatcher');
			$dispatcher->addSubscriber($responseListener);
			$dispatcher->addSubscriber(new RouterListener($matcher, $context));

			$resolver = new ControllerResolver($this->getContainer());
			$this->httpKernel = new HttpKernel($dispatcher, $resolver);
		}

		public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
		{
            $dic = $this->getContainer();

            if (self::MASTER_REQUEST === $type) {
                \Locale::setDefault($dic->getParameter('locale'));
            }

			// Just forward the request to the HTTP kernel. It will trigger
			// all events and registered listeners will respond to them.
			return $this->httpKernel->handle($request, $type, $catch);
		}

        public function getContainer()
        {
            if ($this->container) {
                return $this->container;
            }

            $class = $this->getContainerClass();
            $cache = new ConfigCache($this->getCacheDir() . '/' . $class . '.php', $this->debug);
            if (!$cache->isFresh()) {
                $this->buildContainer();
            }

            // Create a unique instance of the container
            require_once $this->getContainerFilePath();
            $class = $this->getContainerClass();
            $this->container = new $class();

            return $this->container;
        }

        protected function getContainerClass()
        {
            return sprintf('Main%sContainer', ucfirst($this->environment));
        }

        protected function getContainerFilePath()
        {
            return $this->getCacheDir() . '/' . $this->getContainerClass() . '.php';
        }

        protected function buildContainer()
        {
            $dic = new ContainerBuilder();
            $dic->setParameter('kernel.environment', $this->environment);
            $dic->setParameter('kernel.cache_dir', $this->getCacheDir());
            $dic->setParameter('kernel.root_dir', $this->getRootDir());
            $dic->set('kernel', $this);

            $locator = new FileLocator(array(
                $this->getRootDir() . '/config/services',
                $this->getRootDir() . '/config',
            ));
            $loader = new XmlFileLoader($dic, $locator);
            $loader->load('exception.xml');
            $loader->load('legacy.xml');
            $loader->load('dispatcher.xml');
            $loader->load('templating.xml');

            // Load the application's configuration to override the
            // default service definitions configuration per environment
            $loader = new DIYamlFileLoader($dic, $locator);
            $loader->load('config_' . $this->environment . '.yml');

            $dic->compile();

            $target = $this->getContainerFilePath();
            $folder = pathinfo($target, PATHINFO_DIRNAME);
            if (!file_exists($folder)) {
                //$filesystem = $dic->get('filesystem');
                $filesystem = new Filesystem();
                $filesystem->mkdir($folder);
            }

            $dumper = new PhpDumper($dic);
            file_put_contents($target, $dumper->dump(array(
                'class' => $this->getContainerClass(),
            )));
        }

        public function getCacheDir()
        {
            return $this->getRootDir() . '/cache/' . $this->environment;
        }

        public function getRootDir()
        {
            if ($this->rootDir === null) {
                $r = new \ReflectionObject($this);
                $this->rootDir = str_replace('\\', '/', dirname($r->getFileName()));
            }

            return $this->rootDir;
        }
	}