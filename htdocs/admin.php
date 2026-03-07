<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireAdmin();

$pageTitle = 'NexForm - Admin Panel';
$user = currentUser();
$message = null;

$db = new Database();
$pdo = $db->connect();

// Handle delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_user'])) {
            $deleteId = (int)$_POST['delete_user'];
            // Don't allow deleting yourself
            if ($deleteId !== $user['id']) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$deleteId]);
                $message = 'User deleted.';
            }
        }

        if (isset($_POST['delete_form'])) {
            $deleteId = (int)$_POST['delete_form'];
            $stmt = $pdo->prepare("DELETE FROM forms WHERE id = ?");
            $stmt->execute([$deleteId]);
            $message = 'Form deleted.';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

// Get stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalForms = $pdo->query("SELECT COUNT(*) FROM forms")->fetchColumn();
$totalResponses = $pdo->query("SELECT COUNT(*) FROM responses")->fetchColumn();
$totalAnalyses = $pdo->query("SELECT COUNT(*) FROM analysis_logs")->fetchColumn();

// Get all users
$users = $pdo->query("SELECT u.*, 
    (SELECT COUNT(*) FROM forms f WHERE f.user_id = u.id) as form_count 
    FROM users u ORDER BY u.created_at DESC")->fetchAll();

// Get all forms
$forms = $pdo->query("SELECT f.*, u.username,
    (SELECT COUNT(*) FROM responses r WHERE r.form_id = f.id) as response_count 
    FROM forms f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC")->fetchAll();

require_once 'includes/header.php';
?>

<h1>Admin Panel</h1>
<p class="subtitle">Manage users, forms and monitor activity.</p>

<?php if ($message): ?>
    <div class="success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <span class="stat-number"><?php echo $totalUsers; ?></span>
        <span class="stat-label">Users</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo $totalForms; ?></span>
        <span class="stat-label">Forms</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo $totalResponses; ?></span>
        <span class="stat-label">Responses</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo $totalAnalyses; ?></span>
        <span class="stat-label">Analyses</span>
    </div>
</div>

<!-- Users -->
<h2>Users</h2>
<div style="overflow-x: auto;">
    <table class="table">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Forms</th>
            <th>Joined</th>
            <th>Action</th>
        </tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo $u['is_admin'] ? 'Admin' : 'User'; ?></td>
                <td><?php echo $u['form_count']; ?></td>
                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                <td>
                    <?php if ($u['id'] !== $user['id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user and all their forms?');">
                            <button type="submit" name="delete_user" value="<?php echo $u['id']; ?>" class="btn btn-danger btn-small">Delete</button>
                        </form>
                    <?php else: ?>
                        <span class="card-meta">You</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Forms -->
<h2 style="margin-top: 40px;">All Forms</h2>
<div style="overflow-x: auto;">
    <table class="table">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Owner</th>
            <th>Responses</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($forms as $f): ?>
            <tr>
                <td><?php echo $f['id']; ?></td>
                <td><?php echo htmlspecialchars($f['title']); ?></td>
                <td><?php echo htmlspecialchars($f['username']); ?></td>
                <td><?php echo $f['response_count']; ?></td>
                <td><?php echo date('M d, Y', strtotime($f['created_at'])); ?></td>
                <td>
                    <a href="/form.php?id=<?php echo $f['id']; ?>" class="btn btn-small">View</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this form and all its responses?');">
                        <button type="submit" name="delete_form" value="<?php echo $f['id']; ?>" class="btn btn-danger btn-small">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>