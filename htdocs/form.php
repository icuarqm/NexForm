<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$error = null;
$form = null;
$submitted = false;
$isOwner = false;

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($formId <= 0) {
    $error = 'Invalid form ID.';
} else {
    try {
        $db = new Database();
        $pdo = $db->connect();

        $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
        $stmt->execute([$formId]);
        $form = $stmt->fetch();

        if (!$form) {
            $error = 'Form not found.';
        } else {
            // Check if current user is the form owner
            $user = currentUser();
            $isOwner = $user && $user['id'] == $form['user_id'];
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $form) {
    try {
        $db = new Database();
        $pdo = $db->connect();

        $stmt = $pdo->prepare("INSERT INTO responses (form_id, response_data) VALUES (?, ?)");
        $stmt->execute([$formId, json_encode($_POST)]);

        $submitted = true;
    } catch (Exception $e) {
        $error = 'Failed to save response: ' . $e->getMessage();
    }
}

$schema = $form ? json_decode($form['schema_json'], true) : null;
$pageTitle = $schema ? htmlspecialchars($schema['title']) : 'NexForm';

require_once 'includes/header.php';
?>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>

<?php elseif ($submitted): ?>
    <div class="success">
        <h2>Response submitted!</h2>
        <p>Thank you for filling out this form.</p>
        <a href="/" class="btn" style="margin-top: 15px;">← Back to Home</a>
    </div>

<?php elseif ($schema): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <h1><?php echo htmlspecialchars($schema['title']); ?></h1>
        <?php if ($isOwner): ?>
            <a href="/analyze.php?id=<?php echo $formId; ?>" class="btn btn-secondary btn-small">Analyze Form</a>
        <?php endif; ?>
    </div>
    <p class="subtitle"><?php echo htmlspecialchars($schema['description'] ?? ''); ?></p>

    <form method="POST">
        <?php foreach ($schema['fields'] ?? [] as $i => $field): ?>
            <div class="field">
                <label>
                    <?php echo htmlspecialchars($field['label']); ?>
                    <?php if (!empty($field['required'])): ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>

                <?php
                $name = "field_" . $i;
                $req = !empty($field['required']) ? 'required' : '';
                $type = $field['type'] ?? 'text';

                switch ($type):
                    case 'textarea': ?>
                        <textarea name="<?php echo $name; ?>" <?php echo $req; ?>></textarea>
                    <?php break;

                    case 'select': ?>
                        <select name="<?php echo $name; ?>" <?php echo $req; ?>>
                            <option value="">Select...</option>
                            <?php foreach ($field['options'] ?? [] as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php break;

                    case 'checkbox':
                        if (!empty($field['options'])): ?>
                            <div class="checkbox-group">
                                <?php foreach ($field['options'] as $opt): ?>
                                    <label>
                                        <input type="checkbox" name="<?php echo $name; ?>[]" value="<?php echo htmlspecialchars($opt); ?>">
                                        <?php echo htmlspecialchars($opt); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <label>
                                <input type="checkbox" name="<?php echo $name; ?>" value="yes" <?php echo $req; ?>>
                                <?php echo htmlspecialchars($field['label']); ?>
                            </label>
                        <?php endif;
                    break;

                    case 'radio': ?>
                        <div class="radio-group">
                            <?php foreach ($field['options'] ?? [] as $opt): ?>
                                <label>
                                    <input type="radio" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars($opt); ?>" <?php echo $req; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php break;

                    default: ?>
                        <input type="<?php echo htmlspecialchars($type); ?>" name="<?php echo $name; ?>" <?php echo $req; ?>>
                <?php endswitch; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn">Submit Response</button>
    </form>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>