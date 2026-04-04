<?php
header('Content-Type: application/json');

// Sanitize inputs
$name      = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
$email     = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$phone     = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
$company   = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_SPECIAL_CHARS);
$situation = filter_input(INPUT_POST, 'situation', FILTER_SANITIZE_SPECIAL_CHARS);
$message   = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);
$source    = filter_input(INPUT_POST, 'source', FILTER_SANITIZE_SPECIAL_CHARS);

// Basic validation
if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name and valid email are required.']);
    exit;
}

// Rate limiting via session
session_start();
$now = time();
if (isset($_SESSION['last_submit']) && ($now - $_SESSION['last_submit']) < 60) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Please wait before submitting again.']);
    exit;
}
$_SESSION['last_submit'] = $now;

// Build email
$to = 'himuraken@gmail.com, aib@junopi.com';

$sourceLabels = [
    'cybersecurity-guide'            => 'Cybersecurity Guide Download',
    'it-buyers-guide'                => 'IT Buyer\'s Guide Download',
    'cost-reduction'                 => 'Cost Reduction Inquiry',
    'disaster-recovery-template'     => 'DR Template Download',
    'it-provider-scorecard'          => 'IT Provider Scorecard Download',
    'm365-security-checklist'        => 'M365 Security Checklist Download',
    'onboarding-offboarding-checklist' => 'Onboarding/Offboarding Checklist Download',
    'm365-licensing-guide'           => 'M365 Licensing Guide Download',
    'vmware-exit-playbook'           => 'VMware Exit Playbook Download',
];
$sourceLabel = isset($sourceLabels[$source]) ? $sourceLabels[$source] : 'Consultation Request';
$subject = "[$sourceLabel] $name" . ($company ? " ($company)" : "");

$body = "New $sourceLabel from openexperts.tech\n";
$body .= "============================================\n\n";
$body .= "Source:    $sourceLabel\n";
$body .= "Name:      $name\n";
$body .= "Email:     $email\n";
$body .= "Phone:     $phone\n";
$body .= "Company:   $company\n";
$body .= "Situation: $situation\n";
$body .= "Message:   $message\n\n";
$body .= "Submitted: " . date('Y-m-d H:i:s T') . "\n";

$headers = "From: noreply@openexperts.tech\r\n";
$headers .= "Reply-To: $email\r\n";

// Send email
$sent = mail($to, $subject, $body, $headers, '-f noreply@openexperts.tech');

// Also log to file as backup
$logDir = __DIR__ . '/leads';
if (!is_dir($logDir)) {
    mkdir($logDir, 0750, true);
}
$logFile = $logDir . '/leads.csv';
$isNew = !file_exists($logFile);

$fp = fopen($logFile, 'a');
if ($isNew) {
    fputcsv($fp, ['timestamp', 'source', 'name', 'email', 'phone', 'company', 'situation', 'message']);
}
fputcsv($fp, [date('Y-m-d H:i:s'), $source, $name, $email, $phone, $company, $situation, $message]);
fclose($fp);

echo json_encode(['success' => true]);
