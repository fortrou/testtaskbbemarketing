<?php
namespace Moda\DB;

use Moda\DB\Abstracts\ModaDB;

class ModaCelebrities extends ModaDB {
    private $table = 'moda_celebrities';

    public function get_item($id) {
        global $wpdb;
        $table = $wpdb->prefix . $this->table;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public function save_item($data) {
        $data_to_save = [
            'full_name' => '',
            'industry' => '',
        ];

        $data_to_save = array_merge($data_to_save, $data);

        global $wpdb;
        $table = $wpdb->prefix . $this->table;
        
        if (isset($data['id']) && !empty($data['id'])) {
            $wpdb->update($table, $data_to_save, ['id' => $data['id']]);
            return $data['id'];
        } else {
            return $wpdb->insert($table, $data_to_save);
        }
    }

    public function delete_item($id) {
        global $wpdb;
        $table = $wpdb->prefix . $this->table;  
        return $wpdb->delete($table, ['id' => $id]);
    }

    public function get_list($filters) {
        // Implementation for getting a list of stylists based on filters
    }

    public function run_delta() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'moda_celebrities';

        $sql = "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            industry varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_celeb_full_name (full_name(191)),
            KEY idx_industry (industry(191))
        ) $charset_collate;";

        dbDelta($sql);
    }
}
