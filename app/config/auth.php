<?php

return [
    'users' => [
        'table' => 'users',
        'idField' => 'id',
        'fields' => [
            'id' => 'id',
            'auth' => 'email',
            'email' => 'email',
            'password' => 'password'
        ],
        'custom' => [
            // must be a function(\LessQL\Database $db, $user)
            'loadUser' => function (\LessQL\Database $db, $user) {
                $sql = null;
                
                if (preg_match('/^@?[a-zA-Z0-9][a-zA-Z0-9_]+$/', $user)) {
                    // loads a user by username where usernames all start with '@'
                    $user = str_replace('@', '', strtolower($user)); // Remove optional @ and make case-insensitive
                    $sql = "SELECT users.* FROM users WHERE users.id IN (SELECT profiles.owner FROM profiles WHERE LOWER(profiles.hydraateID) = '{$user}' AND profiles.owner = users.id) LIMIT 1;";
                } else if (filter_var($user, FILTER_VALIDATE_EMAIL)) {
                    // Search by email
                    $sql = "SELECT users.* FROM users WHERE users.email = '{$user}' LIMIT 1;";
                }
                
                if ($sql) {
                    $result = $db->query($sql);
                    
                    foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                        return $row;
                    }
                }
                
                return null;
            }
        ],
        'select' => [
            'id',
            'firstName',
            'middleName',
            'lastName',
            'email',
            'status'
        ]
    ],
    'sessions' => [
    ]
];