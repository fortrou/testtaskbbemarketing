<?php
namespace Moda;

use Moda\ModaAdminLayouts;
use Moda\ModaAjax;
use Moda\ModaApi;

class ModaApp {

    private static $instance = null;

    private function __construct() {}

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run_actions() {
        ModaAdminLayouts::instance()->run_actions();
        ModaAjax::instance()->run_actions();
        ModaApi::instance()->run_actions();
    }

    public static function run_deltas() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $stylists_table = $wpdb->prefix . 'moda_stylists';
        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';

        $stylists_sql = "CREATE TABLE $stylists_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            email varchar(255) DEFAULT NULL,
            phone varchar(255) DEFAULT NULL,
            instagram varchar(255) DEFAULT NULL,
            website varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_full_name (full_name(191)),
            KEY idx_updated_at (updated_at)
        ) $charset_collate;";

        $join_sql = "CREATE TABLE $join_table (
            stylist_id bigint(20) unsigned NOT NULL,
            celebrity_id bigint(20) unsigned NOT NULL,
            notes varchar(255) DEFAULT NULL,
            UNIQUE KEY uniq_stylist_celebrity (stylist_id, celebrity_id),
            KEY idx_stylist_id (stylist_id),
            KEY idx_celebrity_id (celebrity_id)
        ) $charset_collate;";

        $charset_collate = $wpdb->get_charset_collate();
        $celebritiesTable = $wpdb->prefix . 'moda_celebrities';

        $celebritiesSql = "CREATE TABLE $celebritiesTable (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            industry varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_celeb_full_name (full_name(191)),
            KEY idx_industry (industry(191))
        ) $charset_collate;";

        $repsTable = $wpdb->prefix . 'moda_stylist_reps';

        $repsSql = "CREATE TABLE $repsTable (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            stylist_id bigint(20) unsigned NOT NULL,
            rep_name varchar(255) NOT NULL,
            company varchar(255) DEFAULT NULL,
            rep_email varchar(255) DEFAULT NULL,
            rep_phone varchar(255) DEFAULT NULL,
            territory varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_stylist_id (stylist_id)
        ) $charset_collate;";

        dbDelta($repsSql);
        dbDelta($stylists_sql);
        dbDelta($join_sql);
        dbDelta($celebritiesSql);
    }
}