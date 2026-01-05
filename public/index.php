<?php

declare(strict_types=1);

$path = $_SERVER['REQUEST_URI'];

// Remove trailing slashes
if ($path !== rtrim($path, '/') && $path !== '/') {
    header('Location: ' . rtrim($path, '/'), response_code: 308);
    exit;
}

match($path) {
    '/' => require __DIR__ . '/../views/index.php',
    default => require __DIR__ . '/../views/repo.php',
};

