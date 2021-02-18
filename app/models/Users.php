<?php
namespace App\Models;

use Hologram\Relations\Model;

class Users extends Model
{
    
    protected $table = 'users';
    
    public function get($id, array $fields = null)
    {
        $result = null;
        
        if (filter_var($id, FILTER_VALIDATE_EMAIL))
            $result = $this->db->users()
                ->where('email', $id)
                ->limit(1);
        else if (is_numeric($id))
            $result = $this->db->users()
                ->where('id', (int) $id)
                ->limit(1);
        else if (preg_match('/^@?[a-zA-Z][a-zA-Z0-9_-]+$/', $id)) {
            $id = trim(strtolower($id), '@');
            $result = $this->db->profiles()
                ->where("LOWER(profiles.hydraateID) = '" . $id . "'")
                ->limit(1)
                ->users()
                ->via('owner');
        }
        
        if ($result) {
            
            if($fields)
            {
                foreach($fields as $f)
                {
                    $result = $result->select($f);
                }
            }
            
            foreach ($result as $row) {
                return $row;
            }
        }
        
        return null;
    }

    public function insert($user)
    {
        $row = $this->db->users()->insert($user);
    }

    public function suspend($id)
    {
        $user = $this->get($id);
        
        if ($user) {
            $user->status = 'suspended';
            $user->save();
            return true;
        }
        
        return false;
    }
}

