<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexForm</title>
</head>
<body>
    <h1>NexForm</h1>
    <p>AI-powered form builder and analyst</p>

    <?php
    $db_host = getenv("MYSQL_HOST");
    $db_name = getenv("MYSQL_DATABASE");
    $db_pass = getenv("MYSQL_ROOT_PASSWORD");

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", "root", $db_pass);
        echo "<p>DB connected</p>";
    } catch (PDOException $e) {
        echo "<p>DB Error: " . $e->getMessage() . "</p>";
    }

    $ai_url = getenv("AI_SERVICE_URL") ?: "http://ai:5000";
    $health = @file_get_contents("$ai_url/health");
    if ($health) {
        echo "<p>AI service connected</p>";
    } else {
        echo "<p>AI service not available</p>";
    }
    ?>
</body>
</html>
