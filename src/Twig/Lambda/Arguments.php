<?php

namespace PhpDocxTemplate\Twig\Lambda;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Exception;

/**
 * Class Arguments
 *
 * @package PhpDocxTemplate\Twig\Lambda
 */
class Arguments extends AbstractExpression
{
    /**
     * @var array
     */
    private $arguments;

    /**
     * Arguments constructor.
     *
     * @param Node $left
     * @param Node $right
     * @param $lineno
     */
    public function __construct(Node $left, Node $right, $lineno)
    {
        $arguments = [];
        foreach ([$left, $right] as $node) {
            if ($node instanceof Arguments) {
                $arguments[] = $node->getArguments();
            } elseif ($node instanceof NameExpression) {
                $arguments[] = [$node->getAttribute('name')];
            } else {
                throw new \InvalidArgumentException('Invalid argument.');
            }
        }
        
        $this->arguments = array_merge($arguments[0], $arguments[1]);
        
        parent::__construct(array('left' => $left, 'right' => $right), array(), $lineno);
    }

    /**
     * @param Compiler $compiler
     *
     * @throws Exception
     */
    public function compile(Compiler $compiler)
    {
        throw new Exception('Semicolon-separated list of arguments can be only used in lambda expression.');
    }

    /**
     * @return array
     */
    public function getArguments() 
    {
        return $this->arguments;
    }
}
