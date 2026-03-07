<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'NexForm - Home';
$user = currentUser();

// Get stats for this user
$totalForms = 0;
$totalResponses = 0;

try {
    $db = new Database();
    $pdo = $db->connect();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM forms WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $totalForms = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM responses r JOIN forms f ON r.form_id = f.id WHERE f.user_id = ?");
    $stmt->execute([$user['id']]);
    $totalResponses = $stmt->fetchColumn();
} catch (Exception $e) {
    // Silently fail, show 0
}

require_once 'includes/header.php';
?>

<h1 class="welcome">Hello, <?php echo htmlspecialchars($user['username']); ?></h1>
<p class="welcome-sub">Welcome to NexForm — create forms with AI, analyze responses instantly.</p>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-number"><?php echo $totalForms; ?></span>
        <span class="stat-label">Forms Created</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo $totalResponses; ?></span>
        <span class="stat-label">Total Submissions</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo $totalForms + $totalResponses; ?></span>
        <span class="stat-label">Total Activity</span>
    </div>
</div>

<div class="actions-grid">
    <a href="/create.php" class="action-card">✨ Create New Form</a>
    <a href="/forms.php" class="action-card">📋 View My Forms</a>
</div>

<?php require_once 'includes/footer.php'; ?>