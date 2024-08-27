<?php

namespace Glomberg\WpdbUnsafeMethods;

use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

/**
 * @psalm-api
 */
class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $psalm, ?SimpleXMLElement $config = null): void
    {
        if ($config === null) {
            return;
        }

        require_once __DIR__ . '/WpdbUnsafeMethodsHandler.php';

        $unsafe_methods_from_config = array_map(static function ($method) {
            return (string) $method;
        }, (array) $config->method);

        WpdbUnsafeMethodsHandler::setUnsafeMethods($unsafe_methods_from_config);

        $psalm->registerHooksFromClass(WpdbUnsafeMethodsHandler::class);
    }
}
