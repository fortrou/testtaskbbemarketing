<?php
namespace Moda\DB;

use Moda\DB\Abstracts\ModaDB;

class ModaStylists extends ModaDB {
    public function get_item($id) {
        // Implementation for getting a stylist by ID
    }

    public function save_item($data) {
        // Implementation for saving a stylist
    }

    public function delete_item($id) {
        // Implementation for deleting a stylist by ID
    }

    public function get_list($filters) {
        // Implementation for getting a list of stylists based on filters
    }

    public function run_delta() {
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

        dbDelta($stylists_sql);
        dbDelta($join_sql);
    }
}
