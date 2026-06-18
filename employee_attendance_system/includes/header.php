<?php
require_once __DIR__ . '/auth.php';
if (is_logged_in()) { sync_daily_attendance($conn); }
$base = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : '';
$pageTitle = $pageTitle ?? 'Employee Attendance System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<div class="app">
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="main-content">
