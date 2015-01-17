<?php
    namespace Application\Controller;

    use Symfony\Component\HttpFoundation\Response;

    class DefaultController extends Controller
    {
        public function defaultAction()
        {

            var_dump($this->get('templating'));
        /*
            if ('dev' === $this->container->getParamter('kernel.environment')) {
                return $this->render->('error/exception', array('exception' => $e));
            }

            $code = $e->getStatusCode();

            $view = 'error/error';
            if (in_array($code, array(401, 403, 404, 405))) {
                $view .= $code;
            }

            return $this->render->($view, array('exception' => $e));*/

            return new Response("test");
        }
    }