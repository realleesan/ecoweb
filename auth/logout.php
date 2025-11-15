<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/auth.php';

// Logout user
logoutUser();

// Redirect to home page
header('Location: ' . BASE_URL . '/index.php');
exit;

