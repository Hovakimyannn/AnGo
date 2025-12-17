<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    // In CI we may not have a committed ".env". PHPUnit config injects the needed env vars.
    // Only boot .env if it exists AND we are not already running in test env.
    $appEnv = (string) ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? '');
    $envFile = dirname(__DIR__).'/.env';
    if ($appEnv !== 'test' && is_file($envFile)) {
        (new Dotenv())->bootEnv($envFile);
    }
}

// Ensure session save path exists (framework.yaml uses var/sessions/%kernel.environment%).
$sessionsDir = dirname(__DIR__).'/var/sessions/test';
if (!is_dir($sessionsDir)) {
    @mkdir($sessionsDir, 0777, true);
}

if (!empty($_SERVER['APP_DEBUG'])) {
    umask(0000);
}
