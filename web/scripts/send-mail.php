<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// 1. Locate and Load Secrets
$possiblePaths = [
    __DIR__ . '/../smtp_d.php',
    __DIR__ . '/../../smtp_d.php',
];

$configPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $configPath = $path;
        break;
    }
}

if (!$configPath) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Config file missing! Check smtp_d.php path.']);
    exit;
}
$config = require $configPath;

// 2. Extract and Validate Input
$name    = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT) ?? '');
$email   = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? '');
$message = trim(filter_input(INPUT_POST, 'message', FILTER_DEFAULT) ?? '');

if (empty($name) || !$email || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid name, email, and message.']);
    exit;
}

$safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// 3. Save Submission into cPanel MySQL Database
try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare("INSERT INTO contact_submissions (name, email, message) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $message]);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    exit;
}

// 4. Dispatch Emails via PHPMailer
$vendorPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($vendorPath)) {
    // If vendor isn't local, inquiry is still saved in DB successfully
    echo json_encode(['success' => true, 'message' => 'Query saved to database successfully!']);
    exit;
}
require $vendorPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->CharSet   = 'UTF-8';
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $config['smtp_port'];

    // Notification Email to Internal Team
    $mail->setFrom($config['smtp_user'], 'Website Contact System');
    $mail->addAddress($config['to_email']);
    $mail->addReplyTo($email, $safeName);

    $mail->isHTML(true);
    $mail->Subject = "New Website Query from " . $safeName;
    $mail->Body    = "<strong>New Inquiry Saved to DB:</strong><br><br>" .
                     "<strong>Name:</strong> {$safeName}<br>" .
                     "<strong>Email:</strong> {$email}<br>" .
                     "<strong>Message:</strong><br>" . nl2br($safeMessage);
    $mail->send();

    // Confirmation Receipt to Client
    $mail->clearAddresses();
    $mail->clearReplyTos();
    $mail->addAddress($email, $safeName);
    $mail->setFrom($config['smtp_user'], 'GG Prohn Support');

    $mail->Subject = "We have received your query - GG Prohn";
    $mail->Body    = "Hello {$safeName},<br><br>" .
                     "Thank you for contacting GG Prohn. We have received your query and will get back to you shortly.<br><br>" .
                     "Kind regards,<br><strong>GG Prohn Team</strong>";
    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Your query has been submitted! A confirmation email was sent.']);
} catch (Exception $e) {
    // DB record exists even if SMTP fails
    echo json_encode(['success' => true, 'message' => 'Your query has been logged. We will contact you shortly.']);
}