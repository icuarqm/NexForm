<?php
session_start();

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if not logged in
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

/**
 * Check if current user is admin
 */
function isAdmin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Redirect to home if not admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /');
        exit();
    }
}

/**
 * Get current user info from session
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'is_admin' => $_SESSION['is_admin']
    ];
}

/**
 * Set session after successful login
 */
function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_admin'] = (bool)$user['is_admin'];
}

/**
 * Clear session on logout
 */
function logoutUser(): void {
    session_destroy();
}