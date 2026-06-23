<?php
declare(strict_types=1);

define('IMF_TESTING', 1);

$plugin_root = dirname(__DIR__);
$wp_root     = dirname(__DIR__, 4);

$composer_autoload = $plugin_root . '/vendor/autoload.php';

// Define ABSPATH early so includes/helpers.php does not exit.
defined('ABSPATH') || define('ABSPATH', $wp_root . '/');

$is_integration = false;
foreach ($_SERVER['argv'] ?? [] as $arg) {
    if (str_contains($arg, '--testsuite=integration') || str_contains($arg, 'integration')) {
        $is_integration = true;
        break;
    }
}

if ($is_integration) {
    $config_file = $plugin_root . '/tests/wp-tests-config.php';
    if (!is_readable($config_file)) {
        fwrite(STDERR, "ERROR: wp-tests-config.php not found at {$config_file}\n");
        fwrite(STDERR, "Copy tests/wp-tests-config-sample.php to tests/wp-tests-config.php and edit.\n");
        exit(1);
    }

    $wp_phpunit = $plugin_root . '/vendor/wp-phpunit/wp-phpunit/includes/bootstrap.php';
    if (!is_readable($wp_phpunit)) {
        fwrite(STDERR, "ERROR: wp-phpunit not installed. Run composer install.\n");
        exit(1);
    }

    putenv('WP_PHPUNIT__TESTS_CONFIG=' . $config_file);
    putenv('WP_PHPUNIT__TABLE_PREFIX=wptests_');

    $WP_PHPUNIT__TESTS_CONFIG = $config_file;
    require $composer_autoload;

    require $wp_phpunit;

    require_once $plugin_root . '/imedia-registration.php';

    // Create the entries table that is normally created on plugin activation.
    if (class_exists('IMF_Database') && method_exists('IMF_Database', 'create_entries_table')) {
        \IMF_Database::create_entries_table();
    }
} else {
    require $composer_autoload;
    require_once $plugin_root . '/tests/stubs/wp-functions.php';
    require_once $plugin_root . '/includes/helpers.php';
}
