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
use Moda\ModaAdminLayouts;
use Moda\ModaCli;
use Moda\ModaAjax;

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
ModaAdminLayouts::instance()->run_actions();
ModaAjax::instance()->run_actions();
register_activation_hook(__FILE__, function() {
    ModaStylists::instance()->run_delta();
    ModaCelebrities::instance()->run_delta();
    ModaStylistReps::instance()->run_delta();
});

if (defined('WP_CLI') && WP_CLI) {
    ModaCli::register();
}
