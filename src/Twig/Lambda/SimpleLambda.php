<?php

namespace PhpDocxTemplate\Twig\Lambda;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class SimpleLambda
 *
 * @package PhpDocxTemplate\Twig\Lambda
 */
class SimpleLambda extends Lambda
{
    /**
     * SimpleLambda constructor.
     *
     * @param Node $node
     * @param $lineno
     */
    public function __construct(Node $node, $lineno)
    {
        parent::__construct(['node' => $node], [], $lineno);
    }

    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler)
    {
        $this->compileWithArguments($compiler, 'node', ['_']);
    }
}
