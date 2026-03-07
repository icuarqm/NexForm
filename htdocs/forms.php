<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$pageTitle = 'NexForm - My Forms';
$user = currentUser();
$forms = [];

try {
    $db = new Database();
    $pdo = $db->connect();

    $stmt = $pdo->prepare("SELECT f.*, 
        (SELECT COUNT(*) FROM responses r WHERE r.form_id = f.id) as response_count 
        FROM forms f WHERE f.user_id = ? ORDER BY f.created_at DESC");
    $stmt->execute([$user['id']]);
    $forms = $stmt->fetchAll();
} catch (Exception $e) {
    // Silently fail
}

require_once 'includes/header.php';
?>

<h1>My Forms</h1>
<p class="subtitle">All forms you've created with AI.</p>

<?php if (empty($forms)): ?>
    <div class="card" style="text-align: center;">
        <p>No forms yet.</p>
        <a href="/create.php" class="btn" style="margin-top: 15px;">✨ Create Your First Form</a>
    </div>
<?php else: ?>
    <?php foreach ($forms as $form): ?>
        <div class="card">
            <div class="card-header">
                <div>
                    <span class="card-title"><?php echo htmlspecialchars($form['title']); ?></span>
                    <p class="card-meta"><?php echo $form['response_count']; ?> response(s) · Created <?php echo date('M d, Y', strtotime($form['created_at'])); ?></p>
                </div>
                <div class="card-actions">
                    <a href="/form.php?id=<?php echo $form['id']; ?>" class="btn btn-small">Go to Form</a>
                    <a href="/analyze.php?id=<?php echo $form['id']; ?>" class="btn btn-small btn-secondary">Analyze</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>