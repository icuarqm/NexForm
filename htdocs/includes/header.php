<?php
require_once __DIR__ . '/auth.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'NexForm'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<nav class="nav">
    <div class="nav-left">
        <a href="/"><img src="/assets/logo.svg" alt="NexForm" class="nav-logo"></a>
        <a href="/" class="nav-brand">NexForm</a>
    </div>

    <div class="nav-links">
        <?php if (isLoggedIn()): ?>
            <a href="/" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">Home</a>
            <a href="/create.php" class="nav-link <?php echo $currentPage === 'create' ? 'active' : ''; ?>">Create Form</a>
            <a href="/forms.php" class="nav-link <?php echo $currentPage === 'forms' ? 'active' : ''; ?>">My Forms</a>
            <?php if (isAdmin()): ?>
                <a href="/admin.php" class="nav-link <?php echo $currentPage === 'admin' ? 'active' : ''; ?>">Admin</a>
            <?php endif; ?>
            <a href="/logout.php" class="nav-link">Logout</a>
        <?php else: ?>
            <a href="/login.php" class="nav-link <?php echo $currentPage === 'login' ? 'active' : ''; ?>">Login</a>
            <a href="/register.php" class="nav-link <?php echo $currentPage === 'register' ? 'active' : ''; ?>">Register</a>
        <?php endif; ?>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
</nav>

<div class="page">