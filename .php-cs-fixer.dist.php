<?php

$fileHeaderComment = <<<'EOF'
This file is part of CommSy.

(c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze

For the full copyright and license information, please view the LICENSE.md
file that was distributed with this source code.
EOF;

$config = new PhpCsFixer\Config();
return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'header_comment' => ['header' => $fileHeaderComment],
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/legacy')
            ->in(__DIR__ . '/src')
    )
;
