<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'config/ai.php';

requireLogin();

$error = null;
$answer = null;
$toolsUsed = null;
$form = null;
$responses = [];
$user = currentUser();

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($formId <= 0) {
    $error = 'Invalid form ID.';
} else {
    try {
        $db = new Database();
        $pdo = $db->connect();

        // Only allow form owner to analyze
        $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ? AND user_id = ?");
        $stmt->execute([$formId, $user['id']]);
        $form = $stmt->fetch();

        if (!$form) {
            $error = 'Form not found or access denied.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM responses WHERE form_id = ? ORDER BY submitted_at DESC");
            $stmt->execute([$formId]);
            $responses = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $form) {
    $question = isset($_POST['question']) ? trim($_POST['question']) : '';

    if (empty($question)) {
        $error = 'Please enter a question.';
    } else {
        try {
            $ai = new AIService();
            $result = $ai->analyze($formId, $question);
            $answer = $result['answer'] ?? 'No answer received.';
            $toolsUsed = $result['tools_used'] ?? [];
        } catch (Exception $e) {
            $error = 'Analysis failed: ' . $e->getMessage();
        }
    }
}

$schema = $form ? json_decode($form['schema_json'], true) : null;
$fields = $schema['fields'] ?? [];
$fieldMap = [];
foreach ($fields as $i => $f) {
    $fieldMap["field_" . $i] = $f['label'];
}

$pageTitle = 'Analyze - ' . ($schema ? htmlspecialchars($schema['title']) : 'NexForm');

require_once 'includes/header.php';
?>

<?php if ($error && !$form): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>

<?php elseif ($form && $schema): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <h1><?php echo htmlspecialchars($schema['title']); ?></h1>
        <a href="/form.php?id=<?php echo $formId; ?>" class="btn btn-small">Go to Form</a>
    </div>
    <p class="subtitle"><?php echo htmlspecialchars($schema['description'] ?? ''); ?></p>

    <p class="stats"><?php echo count($responses); ?> response(s) received</p>

    <form method="POST">
        <textarea name="question" placeholder="Ask a question about the responses... e.g. What are the most common answers?"><?php echo isset($question) ? htmlspecialchars($question) : ''; ?></textarea>
        <button type="submit" class="btn btn-secondary">Analyze</button>
    </form>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($answer): ?>
        <div class="answer">
            <h2>AI Analysis</h2>
            <p><?php echo htmlspecialchars($answer); ?></p>
            <?php if ($toolsUsed): ?>
                <p class="tools">Tools used: <?php echo htmlspecialchars(implode(', ', $toolsUsed)); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($responses)): ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <tr>
                    <th>#</th>
                    <?php foreach ($fields as $f): ?>
                        <th><?php echo htmlspecialchars($f['label']); ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php foreach ($responses as $idx => $r): ?>
                    <?php $data = json_decode($r['response_data'], true); ?>
                    <tr>
                        <td><?php echo $idx + 1; ?></td>
                        <?php foreach ($fields as $i => $f): ?>
                            <td>
                                <?php
                                $val = $data["field_" . $i] ?? '-';
                                if (is_array($val)) $val = implode(', ', $val);
                                echo htmlspecialchars($val);
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>