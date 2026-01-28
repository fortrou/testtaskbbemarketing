<?php
/*
 * Plugin Name: Moda Api
 * Plugin URI: @fortrou
 * Description: Moda api plugin
 * Author: fortrou
 * Author URI: @fortrou
 * Version: 0.1.0
 * Requires at least: 6.1
 * Requires PHP: 7.4
 */

use Moda\DB\ModaStylistReps;
use Moda\DB\ModaStylists;
use Moda\DB\ModaCelebrities;
use Moda\ModaCli;
use Moda\ModaApp;

require_once __DIR__ . '/config.php';

spl_autoload_register(function ($class) {
    $prefix = 'Moda\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    // Базовая директория классов
    $baseDir = __DIR__ . '/classes/';

    // Отрезаем prefix
    $relativeClass = substr($class, strlen($prefix));

    // Формируем путь
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Подключаем файл
    if (file_exists($file)) {
        require $file;
    }
});
ModaApp::instance()->run_actions();
register_activation_hook(__FILE__, function() {
    ModaApp::run_deltas();
});

if (defined('WP_CLI') && WP_CLI) {
    ModaCli::register();
}
