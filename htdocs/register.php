<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Already logged in? Go home
if (isLoggedIn()) {
    header('Location: /');
    exit();
}

$pageTitle = 'NexForm - Register';
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $adminKey = isset($_POST['admin_key']) ? trim($_POST['admin_key']) : '';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db = new Database();
            $pdo = $db->connect();

            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error = 'Username or email already taken.';
            } else {
                // Check admin key
                $isAdmin = false;
                if (!empty($adminKey)) {
                    $serverKey = getenv('ADMIN_SECRET_KEY');
                    if ($adminKey === $serverKey) {
                        $isAdmin = true;
                    } else {
                        $error = 'Invalid admin key.';
                    }
                }

                if (!$error) {
                    // Hash password and insert user
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hash, $isAdmin ? 1 : 0]);

                    // Auto login after register
                    $userId = $pdo->lastInsertId();
                    loginUser([
                        'id' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'is_admin' => $isAdmin
                    ]);

                    header('Location: /');
                    exit();
                }
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div style="max-width: 450px; margin: 0 auto;">
    <h1>Create Account</h1>
    <p class="subtitle">Join NexForm and start building forms with AI.</p>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="field">
            <label>Username <span class="required">*</span></label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
        </div>

        <div class="field">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
        </div>

        <div class="field">
            <label>Password <span class="required">*</span></label>
            <input type="password" name="password" required>
        </div>

        <div class="field">
            <label>Confirm Password <span class="required">*</span></label>
            <input type="password" name="confirm_password" required>
        </div>

        <div class="field">
            <label>Admin Key <span style="color: var(--text-muted); font-weight: 400;">(optional)</span></label>
            <input type="text" name="admin_key" placeholder="Leave empty for regular account">
        </div>

        <button type="submit" class="btn">Register</button>
    </form>

    <p style="margin-top: 20px; color: var(--text-muted);">
        Already have an account? <a href="/login.php">Login here</a>
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>