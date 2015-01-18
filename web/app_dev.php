<?php
	/**
	 * CommSy Front Controller
	 */
	
	// definitions for several paths
	define('LEGACY_DIR', realpath(__DIR__ . '/../legacy'));
	define('CLASSES_DIR', LEGACY_DIR . '/classes');
	define('EXTERNAL_PAGES_DIR', LEGACY_DIR . '/external_pages');
	define('FUNCTIONS_DIR', LEGACY_DIR . '/functions');
	define('INCLUDE_DIR', LEGACY_DIR . '/include');
	define('LIBS_DIR', LEGACY_DIR . '/libs');
	define('PAGES_DIR', LEGACY_DIR . '/pages');
	define('PLUGINS_DIR', LEGACY_DIR . '/plugins');
	define('SCRIPTS_DIR', LEGACY_DIR . '/scripts');
	define('CONFIG_DIR', LEGACY_DIR . '/etc');

	// load required dependencies
	
	// update include path
	set_include_path(LEGACY_DIR . ":" . get_include_path());

	// require autoloading and kernel files
	require_once __DIR__ . '/../vendor/autoload.php';
	require_once __DIR__ . '/../main/MainKernel.php';
	require_once __DIR__ . '/../main/LegacyKernel.php';

	use Symfony\Component\HttpFoundation\Request;

	$request = Request::createFromGlobals();

	$kernel = new MainKernel('dev', true);
	$response = $kernel->handle($request);
	$response->prepare($request);
	$response->send();
	$kernel->terminate($request, $response);