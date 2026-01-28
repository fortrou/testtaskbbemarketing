<?php
namespace Moda\DB\Abstracts;

abstract class ModaDB {
    public $data = [];
    private $table;

    protected static $instance = null;

    protected function __construct() {}

    public static function instance() {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    abstract public function get_item($id);
    abstract public function save_item($data);
    abstract public function delete_item($id);
    abstract protected function run_delta();
    public function get_list($filters) {
        return [];
    }
}