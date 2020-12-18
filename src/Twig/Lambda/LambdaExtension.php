<?php

namespace PhpDocxTemplate\Twig\Lambda;

use DPolac\Dictionary;
use Twig\Error\RuntimeError;
use Twig\ExpressionParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Class LambdaExtension
 *
 * @package PhpDocxTemplate\Twig\Lambda
 */
class LambdaExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getOperators(): array
    {
        return [
            [
                '=>' => [
                    'precedence' => 0,
                    'class' => SimpleLambda::class
                ],
            ],
            [
                '=>' => [
                    'precedence' => 0,
                    'class' => LambdaWithArguments::class,
                    'associativity' => ExpressionParser::OPERATOR_LEFT
                ],
                ';' => [
                    'precedence' => 5,
                    'class' => Arguments::class,
                    'associativity' => ExpressionParser::OPERATOR_RIGHT
                ],
            ]
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('call', '\PhpDocxTemplate\Twig\Lambda\LambdaExtension::call'),
        ];
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('every', '\PhpDocxTemplate\Twig\Lambda\LambdaExtension::every'),
            new TwigTest('any', '\PhpDocxTemplate\Twig\Lambda\LambdaExtension::any'),
        ];
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('map', LambdaExtension::class . '::map'),
            new TwigFilter('select', LambdaExtension::class . '::map'),

            new TwigFilter('filter', LambdaExtension::class . '::filter'),
            new TwigFilter('where', LambdaExtension::class . '::filter'),

            new TwigFilter('unique_by', LambdaExtension::class . '::uniqueBy'),
            new TwigFilter('group_by', LambdaExtension::class . '::groupBy'),
            new TwigFilter('sort_by', LambdaExtension::class . '::sortBy'),
            new TwigFilter('count_by', LambdaExtension::class . '::countBy'),
        ];
    }

    /**
     * @param $array
     * @param $callback
     *
     * @return array|Dictionary
     *
     * @throws RuntimeError
     */
    public static function map($array, $callback) 
    {
        if (!is_callable($callback)) {
            throw new RuntimeError(
                sprintf(
                    'Second argument of "map" must be callable, but is "%s".',
                    gettype($callback)
                )
            );
        }
        
        if (is_array($array)) {
            $array = array_map($callback, $array, array_keys($array));
        } elseif ($array instanceof \Traversable) {
            $result = new Dictionary();
            foreach ($array as $i => $item) {
                $result[$i] = $callback($item, $i);
            }
            $array = $result;
        } else {
            throw new RuntimeError(
                sprintf(
                    'First argument of "map" must be array or Traversable, but is "%s".',
                    gettype($array)
                )
            );
        }
        
        return $array;
    }

    /**
     * @param $array
     * @param $callback
     *
     * @return array|Dictionary
     *
     * @throws RuntimeError
     */
    public static function filter($array, $callback)
    {
        if (!is_callable($callback)) {
            throw new RuntimeError(
                sprintf(
                    'Second argument of "filter" must be callable, but is "%s".',
                    gettype($callback)
                )
            );
        }

        if (is_array($array)) {
            $array = array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
        } elseif ($array instanceof \Traversable) {
            $result = new Dictionary();
            foreach ($array as $i => $item) {
                if ($callback($item, $i)) {
                    $result[$i] = $item;
                }
            }
            $array = $result;
        } else {
            throw new RuntimeError(
                sprintf(
                    'First argument of "filter" must be array or Traversable, but is "%s".',
                    gettype($array)
                )
            );
        }

        return $array;
    }

    /**
     * @param $array
     * @param $callback
     *
     * @return array|Dictionary
     *
     * @throws RuntimeError
     */
    public static function uniqueBy($array, $callback)
    {
        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new RuntimeError(
                sprintf(
                    'First argument of "unique_by" must be array or Traversable, but is "%s".',
                    gettype($array)
                )
            );
        }

        if ('==' === $callback) {
            $callback = function($item1, $item2) { return $item1 == $item2; };
        } else if ('===' === $callback) {
            $callback = function($item1, $item2) { return $item1 === $item2; };
        } else if (!is_callable($callback)) {
            throw new RuntimeError(
                sprintf(
                    'Second argument of "unique_by" must be callable, "==" or "===", but is "%s".',
                    gettype($callback)
                )
            );
        }

        if ($array instanceof \Traversable) {
            if ($array instanceof \Iterator) {
                // convert Iterator to IteratorAggregate for nested foreach
                $array = Dictionary::fromArray($array);
            }
            $result = new Dictionary();
        } else {
            $result = [];
        }
        
        foreach ($array as $i => $item) {
            foreach ($array as $j => $previous) {
                if ($i === $j) {
                    // add to results if already checked every previous element
                    $result[$i] = $item;
                } elseif (isset($result[$j]) && $callback($item, $previous, $i, $j)) {
                    // skip if is identical with value which is already in results array
                    continue 2;
                }
            }
        }

        return $result;
    }

    /**
     * @param $array
     * @param $callback
     *
     * @return Dictionary
     *
     * @throws RuntimeError
     */
    public static function groupBy($array, $callback): Dictionary
    {

        if (!is_callable($callback)) {
            throw new RuntimeError(
                sprintf('Second argument of "group_by" must be callable, but is "%s".', gettype($callback))
            );
        }

        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new RuntimeError(sprintf(
                    'First argument of "group_by" must be array or Traversable, but is "%s".',
                    gettype($array)
                )
            );
        }

        $results = new Dictionary();

        foreach ($array as $i => $item) {
            $key = $callback($item, $i);

            if (!isset($results[$key])) {
                $results[$key] = [$i => $item];
            } else {
                $results[$key][$i] = $item;
            }

        }

        return $results;
    }

    /**
     * @param $array
     * @param $callback
     * @param string $direction
     *
     * @return Dictionary|array
     *
     * @throws RuntimeError
     */
    public static function sortBy($array, $callback, $direction = 'ASC')
    {
        if (!is_callable($callback)) {
            throw new RuntimeError(
                sprintf(
                    'Second argument of "sort_by" must be callable, but is "%s".',
                    gettype($callback)
                )
            );
        }

        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new RuntimeError(
                sprintf(
                    'First argument of "sort_by" must be array or Traversable, but is "%s".',
                    gettype($array)
                )
            );
        }

        if ($array instanceof \Traversable) {
            if ($array instanceof Dictionary) {
                $array = $array->getCopy();
            } else {
                $array = Dictionary::fromArray($array);
            }

            return $array->sortBy($callback, $direction);
        } else {
            $direction = (strtoupper($direction) === 'DESC') ? SORT_DESC : SORT_ASC;
            $order = self::map($array, $callback);
            array_multisort($order, $direction, SORT_REGULAR, $array);

            return $array;
        }
    }

    /**
     * @param $array
     * @param $callback
     *
     * @return Dictionary
     *
     * @throws RuntimeError
     */
    public static function countBy($array, $callback): Dictionary
    {
        if (!is_callable($callback)) {
            throw new RuntimeError(
                sprintf(
                    'Second argument of "count_by" must be callable, but is "%s".',
                    gettype($callback)
                )
            );
        }

        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new RuntimeError(
                sprintf(
                    'First argument of "count_by" must be array or Traversable, but is "%s".',
                    gettype($array)
                )
            );
        }

        $result = new Dictionary();
        foreach ($array as $i => $element) {
            $key = $callback($element, $i);
            if (is_bool($key)) {
                $key = $key ? 'true' : 'false';
            } elseif (is_null($key)) {
                $key = 'null';
            }
            if (!isset($result[$key])) {
                $result[$key] = 1;
            } else {
                ++$result[$key];
            }
        }

        return $result;
    }

    /**
     * @param $array
     * @param $callback
     *
     * @return bool
     *
     * @throws RuntimeError
     */
    public static function every($array, $callback): bool
    {
        if (!is_callable($callback)) {
            throw new RuntimeError(
                sprintf(
                    'Second argument of "every" must be callable, but is "%s".',
                    gettype($callback)
                )
            );
        }

        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new RuntimeError(
                sprintf(
                    'First argument of "every" must be array or Traversable, but is "%s".',
                    gettype($array)
                )
            );
        }

        foreach ($array as $i => $item) {
            if (!$callback($item, $i)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $array
     * @param $callback
     *
     * @return bool
     *
     * @throws RuntimeError
     */
    public static function any($array, $callback): bool
    {
        if (!is_callable($callback)) {
            throw new RuntimeError(
                sprintf(
                    'Second argument of "any" must be callable, but is "%s".',
                    gettype($callback)
                )
            );
        }

        if (!is_array($array) && !($array instanceof \Traversable)) {
            throw new RuntimeError(
                sprintf(
                    'First argument of "any" must be array or Traversable, but is "%s".',
                    gettype($array)
                )
            );
        }

        foreach ($array as $i => $item) {
            if ($callback($item, $i)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $callback
     * @param array $args
     *
     * @return mixed
     */
    public static function call($callback, array $args = [])
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('First argument must be callable.');
        }

        return call_user_func_array($callback, $args);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'dpolac_lambda_extension';
    }
}