<?php
namespace Moda\DB;

use Moda\DB\Abstracts\ModaDB;

class ModaStylistReps extends ModaDB {
    private $table = 'moda_stylist_reps';

    public function get_item($id) {
        var_dump($id);die;
        // Implementation for getting a stylist rep by ID
    }

    public function save_item($data) {
        // Implementation for saving a stylist rep
    }

    public function delete_item($id) {
        // Implementation for deleting a stylist rep by ID
    }

    public function get_list($filters) {
        // Implementation for getting a list of stylist reps based on filters
    }

    public function run_delta() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'moda_stylist_reps';

        $sql = "CREATE TABLE $table (
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

        dbDelta($sql);
    }
}
