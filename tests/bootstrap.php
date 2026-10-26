<?php

$composer = __DIR__ . '/../vendor/autoload.php';

if (is_file($composer)) {
    require $composer;
}

spl_autoload_register(function (string $class): void {
    $prefix = 'Ngoni\\DischubPhp\\Tests\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});
