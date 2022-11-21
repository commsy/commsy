<?php

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
    /**
     * @Route("/api/metrics", name="metrics")
     */
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
