<?php
    namespace Application\Templating;

    use Symfony\Bridge\Twig\TwigEngine;
    use Symfony\Component\Templating\TemplateNameParserInterface;
    use Symfony\Component\Templating\EngineInterface;

    class TwigThemeEngine extends TwigEngine implements EngineInterface
    {
        public function __construct(\Twig_Environment $environment, TemplateNameParserInterface $parser)
        {
            parent::_construct($enviornment, $parser);
        }
    }