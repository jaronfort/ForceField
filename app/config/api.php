<?php

return [
    'json.pretty' => ff_config('app.mode', 'development') == 'development',
    'errors.codes' => [
        'user_auth_error' => 2352,
        'anonymous_user_error' => 3593,
        'user_conflict_error' => 5356,
        'illegal_access_error' => 3918,
        'validation_error' => 1024
    ]
];