<?php
define('MODA_VERSION', '0.1.0');
define('MODA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MODA_PLUGIN_URL', plugin_dir_url(__FILE__));



add_action('admin_enqueue_scripts', 'moda_admin_scripts');
function moda_admin_scripts() {
    wp_enqueue_script(
        'moda_scripts',
        MODA_PLUGIN_URL . '/assets/js/main.js',
        ['jquery'],
        filemtime(MODA_PLUGIN_URL . 'assets/js/main.js')
    );
    wp_localize_script('moda_scripts', 'moda_ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'moda_ajax_nonce' ),
    ));
}
