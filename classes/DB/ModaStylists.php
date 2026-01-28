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

    
}
