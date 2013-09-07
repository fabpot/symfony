<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * Allows to compile and evaluate expressions written in your own DSL.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExpressionLanguage
{
    private $lexer;
    private $parser;
    private $compiler;
    private $cache;

    protected $functions;

    public function __construct()
    {
        $this->functions = array();
        $this->registerFunctions();
    }

    /**
     * Compiles an expression source code.
     *
     * @param string $expression The expression to compile
     * @param array  $names      An array of valid names
     *
     * @return string The compiled PHP source code
     */
    public function compile($expression, $names = array())
    {
        return $this->getCompiler()->compile($this->parse($expression, $names))->getSource();
    }

    /**
     * Evaluate an expression.
     *
     * @param Expression|string $expression The expression to compile
     * @param array             $values     An array of values
     *
     * @return string The result of the evaluation of the expression
     */
    public function evaluate($expression, $values = array())
    {
        if ($expression instanceof ParsedExpression) {
            $expression = $expression->getNodes();
        } else {
            $expression = $this->parse($expression, array_keys($values));
        }

        return $expression->evaluate($this->functions, $values);
    }

    /**
     * Parses an expression.
     *
     * @param Expression|string $expression The expression to parse
     *
     * @return Node A Node tree
     */
    public function parse($expression, $names)
    {
        $key = $expression.'//'.implode('-', $names);

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->getParser()->parse($this->getLexer()->tokenize((string) $expression), $names);
        }

        return $this->cache[$key];
    }

    public function addFunction($name, $compiler, $evaluator)
    {
        $this->functions[$name] = array('compiler' => $compiler, 'evaluator' => $evaluator);
    }

    protected function registerFunctions()
    {
        $this->addFunction('constant', function ($constant) {
            return sprintf('constant(%s)', $constant);
        }, function (array $values, $constant) {
            return constant($constant);
        });
    }

    private function getLexer()
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer();
        }

        return $this->lexer;
    }

    private function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Parser($this->functions);
        }

        return $this->parser;
    }

    private function getCompiler()
    {
        if (null === $this->compiler) {
            $this->compiler = new Compiler($this->functions);
        }

        return $this->compiler->reset();
    }
}
