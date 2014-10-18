<?php
	namespace Application\Kernel\EventListener;

	use Symfony\Component\EventDispatcher\EventSubscriberInterface;
	use Symfony\Component\HttpKernel\KernelEvents;
	use Symfony\Component\HttpKernel\Event\GetResponseEvent;
	use Symfony\Component\HttpKernel\HttpKernelInterface;

	class LegacyListener implements EventSubscriberInterface
	{
		private $legacyKernel;

		public function __construct(/*LegacyKernel*/ $legacyKernel)
		{
			$this->legacyKernel = $legacyKernel;
		}

		public static function getSubscribedEvents()
		{
			return array(
				KernelEvents::REQUEST => array('onKernelRequest', 512),
			);
		}

		public function onKernelRequest(GetResponseEvent $event)
		{
			// the legacy kernel only deals with master requests
			if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
				return;
			}

			// Let the wrapped legacy kernel handle the legacy request.
			// Setting a response in the event will directly jump to the response event.
			$request = $event->getRequest();
			if ($request->query->has('cid')) {
				$response = $this->legacyKernel->handle($request);
				$event->setResponse($response);
			}
		}
	}