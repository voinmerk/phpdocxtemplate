<?php

namespace PhpDocxTemplate\Twig\Lambda;

use Twig\Compiler;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

/**
 * Class LambdaWithArguments
 *
 * @package PhpDocxTemplate\Twig\Lambda
 */
class LambdaWithArguments extends Lambda
{
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * LambdaWithArguments constructor.
     *
     * @param Node $left
     * @param Node $right
     * @param $lineno
     */
    public function __construct(Node $left, Node $right, $lineno)
    {
        parent::__construct(['left' => $left, 'right' => $right], [], $lineno);

        if ($left instanceof NameExpression) {
            $this->arguments = [ $left->getAttribute('name') ];
        } elseif ($left instanceof Arguments) {
            $this->arguments = $left->getArguments();
        } else {
            throw new \InvalidArgumentException('Invalid argument\'s list for lambda.');
        }
        
        if (count($this->arguments) !== count(array_flip($this->arguments))) {
            throw new \InvalidArgumentException('Each lambda argument must have unique name.');
        }

    }

    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler)
    {
        $this->compileWithArguments($compiler, 'right', $this->arguments);
    }
}
