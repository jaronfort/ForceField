<?php
namespace Hologram\Relations;

use ForceField\Core\Configure;

abstract class Model
{

    private $db;

    protected $table = ''; // Must be overriden

    protected $primary_key = 'id';

    private $row;

    public function __construct()
    {
    }

    public function __destruct()
    {
        $this->db = null;
    }
    
    private function initDb()
    {
        $this->db = Model::getDb();
        
        if ($this->primary_key != 'id')
            $this->db->setPrimary($this->table, $this->primary_key);
    }

    protected function map(\LessQL\Row $row, array $map)
    {
        foreach ($map as $field => $type) {
            if (isset($row->$field)) {
                // Convert
                switch ($type) {
                    case 'boolean':
                        $row->$field = $row->$field == '1' || $row->$field == 1 ? true : false;
                        break;
                    case 'int':
                        $row->$field = (int) $row->$field;
                        break;
                    case 'float':
                        $row->$field = (float) $row->$field;
                        break;
                    case 'double':
                        $row->$field = (double) $row->$field;
                        break;
                    case 'string':
                        if ($row->$field == null)
                            continue;
                        $row->$field = (string) $row->$field;
                        break;
                    case 'unix':
                        if (! is_string($row->$field))
                            continue; // Skip
                        if (is_string($format))
                            $row->$field = date($format, strtotime($row->$field));
                        else
                            $row->$field = strtotime($row->$field);
                        break;
                    default:
                        throw new \Exception("The value ,\"{$type}\", is not a supported conversion type.");
                }
            }
        }
    }

    protected function mapAll(array $rows, array $map)
    {
        $a = [];
        
        foreach ($rows as $row) {
            $a[] = $this->map($row, $map);
        }
        
        return $a;
    }

    protected function table()
    {
        if(!$this->db)
            $this->initDb();
        
        $table = $this->table;
        return $table ? $this->db->$table() : null;
    }

    public static function getDb()
    {
        $driver = Configure::readString('database.driver', 'mysql');
        $host = Configure::readString("database.{$driver}.host", 'localhost');
        $name = Configure::readString("database.{$driver}.name", 'test');
        $user = Configure::readString("database.{$driver}.user", 'root');
        $pass = Configure::readString("database.{$driver}.pass", '');
        $charset = Configure::readString("database.{$driver}.charset", 'utf8mb4');
        $pdo = new \PDO("{$driver}:host={$host};dbname={$name};charset={$charset}", $user, $pass);
        return new \LessQL\Database($pdo);
    }
    
    public function row(array $data = null) {
        return $this->table()->createRow($data != null ? $data : []);
    }

    public function __get($property)
    {
        if($property == 'db') {
            
            if(!$this->db)
                $this->initDb();
            
            return $this->db;
        }
        
        return $this->row ? $this->row->$property : null;
    }
    
    public function __call($method, $args)
    {
        if ($table = $this->table()) {
            
            return call_user_func_array([
                $table,
                $method
            ], $args);
        
        }
        
        throw new \Exception("Cannot call method '${method}' on model when table is not defined.");
    }
}

