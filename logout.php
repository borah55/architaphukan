<?php
require_once __DIR__ . '/includes/init.php';
auth_logout();
flash_set('info', 'You have been logged out.');
redirect('login.php');
