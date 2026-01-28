<?php
namespace Moda;

class ModaApi {
    private static $instance = null;
    private $namespace = 'moda/v1';

    private function __construct() {}

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run_actions() {
        add_action('rest_api_init', [$this, 'mount_routes']);
    }

    public function mount_routes() {
        register_rest_route($this->namespace, '/stylists', [
            'methods'   => 'GET',
            'callback'  => [$this, 'get_stylists'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route($this->namespace, '/stylists/(?P<id>\d+)', [
            'methods'   => 'GET',
            'callback'  => [$this, 'get_item'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route($this->namespace, '/stylists', [
            'methods'   => 'POST',
            'callback'  => [$this, 'create_stylist'],
            'permission_callback' => [$this, 'admin_permission']
        ]);

        register_rest_route($this->namespace, '/stylists/(?P<id>\d+)', [
            'methods'   => 'PATCH',
            'callback'  => [$this, 'update_stylist'],
            'permission_callback' => [$this, 'admin_permission']
        ]);

        register_rest_route($this->namespace, '/stylists/(?P<id>\d+)/celebrities/(?P<celebrity_id>\d+)', [
            'methods'   => 'POST',
            'callback'  => [$this, 'attach_celebrity'],
            'permission_callback' => [$this, 'admin_permission']
        ]);

        register_rest_route($this->namespace, '/stylists/(?P<id>\d+)/celebrities/(?P<celebrity_id>\d+)', [
            'methods'   => 'DELETE',
            'callback'  => [$this, 'detach_celebrity'],
            'permission_callback' => [$this, 'admin_permission']
        ]);

        register_rest_route($this->namespace, '/stylists/(?P<id>\d+)/reps', [
            'methods'   => 'POST',
            'callback'  => [$this, 'add_representative'],
            'permission_callback' => [$this, 'admin_permission']
        ]);

        register_rest_route($this->namespace, '/reps/(?P<id>\d+)', [
            'methods'   => 'DELETE',
            'callback'  => [$this, 'remove_representative'],
            'permission_callback' => [$this, 'admin_permission']
        ]);
    }

    public function admin_permission() {
        if (!is_user_logged_in()) {
            return new \WP_Error('moda_unauthorized', 'Unauthorized.', ['status' => 401]);
        }
        if (!current_user_can('manage_options')) {
            return new \WP_Error('moda_forbidden', 'Forbidden.', ['status' => 403]);
        }
        return true;
    }

    public function get_item($request) {
        global $wpdb;

        $id = (int) $request['id'];
        if (!$id) {
            return new \WP_Error('moda_invalid_id', 'Invalid stylist ID.', ['status' => 400]);
        }

        $stylists_table = $wpdb->prefix . 'moda_stylists';
        $reps_table = $wpdb->prefix . 'moda_stylist_reps';
        $celebs_table = $wpdb->prefix . 'moda_celebrities';
        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';

        $stylist = $wpdb->get_row($wpdb->prepare("SELECT * FROM $stylists_table WHERE id = %d", $id), ARRAY_A);
        if (!$stylist) {
            return new \WP_Error('moda_not_found', 'Stylist not found.', ['status' => 404]);
        }

        $reps = $wpdb->get_results($wpdb->prepare("SELECT * FROM $reps_table WHERE stylist_id = %d ORDER BY updated_at DESC", $id), ARRAY_A);
        $celebs = $wpdb->get_results($wpdb->prepare(
            "SELECT c.* FROM $celebs_table c
             INNER JOIN $join_table sc ON sc.celebrity_id = c.id
             WHERE sc.stylist_id = %d
             ORDER BY c.full_name ASC",
            $id
        ), ARRAY_A);

        $response = rest_ensure_response([
            'stylist' => $stylist,
            'reps' => $reps,
            'celebrities' => $celebs,
        ]);
        $response->set_status(200);
        return $response;
    }

    public function get_stylists($request) {
        global $wpdb;

        $stylists_table = $wpdb->prefix . 'moda_stylists';
        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';
        $celebs_table = $wpdb->prefix . 'moda_celebrities';

        $per_page = max(10, (int) $request->get_param('per_page'));
        $page = max(1, (int) $request->get_param('page'));
        $search = sanitize_text_field($request->get_param('q'));
        $celebrity = $request->get_param('celebrity');
        $sort = sanitize_text_field($request->get_param('sort') ?: 'updated_at');
        $order = strtoupper(sanitize_text_field($request->get_param('order') ?: 'DESC'));

        $allowed_sort = ['name', 'updated_at'];
        if (!in_array($sort, $allowed_sort, true)) {
            $sort = 'updated_at';
        }
        $orderby = ($sort === 'name') ? 'full_name' : 'updated_at';
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';

        $where = '1=1';
        $join = '';
        $params = [];

        if ($celebrity !== null && $celebrity !== '') {
            $join = " INNER JOIN $join_table sc ON sc.stylist_id = s.id INNER JOIN $celebs_table c ON c.id = sc.celebrity_id";
            if (is_numeric($celebrity)) {
                $where .= " AND sc.celebrity_id = %d";
                $params[] = (int) $celebrity;
            } else {
                $where .= " AND c.full_name LIKE %s";
                $params[] = '%' . $wpdb->esc_like(sanitize_text_field($celebrity)) . '%';
            }
        }

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= " AND s.full_name LIKE %s";
            $params[] = $like;
        }

        $count_sql = "SELECT COUNT(DISTINCT s.id) FROM $stylists_table s $join WHERE $where";
        $total = (int) ($params ? $wpdb->get_var($wpdb->prepare($count_sql, $params)) : $wpdb->get_var($count_sql));

        $offset = ($page - 1) * $per_page;
        $sql = "SELECT s.id, s.full_name, s.email, s.phone, s.instagram, s.website, s.created_at, s.updated_at, COUNT(DISTINCT sc2.celebrity_id) AS celebrity_count
                FROM $stylists_table s
                LEFT JOIN $join_table sc2 ON sc2.stylist_id = s.id
                $join
                WHERE $where
                GROUP BY s.id
                ORDER BY s.$orderby $order
                LIMIT %d OFFSET %d";
        $query_params = array_merge($params, [$per_page, $offset]);
        $items = $wpdb->get_results($wpdb->prepare($sql, $query_params), ARRAY_A);

        $response = rest_ensure_response($items);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', (string) max(1, (int) ceil($total / $per_page)));
        $response->set_status(200);
        return $response;
    }

    public function create_stylist($request) {
        global $wpdb;

        $full_name = sanitize_text_field($request->get_param('full_name'));
        if (!$full_name) {
            return new \WP_Error('moda_missing_full_name', 'full_name is required.', ['status' => 400]);
        }

        $table = $wpdb->prefix . 'moda_stylists';
        $data = [
            'full_name' => $full_name,
            'email' => sanitize_email($request->get_param('email')),
            'phone' => sanitize_text_field($request->get_param('phone')),
            'instagram' => sanitize_text_field($request->get_param('instagram')),
            'website' => esc_url_raw($request->get_param('website')),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $data);
        if ($result === false) {
            return new \WP_Error('moda_create_failed', 'Failed to create stylist.', ['status' => 500]);
        }

        $id = (int) $wpdb->insert_id;
        $response = rest_ensure_response(['id' => $id]);
        $response->set_status(201);
        return $response;
    }

    public function update_stylist($request) {
        global $wpdb;

        $id = (int) $request['id'];
        if (!$id) {
            return new \WP_Error('moda_invalid_id', 'Invalid stylist ID.', ['status' => 400]);
        }

        $table = $wpdb->prefix . 'moda_stylists';
        $data = [];

        $fields = ['full_name', 'email', 'phone', 'instagram', 'website'];
        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                $value = $request->get_param($field);
                if ($field === 'email') {
                    $value = sanitize_email($value);
                } elseif ($field === 'website') {
                    $value = esc_url_raw($value);
                } else {
                    $value = sanitize_text_field($value);
                }
                $data[$field] = $value;
            }
        }

        if (!$data) {
            return new \WP_Error('moda_no_fields', 'No fields to update.', ['status' => 400]);
        }

        $data['updated_at'] = current_time('mysql');
        $updated = $wpdb->update($table, $data, ['id' => $id], null, ['%d']);
        if ($updated === false) {
            return new \WP_Error('moda_update_failed', 'Failed to update stylist.', ['status' => 500]);
        }

        $response = rest_ensure_response(['updated' => (bool) $updated]);
        $response->set_status(200);
        return $response;
    }

    public function attach_celebrity($request) {
        global $wpdb;

        $stylist_id = (int) $request['id'];
        $celebrity_id = (int) $request['celebrity_id'];
        if (!$stylist_id || !$celebrity_id) {
            return new \WP_Error('moda_invalid_ids', 'Invalid IDs.', ['status' => 400]);
        }

        $table = $wpdb->prefix . 'moda_stylist_celebrity';
        $result = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $table (stylist_id, celebrity_id) VALUES (%d, %d)",
            $stylist_id,
            $celebrity_id
        ));
        if ($result === false) {
            return new \WP_Error('moda_attach_failed', 'Failed to attach celebrity.', ['status' => 500]);
        }

        $response = rest_ensure_response(['attached' => true]);
        $response->set_status(201);
        return $response;
    }

    public function detach_celebrity($request) {
        global $wpdb;

        $stylist_id = (int) $request['id'];
        $celebrity_id = (int) $request['celebrity_id'];
        if (!$stylist_id || !$celebrity_id) {
            return new \WP_Error('moda_invalid_ids', 'Invalid IDs.', ['status' => 400]);
        }

        $table = $wpdb->prefix . 'moda_stylist_celebrity';
        $deleted = $wpdb->delete($table, ['stylist_id' => $stylist_id, 'celebrity_id' => $celebrity_id], ['%d', '%d']);
        if ($deleted === false) {
            return new \WP_Error('moda_detach_failed', 'Failed to detach celebrity.', ['status' => 500]);
        }
        if ($deleted === 0) {
            return new \WP_Error('moda_not_found', 'Link not found.', ['status' => 404]);
        }

        $response = rest_ensure_response(['detached' => true]);
        $response->set_status(200);
        return $response;
    }

    public function add_representative($request) {
        global $wpdb;

        $stylist_id = (int) $request['id'];
        $rep_name = sanitize_text_field($request->get_param('rep_name'));
        if (!$stylist_id || !$rep_name) {
            return new \WP_Error('moda_invalid_input', 'stylist_id and rep_name are required.', ['status' => 400]);
        }

        $table = $wpdb->prefix . 'moda_stylist_reps';
        $result = $wpdb->insert($table, [
            'stylist_id' => $stylist_id,
            'rep_name' => $rep_name,
            'company' => sanitize_text_field($request->get_param('company')),
            'rep_email' => sanitize_email($request->get_param('rep_email')),
            'rep_phone' => sanitize_text_field($request->get_param('rep_phone')),
            'territory' => sanitize_text_field($request->get_param('territory')),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        if ($result === false) {
            return new \WP_Error('moda_rep_create_failed', 'Failed to create representative.', ['status' => 500]);
        }

        $response = rest_ensure_response(['id' => (int) $wpdb->insert_id]);
        $response->set_status(201);
        return $response;
    }

    public function remove_representative($request) {
        global $wpdb;

        $id = (int) $request['id'];
        if (!$id) {
            return new \WP_Error('moda_invalid_id', 'Invalid rep ID.', ['status' => 400]);
        }

        $table = $wpdb->prefix . 'moda_stylist_reps';
        $deleted = $wpdb->delete($table, ['id' => $id], ['%d']);
        if ($deleted === false) {
            return new \WP_Error('moda_rep_delete_failed', 'Failed to delete representative.', ['status' => 500]);
        }
        if ($deleted === 0) {
            return new \WP_Error('moda_not_found', 'Representative not found.', ['status' => 404]);
        }

        $response = rest_ensure_response(['deleted' => true]);
        $response->set_status(200);
        return $response;
    }
}
