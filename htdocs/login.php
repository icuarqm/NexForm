<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Already logged in? Go home
if (isLoggedIn()) {
    header('Location: /');
    exit();
}

$pageTitle = 'NexForm - Login';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = isset($_POST['identity']) ? trim($_POST['identity']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($identity) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        try {
            $db = new Database();
            $pdo = $db->connect();

            // Check both username and email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$identity, $identity]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                loginUser($user);
                header('Location: /');
                exit();
            } else {
                $error = 'Invalid username/email or password.';
            }
        } catch (Exception $e) {
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div style="max-width: 450px; margin: 0 auto;">
    <h1>Welcome Back</h1>
    <p class="subtitle">Login to your NexForm account.</p>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="field">
            <label>Username or Email <span class="required">*</span></label>
            <input type="text" name="identity" value="<?php echo htmlspecialchars($identity ?? ''); ?>" required>
        </div>

        <div class="field">
            <label>Password <span class="required">*</span></label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <p style="margin-top: 20px; color: var(--text-muted);">
        Don't have an account? <a href="/register.php">Register here</a>
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>