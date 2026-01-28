<?php
namespace Moda;

use Moda\DB\ModaStylists;
class ModaAjax {
    private static $instance = null;

    private function __construct() {}

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run_actions() {
        add_action('wp_ajax_moda_save_stylist_details', [$this, 'moda_save_stylist_details']);
        add_action('wp_ajax_moda_search_celebrities', [$this, 'moda_search_celebrities']);
    }

    public function moda_save_stylist_details() {
        check_ajax_referer('moda_ajax_nonce', '_ajax_nonce');

        $stylist_id = isset($_POST['stylist_id']) ? (int) $_POST['stylist_id'] : 0;
        $stylist_data = isset($_POST['stylist_data']) && is_array($_POST['stylist_data'])
            ? wp_unslash($_POST['stylist_data'])
            : [];

        $payload = [];
        $field_map = [
            'stylist_full_name' => 'full_name',
            'stylist_email' => 'email',
            'stylist_phone' => 'phone',
            'stylist_instagram' => 'instagram',
            'stylist_website' => 'website',
        ];
        foreach ($field_map as $input_key => $field) {
            if (!array_key_exists($input_key, $stylist_data)) {
                continue;
            }
            $value = $stylist_data[$input_key];
            if ($field === 'email') {
                $payload[$field] = sanitize_email($value);
            } elseif ($field === 'website') {
                $payload[$field] = esc_url_raw($value);
            } else {
                $payload[$field] = sanitize_text_field($value);
            }
        }

        if ($stylist_id <= 0 || empty($payload)) {
            wp_send_json_error(['message' => 'Invalid stylist ID or data.'], 400);
        }

        $result = ModaStylists::instance()->save_item(array_merge(['id' => $stylist_id], $payload));
        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to save stylist details.'], 500);
        }

        wp_send_json_success(['message' => 'Stylist details saved successfully.']);
    }

    public function moda_search_celebrities() {
        check_ajax_referer('moda_ajax_nonce', '_ajax_nonce');

        global $wpdb;

        $stylist_id = isset($_POST['stylist_id']) ? (int) $_POST['stylist_id'] : 0;
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';

        if (!$stylist_id) {
            wp_send_json_error(['message' => 'Invalid stylist ID.'], 400);
        }

        $celebs_table = $wpdb->prefix . 'moda_celebrities';
        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';

        $where = 'sc.celebrity_id IS NULL';
        $params = [$stylist_id];

        if ($search !== '') {
            $where .= ' AND c.full_name LIKE %s';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $sql = "SELECT c.id, c.full_name
                FROM $celebs_table c
                LEFT JOIN $join_table sc
                    ON sc.celebrity_id = c.id AND sc.stylist_id = %d
                WHERE $where
                ORDER BY c.full_name ASC
                LIMIT 20";

        $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        wp_send_json_success($results);
    }
}
