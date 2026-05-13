<?php

use App\Kernel;

$_SERVER['APP_RUNTIME_OPTIONS'] = [
    'disable_dotenv' => ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev') === 'prod',
];

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
