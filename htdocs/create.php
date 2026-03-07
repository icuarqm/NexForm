<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'config/ai.php';

requireLogin();

$pageTitle = 'NexForm - Create Form';
$user = currentUser();
$success = false;
$error = null;
$result = null;
$formId = null;

// Form ideas for random hints
$hints = [
    "Restaurant reservation form with date, time, guests and dietary needs",
    "Job application form with resume upload and experience details",
    "Customer feedback survey for a coffee shop",
    "Event registration form for a tech conference",
    "Hotel booking form with room type and special requests",
    "Student enrollment form for an online course",
    "Bug report form for a software product",
    "Wedding RSVP form with meal preferences",
    "Gym membership registration form",
    "Patient intake form for a dental clinic",
    "Volunteer sign-up form for a charity event",
    "Product return request form for an e-commerce store",
    "Employee onboarding form with emergency contacts",
    "Pet adoption application form",
    "Scholarship application form with essay questions",
    "Food delivery order form with customization options",
    "Apartment rental application form",
    "Workshop registration form with skill level selection",
    "Customer satisfaction survey for a bank",
    "Travel visa application form",
    "Freelancer project inquiry form",
    "School field trip permission form",
    "IT support ticket form with priority levels",
    "Membership renewal form for a club",
    "Contest entry form with terms acceptance",
    "Appointment booking form for a hair salon",
    "Course evaluation form for university students",
    "Donation form for a nonprofit organization",
    "Car rental booking form with insurance options",
    "Newsletter subscription form with topic preferences",
    "Recipe submission form for a cooking website",
    "Internship application form with availability",
    "Complaint form for a government service",
    "Music festival ticket order form",
    "Book club registration form with genre preferences",
    "Fitness class booking form with time slots",
    "Parent-teacher meeting scheduling form",
    "Real estate property inquiry form",
    "Photography session booking form",
    "Language course registration form with proficiency level",
    "Blood donation registration form",
    "Startup pitch submission form",
    "Library card application form",
    "Home repair service request form",
    "Birthday party RSVP form with theme selection",
    "Online exam registration form",
    "Catering order form with menu choices",
    "Coworking space membership form",
    "Yoga retreat registration form with accommodation preferences",
    "Film screening feedback form"
];

$randomHint = $hints[array_rand($hints)];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = isset($_POST['prompt']) ? trim($_POST['prompt']) : '';

    if (empty($prompt)) {
        $error = 'Please describe the form you want to create.';
    } else {
        try {
            $ai = new AIService();
            $result = $ai->generate($prompt);

            // Generate unique share token
            $shareToken = bin2hex(random_bytes(8));

            $db = new Database();
            $pdo = $db->connect();
            $stmt = $pdo->prepare("INSERT INTO forms (user_id, title, description, schema_json, prompt_used, share_token) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user['id'],
                $result['title'] ?? 'Untitled Form',
                $result['description'] ?? '',
                json_encode($result),
                $prompt,
                $shareToken
            ]);

            $formId = $pdo->lastInsertId();
            $success = true;
        } catch (Exception $e) {
            $error = 'Something went wrong: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<h1>Create a Form</h1>
<p class="subtitle">Describe what you need, AI will build it for you.</p>

<form method="POST">
    <textarea name="prompt" placeholder="<?php echo htmlspecialchars($randomHint); ?>"><?php echo isset($prompt) ? htmlspecialchars($prompt) : ''; ?></textarea>
    <button type="submit" class="btn">Generate Form</button>
</form>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success && $result): ?>
    <div class="card" style="margin-top: 20px;">
        <h2><?php echo htmlspecialchars($result['title'] ?? 'Untitled Form'); ?></h2>
        <p class="subtitle"><?php echo htmlspecialchars($result['description'] ?? ''); ?></p>

        <?php foreach ($result['fields'] ?? [] as $field): ?>
            <div class="field-item">
                <span class="field-label"><?php echo htmlspecialchars($field['label']); ?></span>
                <span class="field-type">(<?php echo htmlspecialchars($field['type']); ?>)</span>
                <?php if (!empty($field['required'])): ?>
                    <span class="field-required">*required</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <a class="btn" href="form.php?id=<?php echo $formId; ?>">View & Fill Form →</a>
        <a class="btn btn-secondary" href="analyze.php?id=<?php echo $formId; ?>">Analyze →</a>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>