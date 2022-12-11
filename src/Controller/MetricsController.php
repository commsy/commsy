<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Metrics\PrometheusCollector;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController extends AbstractController
{
    #[Route(path: '/api/metrics', name: 'metrics')]
    public function index(PrometheusCollector $collector): Response
    {
        $collector->updateMetrics();

        $registry = new CollectorRegistry(new APC($this->getParameter('commsy.metrics.cache_namespace')));
        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        $response = new Response($result);
        $response->headers->set('Content-Type', RenderTextFormat::MIME_TYPE);

        return $response;
    }
}
