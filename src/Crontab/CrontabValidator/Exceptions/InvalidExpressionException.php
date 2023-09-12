<?php declare(strict_types = 1);
/**
 * @author hollodotme
 */

namespace Sweeper\HelperPhp\Crontab\CrontabValidator\Exceptions;

/**
 * InvalidExpressionException
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:32
 * @Path \Sweeper\HelperPhp\Crontab\CrontabValidator\Exceptions\InvalidExpressionException
 */
final class InvalidExpressionException extends \InvalidArgumentException
{

    /** @var string */
    private $expression;

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function withExpression(string $expression): InvalidExpressionException
    {
        $this->message    = sprintf('Invalid crontab expression: "%s"', $expression);
        $this->expression = $expression;

        return $this;
    }

}
