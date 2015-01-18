<?php
    namespace Application\Controller;

    use Symfony\Component\Debug\Exception\FlattenException;

    class ErrorController extends Controller
    {
        public function exceptionAction(FlattenException $e)
        {die("exception");/*
            if ('dev' === $this->container->getParamter('kernel.environment')) {
                return $this->render->('error/exception', array('exception' => $e));
            }

            $code = $e->getStatusCode();

            $view = 'error/error';
            if (in_array($code, array(401, 403, 404, 405))) {
                $view .= $code;
            }

            return $this->render->($view, array('exception' => $e));*/
        }
    }