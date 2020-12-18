<?php

namespace PhpDocxTemplate\Twig\Lambda;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;

/**
 * Class Lambda
 *
 * @package PhpDocxTemplate\Twig\Lambda
 */
abstract class Lambda extends AbstractExpression
{
    /**
     * @param Compiler $compiler
     * @param $expressionNode
     * @param array $arguments
     */
    protected function compileWithArguments(Compiler $compiler, $expressionNode, array $arguments): void
    {
        $compiler->raw("\n");
        $compiler->indent();
        $compiler->write('');
        $compiler->raw("function() use(&\$context) {\n");
        $compiler->indent();

        // copy of arguments and __ from context
        foreach ($arguments as $arg) {
            $compiler->write('');
            $compiler->raw("if (isset(\$context['$arg'])) \$outer$arg = \$context['$arg'];\n");
        }
        $compiler->write('');
        $compiler->raw("if (isset(\$context['__'])) \$outer__ = \$context['__'];\n");

        // adding closure's arguments to context
        $compiler->write('');
        $compiler->raw("\$context['__'] = func_get_args();\n");
        foreach ($arguments as $i => $arg) {
            $compiler->write('');
            $compiler->raw("if (func_num_args()>$i) \$context['$arg'] = func_get_arg($i);\n");
            $compiler->write('');
            $compiler->raw("else unset(\$context['$arg']);\n");
        }

        // getting call result
        $compiler->write('');
        $compiler->raw("\$result = ");
        $compiler->subcompile($this->getNode($expressionNode));
        $compiler->raw(";\n");

        // recreating original context
        foreach ($arguments as $arg) {
            $compiler->write('');
            $compiler->raw("if (isset(\$outer$arg)) \$context['$arg'] = \$outer$arg ;\n");
            $compiler->write('');
            $compiler->raw("else unset(\$context['$arg']);\n");
        }
        $compiler->write('');
        $compiler->raw("if (isset(\$outer__)) \$context['__'] = \$outer__ ;\n");
        $compiler->write('');
        $compiler->raw("else unset(\$context['__']);\n");

        // return statement
        $compiler->write('');
        $compiler->raw("return \$result;\n");
        $compiler->outdent();
        $compiler->write('');

        $compiler->raw("}\n");
        $compiler->outdent();
        $compiler->write('');
    }
}
