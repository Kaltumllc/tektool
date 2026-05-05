<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';

header('Content-Type: application/json');

// For AJAX requests, return JSON error instead of redirecting
if (!is_logged_in()) {
    echo json_encode(['error' => 'Session expired. Please refresh the page and log in again.']);
    exit();
}

// Rate limiting — max 20 AI requests per user per hour
$user_id  = $_SESSION['user_id'];
$one_hour = date('Y-m-d H:i:s', strtotime('-1 hour'));
$rate     = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM audit_log
     WHERE user_id = $user_id
     AND action LIKE 'AI chat%'
     AND created_at > '$one_hour'"
));
if (($rate['c'] ?? 0) >= 20) {
    echo json_encode(['error' => 'Rate limit reached. Max 20 AI requests per hour.']);
    exit();
}

$data       = json_decode(file_get_contents('php://input'), true);
$message    = trim($data['message'] ?? '');
$context    = trim($data['context'] ?? '');
$request_id = (int)($data['request_id'] ?? 0);

if (empty($message)) {
    echo json_encode(['error' => 'Message is required.']);
    exit();
}

$system = "You are TekBot, an expert field technician assistant for C&W Services. 
You help junior and senior technicians solve real job site problems including:
- Electrical systems (LOTO, panels, wiring, motors)
- HVAC (cooling, heating, refrigeration, controls)
- Plumbing (pipes, valves, pumps, fixtures)
- Building automation and controls
- Safety procedures and compliance
- Mechanical systems and equipment

Always give clear, step-by-step, practical answers. 
Prioritize safety — always remind techs of safety precautions first.
Be concise but thorough. Use numbered steps when giving procedures.
If you are unsure, say so clearly and recommend escalating to a senior tech or engineer.";

if ($context) {
    $system .= "\n\nCurrent request context: $context";
}

if (!defined('ANTHROPIC_API_KEY') || !ANTHROPIC_API_KEY) {
    echo json_encode(['error' => 'TekBot is not configured. Contact your administrator.']);
    exit();
}

$payload = json_encode([
    'model'      => 'claude-haiku-4-5-20251001',
    'max_tokens' => 1024,
    'system'     => $system,
    'messages'   => [
        ['role' => 'user', 'content' => $message]
    ]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$err      = curl_error($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    echo json_encode(['error' => 'Connection error: ' . $err]);
    exit();
}

$result = json_decode($response, true);

if (isset($result['content'][0]['text'])) {
    $ai_response = $result['content'][0]['text'];

    // Log to audit
    $action = "AI chat used on request #$request_id by user #$user_id";
    $log = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
    if ($log) {
        mysqli_stmt_bind_param($log, 'is', $user_id, $action);
        mysqli_stmt_execute($log);
        mysqli_stmt_close($log);
    }

    // FIX: guard messages insert so failure doesn't kill the response
    if ($request_id) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO messages (request_id, sender_id, body, is_ai) VALUES (?, ?, ?, 1)"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iis', $request_id, $user_id, $ai_response);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Always return the reply regardless of logging success
    echo json_encode(['reply' => $ai_response]);
} else {
    $api_error = $result['error']['message'] ?? 'Unknown API error';
    $api_type  = $result['error']['type'] ?? '';
    echo json_encode([
        'error' => $api_error,
        'debug' => "HTTP $httpcode | Type: $api_type"
    ]);
}
?>