<?php

declare(strict_types=1);

namespace Glomberg\WpdbUnsafeMethods;

use Psalm\CodeLocation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

final class WpdbUnsafeMethodsHandler implements AfterExpressionAnalysisInterface
{
    private static array $collected_variables = [];

    private static array $unsafe_variables_types = [
        'Scalar_String',
        'Scalar_Encapsed',
        'Expr_BinaryOp_Concat',
        'Expr_FuncCall',
        ];

    private static array $unsafe_methods = [];


    public static function setUnsafeMethods(array $unsafe_methods): void
    {
        self::$unsafe_methods = $unsafe_methods;
    }

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expression = $event->getExpr();

        // 1) If the expression is a variable assignment, store the variable name
        if ( $expression->getType() === 'Expr_Assign' && $expression->var->getType() === 'Expr_Variable' ) {
            $var_name = $expression->var->name;
            self::$collected_variables[$var_name] = [
                'expression_type' => $expression->expr->getType(),
            ];
        }

        // 2) Check the expression against unsafe method usage
        if ( $expression->getType() === 'Expr_MethodCall' ) {

            $method_name = $expression->name->name ?? '';
            $method_args = $expression->args;

            if ( ! in_array($method_name, self::$unsafe_methods, true) || $method_args === [] ) {
                return true;
            }

            // Extra args (ARRAY_A, OBJECT, ...) must not hide an unprepared SQL query.
            $first_arg = $method_args[0]->value;
            $first_arg_type = $first_arg->getType();

            // 2.1) If the first argument is a raw SQL expression - it is a forbidden method call
            if ( in_array($first_arg_type, self::$unsafe_variables_types, true) ) {
                if ( self::isSuppressed($event, $expression) ) {
                    return true;
                }

                self::report($event, $expression, $method_name);
            }

            // 2.2) If the first argument is a variable that was assigned raw SQL - also forbidden
            if ( $first_arg_type === 'Expr_Variable' ) {
                $var_name = $first_arg->name;
                if (
                    isset(self::$collected_variables[$var_name]) &&
                    in_array(self::$collected_variables[$var_name]['expression_type'], self::$unsafe_variables_types, true)
                ) {
                    if ( self::isSuppressed($event, $expression) ) {
                        return true;
                    }

                    self::report($event, $expression, $method_name);
                }
            }
        }

        return true;
    }

    private static function isSuppressed(AfterExpressionAnalysisEvent $event, $expression): bool
    {
        $code_location = new CodeLocation($event->getStatementsSource(), $expression);
        $file_path = $code_location->file_path;
        $line_number = $code_location->getLineNumber() - 2;
        $file_lines = file($file_path);

        return isset($file_lines[$line_number])
            && strpos($file_lines[$line_number], '@psalm-suppress WpdbUnsafeMethodsIssue') !== false;
    }

    private static function report(AfterExpressionAnalysisEvent $event, $expression, string $method_name): void
    {
        IssueBuffer::maybeAdd(
            new WpdbUnsafeMethodsIssue(
                "Forbidden method call: {$method_name}",
                new CodeLocation($event->getStatementsSource(), $expression),
            )
        );
    }
}
