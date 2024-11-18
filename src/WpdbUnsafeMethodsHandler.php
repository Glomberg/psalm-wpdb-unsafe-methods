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
        'Expr_BinaryOp_Concat'
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

            $method_name = $expression->name->name;
            $method_args = $expression->args;

            // 2.1) If the expression contains string - it is a forbidden method call
            if (
                in_array($method_name, self::$unsafe_methods, true) &&
                count($method_args) === 1 &&
                in_array($method_args[0]->value->getType(), self::$unsafe_variables_types, true)
            ) {
                $code_location = new CodeLocation($event->getStatementsSource(), $expression);
                $file_path = $code_location->file_path;
                $line_number = $code_location->getLineNumber() - 2;
                $file_lines = file($file_path);
                if (isset($file_lines[$line_number]) && strpos($file_lines[$line_number], '@psalm-suppress WpdbUnsafeMethodsIssue') !== false) {
                    return true;
                }

                IssueBuffer::maybeAdd(
                    new WpdbUnsafeMethodsIssue(
                        "Forbidden method call: {$method_name}",
                        new CodeLocation($event->getStatementsSource(), $expression),
                    )
                );
            }

            // 2.2) If the expression contains variable, check if it is a string - it is also forbidden method call
            if ( in_array($method_name, self::$unsafe_methods, true) && count($method_args) === 1 && $method_args[0]->value->getType() === 'Expr_Variable' ) {
                $var_name = $method_args[0]->value->name;
                if (
                    isset(self::$collected_variables[$var_name]) &&
                    in_array(self::$collected_variables[$var_name]['expression_type'], self::$unsafe_variables_types, true)
                ) {
                    $code_location = new CodeLocation($event->getStatementsSource(), $expression);
                    $file_path = $code_location->file_path;
                    $line_number = $code_location->getLineNumber() - 2;
                    $file_lines = file($file_path);
                    if (isset($file_lines[$line_number]) && strpos($file_lines[$line_number], '@psalm-suppress WpdbUnsafeMethodsIssue') !== false) {
                        return true;
                    }

                    IssueBuffer::maybeAdd(
                        new WpdbUnsafeMethodsIssue(
                            "Forbidden method call: {$method_name}",
                            new CodeLocation($event->getStatementsSource(), $expression),
                        )
                    );
                }
            }
        }

        return true;
    }
}
