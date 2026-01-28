<?php
namespace Moda\DB;

use Moda\DB\Abstracts\ModaDB;

class ModaStylists extends ModaDB {
    public function get_item($id) {
        global $wpdb;

        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $table = $wpdb->prefix . 'moda_stylists';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }

    public function save_item($data) {
        global $wpdb;

        $table = $wpdb->prefix . 'moda_stylists';
        $now = current_time('mysql');

        $id = isset($data['id']) ? (int) $data['id'] : 0;
        $payload = [
            'full_name' => $data['full_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'website' => $data['website'] ?? null,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $result = $wpdb->update($table, $payload, ['id' => $id], null, ['%d']);
            if ($result === false) {
                return false;
            }
            return $id;
        }

        $payload['created_at'] = $data['created_at'] ?? $now;
        $result = $wpdb->insert($table, $payload);
        if ($result === false) {
            return false;
        }
        return (int) $wpdb->insert_id;
    }

    public function delete_item($id) {
        global $wpdb;

        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $table = $wpdb->prefix . 'moda_stylists';
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);
        if ($result === false) {
            return false;
        }
        return $result > 0;
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
