<?php
namespace Moda;

class ModaCli {
    public static function register() {
        \WP_CLI::add_command('moda seed', [self::class, 'seed']);
    }

    public static function seed($args, $assoc_args) {
        global $wpdb;

        $stylists_count = isset($assoc_args['stylists']) ? (int) $assoc_args['stylists'] : 0;
        $celebs_count = isset($assoc_args['celebs']) ? (int) $assoc_args['celebs'] : 0;
        $links_count = isset($assoc_args['links']) ? (int) $assoc_args['links'] : 0;
        $reps_count = isset($assoc_args['reps']) ? (int) $assoc_args['reps'] : 0;

        if ($stylists_count <= 0 || $celebs_count <= 0 || $links_count <= 0) {
            \WP_CLI::error('Usage: wp moda seed --stylists=2000 --celebs=5000 --links=30000 [--reps=1000]');
        }

        $stylists_table = $wpdb->prefix . 'moda_stylists';
        $celebs_table = $wpdb->prefix . 'moda_celebrities';
        $join_table = $wpdb->prefix . 'moda_stylist_celebrity';
        $reps_table = $wpdb->prefix . 'moda_stylist_reps';

        $first_names = ['Alex', 'Jamie', 'Taylor', 'Jordan', 'Casey', 'Morgan', 'Riley', 'Avery', 'Parker', 'Reese', 'Cameron', 'Drew', 'Quinn', 'Hayden', 'Dakota', 'Skyler', 'Rowan', 'Emerson', 'Finley', 'Sawyer'];
        $last_names = ['Smith', 'Johnson', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia', 'Martinez', 'Robinson', 'Clark', 'Rodriguez', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen', 'Young', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores'];
        $industries = ['Music', 'Film/TV', 'Sports', 'Fashion', 'Digital', 'Theatre', 'Art', 'Literature', 'Gaming', 'Business', 'Politics', 'Science', 'Technology', 'Philanthropy'];
        $territories = ['US', 'EU', 'Global', 'UK', 'Portugal', 'Canada', 'Australia', 'Asia', 'Latin America'];
        $companies = ['Atlas Talent', 'North Star', 'Lumen Group', 'Harbor Mgmt', 'Nova Agency', 'Summit Reps', 'Pinnacle Partners', 'Zenith Talent', 'Vertex Agency', 'Horizon Reps', 'Apex Talent', 'Vanguard Agency'];

        $wpdb->query('START TRANSACTION');

        self::truncate_tables([$join_table, $reps_table, $stylists_table, $celebs_table]);

        self::insert_stylists($wpdb, $stylists_table, $stylists_count, $first_names, $last_names);
        self::insert_celebs($wpdb, $celebs_table, $celebs_count, $first_names, $last_names, $industries);

        $stylist_ids = $wpdb->get_col("SELECT id FROM $stylists_table");
        $celeb_ids = $wpdb->get_col("SELECT id FROM $celebs_table");

        self::insert_links($wpdb, $join_table, $links_count, $stylist_ids, $celeb_ids);

        if ($reps_count <= 0) {
            $reps_count = (int) max(1, floor($stylists_count / 3));
        }

        self::insert_reps($wpdb, $reps_table, $reps_count, $stylist_ids, $companies, $territories);

        $wpdb->query('COMMIT');

        \WP_CLI::success("Seeded: stylists=$stylists_count, celebs=$celebs_count, links=$links_count, reps=$reps_count");
    }

    private static function truncate_tables(array $tables) {
        global $wpdb;
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE $table");
        }
    }

    private static function insert_stylists($wpdb, $table, $count, $first_names, $last_names) {
        for ($i = 0; $i < $count; $i++) {
            $name = self::rand_name($first_names, $last_names);
            $email = strtolower(str_replace(' ', '.', $name)) . $i . '@example.com';
            $wpdb->insert($table, [
                'full_name' => $name,
                'email' => $email,
                'phone' => '+1-555-' . str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'instagram' => '@' . strtolower(str_replace(' ', '', $name)),
                'website' => 'https://example.com/' . strtolower(str_replace(' ', '-', $name)),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);
        }
    }

    private static function insert_celebs($wpdb, $table, $count, $first_names, $last_names, $industries) {
        for ($i = 0; $i < $count; $i++) {
            $name = self::rand_name($first_names, $last_names);
            $wpdb->insert($table, [
                'full_name' => $name,
                'industry' => $industries[array_rand($industries)],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);
        }
    }

    private static function insert_links($wpdb, $table, $count, $stylist_ids, $celeb_ids) {
        $stylist_total = count($stylist_ids);
        $celeb_total = count($celeb_ids);
        if (!$stylist_total || !$celeb_total) {
            return;
        }
        for ($i = 0; $i < $count; $i++) {
            $stylist_id = $stylist_ids[rand(0, $stylist_total - 1)];
            $celeb_id = $celeb_ids[rand(0, $celeb_total - 1)];
            $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO $table (stylist_id, celebrity_id, notes) VALUES (%d, %d, %s)",
                $stylist_id,
                $celeb_id,
                'Seed link'
            ));
        }
    }

    private static function insert_reps($wpdb, $table, $count, $stylist_ids, $companies, $territories) {
        $stylist_total = count($stylist_ids);
        if (!$stylist_total) {
            return;
        }
        for ($i = 0; $i < $count; $i++) {
            $stylist_id = $stylist_ids[rand(0, $stylist_total - 1)];
            $rep_name = 'Rep ' . ($i + 1);
            $wpdb->insert($table, [
                'stylist_id' => $stylist_id,
                'rep_name' => $rep_name,
                'company' => $companies[array_rand($companies)],
                'rep_email' => 'rep' . ($i + 1) . '@example.com',
                'rep_phone' => '+1-555-' . str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'territory' => $territories[array_rand($territories)],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);
        }
    }

    private static function rand_name($first_names, $last_names) {
        return $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)];
    }
}
