<?php
namespace Moda\DB;

use Moda\DB\Abstracts\ModaDB;

class ModaStylistReps extends ModaDB {
    private $table = 'moda_stylist_reps';

    public function get_item($id) {
        global $wpdb;

        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $table = $wpdb->prefix . $this->table;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }

    public function save_item($data) {
        global $wpdb;

        $table = $wpdb->prefix . $this->table;
        $now = current_time('mysql');

        $id = isset($data['id']) ? (int) $data['id'] : 0;

        $payload = [
            'stylist_id' => isset($data['stylist_id']) ? (int) $data['stylist_id'] : 0,
            'rep_name' => isset($data['rep_name']) ? $data['rep_name'] : null,
            'company' => $data['company'] ?? null,
            'rep_email' => $data['rep_email'] ?? null,
            'rep_phone' => $data['rep_phone'] ?? null,
            'territory' => $data['territory'] ?? null,
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

        $table = $wpdb->prefix . $this->table;
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);
        if ($result === false) {
            return false;
        }
        return $result > 0;
    }

    public function get_list($filters) {
        global $wpdb;

        $table = $wpdb->prefix . $this->table;

        $stylist_id = isset($filters['stylist_id']) ? (int) $filters['stylist_id'] : 0;
        $search = isset($filters['search']) ? sanitize_text_field($filters['search']) : '';
        $orderby = isset($filters['orderby']) ? sanitize_text_field($filters['orderby']) : 'updated_at';
        $order = isset($filters['order']) ? strtoupper(sanitize_text_field($filters['order'])) : 'DESC';
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;

        $allowed_orderby = ['rep_name', 'updated_at', 'created_at', 'company'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'updated_at';
        }
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);

        $where = '1=1';
        $params = [];

        if ($stylist_id > 0) {
            $where .= " AND stylist_id = %d";
            $params[] = $stylist_id;
        }

        if ($search !== '') {
            $where .= " AND rep_name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $sql = "SELECT *
                FROM $table
                WHERE $where
                ORDER BY $orderby $order
                LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
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
