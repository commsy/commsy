<?php
	use Symfony\Component\HttpKernel\HttpKernelInterface;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;

	class LegacyKernel implements HttpKernelInterface
	{
		public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
		{
			ob_start();

			//require_once('commsy.php');

			return new Response(ob_get_clean());
		}
	}