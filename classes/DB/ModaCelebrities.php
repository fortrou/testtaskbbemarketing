<?php
namespace Moda\DB;

use Moda\DB\Abstracts\ModaDB;

class ModaCelebrities extends ModaDB {
    private $table = 'moda_celebrities';

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
            'full_name' => $data['full_name'] ?? null,
            'industry' => $data['industry'] ?? null,
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

        $search = isset($filters['search']) ? sanitize_text_field($filters['search']) : '';
        $industry = isset($filters['industry']) ? sanitize_text_field($filters['industry']) : '';
        $orderby = isset($filters['orderby']) ? sanitize_text_field($filters['orderby']) : 'updated_at';
        $order = isset($filters['order']) ? strtoupper(sanitize_text_field($filters['order'])) : 'DESC';
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;

        $allowed_orderby = ['full_name', 'updated_at', 'created_at', 'industry'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'updated_at';
        }
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);

        $where = '1=1';
        $params = [];

        if ($industry !== '') {
            $where .= " AND industry = %s";
            $params[] = $industry;
        }

        if ($search !== '') {
            $where .= " AND full_name LIKE %s";
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

    public function attach_to_stylist($stylist_id, $celebrity_id) {
        global $wpdb;

        $stylist_id = (int) $stylist_id;
        $celebrity_id = (int) $celebrity_id;
        if ($stylist_id <= 0 || $celebrity_id <= 0) {
            return false;
        }

        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';
        $result = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $join_table (stylist_id, celebrity_id) VALUES (%d, %d)",
            $stylist_id,
            $celebrity_id
        ));
        if ($result === false) {
            return false;
        }
        return true;
    }

    public function detach_from_stylist($stylist_id, $celebrity_id) {
        global $wpdb;

        $stylist_id = (int) $stylist_id;
        $celebrity_id = (int) $celebrity_id;
        if ($stylist_id <= 0 || $celebrity_id <= 0) {
            return false;
        }

        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';
        $result = $wpdb->delete(
            $join_table,
            ['stylist_id' => $stylist_id, 'celebrity_id' => $celebrity_id],
            ['%d', '%d']
        );
        if ($result === false) {
            return false;
        }
        return $result > 0;
    }

}
