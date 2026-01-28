<?php
namespace Moda;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ModaAdminLayouts {
    private static $instance = null;
    private $page_slug = 'moda-database';

    private function __construct() {}

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run_actions() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu() {
        add_menu_page(
            'Moda Database',
            'Moda Database',
            'manage_options',
            $this->page_slug,
            [$this, 'render_page'],
            'dashicons-database',
            26
        );
    }

    public function render_page() {
        $this->handle_post_actions();

        $view = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : 'list';

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Moda Database</h1>';

        if ($view === 'detail') {
            $this->render_stylist_detail();
        } else {
            $this->render_stylists_list();
        }

        echo '</div>';
    }

    private function render_stylists_list() {
        $search = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
        $celebrity_id = isset($_REQUEST['celebrity_id']) ? (int) $_REQUEST['celebrity_id'] : 0;

        $list_table = new ModaStylistsListTable();
        $list_table->set_filters($search, $celebrity_id);
        $list_table->prepare_items();

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr($this->page_slug) . '" />';

        $this->render_celebrity_filter($celebrity_id);
        $list_table->search_box('Search stylists', 'moda-stylists');
        $list_table->display();
        echo '</form>';
    }

    private function render_celebrity_filter($selected_id) {
        global $wpdb;

        $celebs_table = $wpdb->prefix . 'moda_celebrities';
        $celebs = $wpdb->get_results("SELECT id, full_name FROM $celebs_table ORDER BY full_name ASC");

        echo '<div style="margin: 12px 0;">';
        echo '<label for="celebrity_id" style="margin-right: 8px;">Filter by celebrity:</label>';
        echo '<select name="celebrity_id" id="celebrity_id">';
        echo '<option value="0">All</option>';
        foreach ($celebs as $celeb) {
            $selected = ((int) $selected_id === (int) $celeb->id) ? ' selected' : '';
            echo '<option value="' . esc_attr($celeb->id) . '"' . $selected . '>' . esc_html($celeb->full_name) . '</option>';
        }
        echo '</select> ';
        submit_button('Filter', 'secondary', '', false);
        echo '</div>';
    }

    private function render_stylist_detail() {
        global $wpdb;

        $stylist_id = isset($_GET['stylist_id']) ? (int) $_GET['stylist_id'] : 0;
        if (!$stylist_id) {
            echo '<p>Stylist not found.</p>';
            return;
        }

        $stylists_table = $wpdb->prefix . 'moda_stylists';
        $reps_table = $wpdb->prefix . 'moda_stylist_reps';
        $celebs_table = $wpdb->prefix . 'moda_celebrities';
        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';

        $stylist = $wpdb->get_row($wpdb->prepare("SELECT * FROM $stylists_table WHERE id = %d", $stylist_id), ARRAY_A);
        if (!$stylist) {
            echo '<p>Stylist not found.</p>';
            return;
        }

        $reps = $wpdb->get_results($wpdb->prepare("SELECT * FROM $reps_table WHERE stylist_id = %d ORDER BY updated_at DESC", $stylist_id), ARRAY_A);
        $celebs = $wpdb->get_results($wpdb->prepare(
            "SELECT c.* FROM $celebs_table c
             INNER JOIN $join_table sc ON sc.celebrity_id = c.id
             WHERE sc.stylist_id = %d
             ORDER BY c.full_name ASC",
            $stylist_id
        ), ARRAY_A);

        $back_url = admin_url('admin.php?page=' . $this->page_slug);
        require_once MODA_PLUGIN_PATH . 'templates/stylist_details.php';
    }

