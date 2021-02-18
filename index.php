<?php

require_once 'vendors/ffcore.php';

// jibber gabber
ini_set('memory_limit', '2048M');
ini_set('upload_max_filesize', '1024M');
ini_set('max_input_time', '1000');
ini_set('post_max_size', '2000M');
set_time_limit(0);


// Run setup
ff_setup([
	'limit_ip' => [
		/* restrict access to particular IP addresses here */
	],
    'allowed_domains' => [
		/* your top-level domains here */
    ]
]);

// Execute
ff_exec();

