<?php

error_reporting(E_ALL);

// Do not expose PHP errors to visitors.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Keep error logging enabled.
ini_set('log_errors', '1');

define('DB_PATH', __DIR__ . '/../database/forum.sqlite');
define('UPLOAD_DIR', __DIR__ . '/../public/uploads');
define('APP_NAME', 'DevTalk Forum');