    private function handle_post_actions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_POST['moda_action']) ? sanitize_text_field(wp_unslash($_POST['moda_action'])) : '';
        if (!$action) {
            return;
        }

        check_admin_referer('moda_admin_action');

        global $wpdb;

        $stylists_table = $wpdb->prefix . 'moda_stylists';
        $reps_table = $wpdb->prefix . 'moda_stylist_reps';
        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';

        $stylist_id = isset($_POST['stylist_id']) ? (int) $_POST['stylist_id'] : 0;
        if (!$stylist_id || !$wpdb->get_var($wpdb->prepare("SELECT id FROM $stylists_table WHERE id = %d", $stylist_id))) {
            return;
        }

        if ($action === 'add_rep') {
            $rep_name = isset($_POST['rep_name']) ? sanitize_text_field(wp_unslash($_POST['rep_name'])) : '';
            if ($rep_name) {
                $wpdb->insert($reps_table, [
                    'stylist_id' => $stylist_id,
                    'rep_name' => $rep_name,
                    'company' => isset($_POST['company']) ? sanitize_text_field(wp_unslash($_POST['company'])) : null,
                    'rep_email' => isset($_POST['rep_email']) ? sanitize_email(wp_unslash($_POST['rep_email'])) : null,
                    'rep_phone' => isset($_POST['rep_phone']) ? sanitize_text_field(wp_unslash($_POST['rep_phone'])) : null,
                    'territory' => isset($_POST['territory']) ? sanitize_text_field(wp_unslash($_POST['territory'])) : null,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]);
            }
        } elseif ($action === 'attach_celebrity') {
            $celebrity_id = isset($_POST['celebrity_id']) ? (int) $_POST['celebrity_id'] : 0;
            if ($celebrity_id) {
                $wpdb->query($wpdb->prepare(
                    "INSERT IGNORE INTO $join_table (stylist_id, celebrity_id) VALUES (%d, %d)",
                    $stylist_id,
                    $celebrity_id
                ));
            }
        } elseif ($action === 'remove_rep') {
            $rep_id = isset($_POST['rep_id']) ? (int) $_POST['rep_id'] : 0;
            if ($rep_id) {
                $wpdb->delete($reps_table, ['id' => $rep_id, 'stylist_id' => $stylist_id], ['%d', '%d']);
            }
        } elseif ($action === 'detach_celebrity') {
            $celebrity_id = isset($_POST['celebrity_id']) ? (int) $_POST['celebrity_id'] : 0;
            if ($celebrity_id) {
                $wpdb->delete($join_table, ['stylist_id' => $stylist_id, 'celebrity_id' => $celebrity_id], ['%d', '%d']);
            }
        }

        $redirect = admin_url('admin.php?page=' . $this->page_slug . '&view=detail&stylist_id=' . $stylist_id);
        wp_safe_redirect($redirect);
        exit;
    }
}

class ModaStylistsListTable extends \WP_List_Table {
    private $search = '';
    private $celebrity_id = 0;

    public function __construct() {
        parent::__construct([
            'singular' => 'stylist',
            'plural' => 'stylists',
            'ajax' => false,
        ]);
    }

    public function set_filters($search, $celebrity_id) {
        $this->search = $search;
        $this->celebrity_id = (int) $celebrity_id;
    }

    public function get_columns() {
        return [
            'full_name' => 'Full name',
            'email' => 'Email',
            'updated_at' => 'Updated',
        ];
    }

    public function get_sortable_columns() {
        return [
            'full_name' => ['full_name', false],
            'updated_at' => ['updated_at', true],
        ];
    }

    protected function column_full_name($item) {
        $url = admin_url('admin.php?page=moda-database&view=detail&stylist_id=' . $item['id']);
        return '<a href="' . esc_url($url) . '">' . esc_html($item['full_name']) . '</a>';
    }

    protected function column_default($item, $column_name) {
        if (isset($item[$column_name])) {
            return esc_html($item[$column_name]);
        }
        return '';
    }

    public function no_items() {
        echo 'No items found.';
    }

    public function prepare_items() {
        global $wpdb;

        $stylists_table = $wpdb->prefix . 'moda_stylists';
        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';

        $per_page = 20;
        $current_page = $this->get_pagenum();

        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];

        $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'updated_at';
        $order = isset($_GET['order']) ? strtoupper(sanitize_text_field(wp_unslash($_GET['order']))) : 'DESC';

        $sortable = $this->get_sortable_columns();
        if (!isset($sortable[$orderby])) {
            $orderby = 'updated_at';
        }
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';

        $where = '1=1';
        $join = '';

        if ($this->celebrity_id) {
            $join = " INNER JOIN $join_table sc ON sc.stylist_id = s.id";
            $where .= $wpdb->prepare(" AND sc.celebrity_id = %d", $this->celebrity_id);
        }

        if ($this->search) {
            $like = '%' . $wpdb->esc_like($this->search) . '%';
            $where .= $wpdb->prepare(" AND s.full_name LIKE %s", $like);
        }

        $count_sql = "SELECT COUNT(DISTINCT s.id) FROM $stylists_table s $join WHERE $where";
        $total_items = (int) $wpdb->get_var($count_sql);

        $offset = ($current_page - 1) * $per_page;
        $sql = "SELECT DISTINCT s.id, s.full_name, s.email, s.updated_at
                FROM $stylists_table s
                $join
                WHERE $where
                ORDER BY s.$orderby $order
                LIMIT %d OFFSET %d";
        $this->items = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset), ARRAY_A);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => (int) ceil($total_items / $per_page),
        ]);
    }
}
