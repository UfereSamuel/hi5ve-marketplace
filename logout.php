<?php
require_once 'config/config.php';
require_once 'classes/User.php';

// Logout user
User::logout();

// Redirect to home page with logout message
redirectTo('index.php?logout=1');
?> 