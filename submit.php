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

// Bot detection
$honeypot = filter_input(INPUT_POST, 'website', FILTER_SANITIZE_SPECIAL_CHARS);
$formToken = filter_input(INPUT_POST, '_token', FILTER_SANITIZE_SPECIAL_CHARS);
$loadTime  = filter_input(INPUT_POST, '_loaded', FILTER_SANITIZE_SPECIAL_CHARS);

// Honeypot: hidden field that should be empty
if (!empty($honeypot)) {
    echo json_encode(['success' => true]); // fake success so bots don't retry
    exit;
}

// JS token: must match expected value (bots without JS won't have it)
if ($formToken !== 'oe-human-2026') {
    echo json_encode(['success' => true]);
    exit;
}

// Time check: reject if submitted faster than 3 seconds
if (!empty($loadTime) && (time() - intval($loadTime)) < 3) {
    echo json_encode(['success' => true]);
    exit;
}

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

// Send notification to business owner
$sent = mail($to, $subject, $body, $headers, '-f noreply@openexperts.tech');

// Send auto-reply with guide link to the requester
$guideLinks = [
    'cybersecurity-guide'              => ['Cybersecurity Essentials for Business Owners', 'https://openexperts.tech/guide-cybersecurity-essentials.html'],
    'it-buyers-guide'                  => ['Small Business IT Buyer\'s Guide', 'https://openexperts.tech/guide-it-buyers-guide.html'],
    'disaster-recovery-template'       => ['Small Business Disaster Recovery Template', 'https://openexperts.tech/guide-disaster-recovery.html'],
    'it-provider-scorecard'            => ['IT Provider Scorecard', 'https://openexperts.tech/guide-it-provider-scorecard.html'],
    'm365-security-checklist'          => ['Microsoft 365 Security Hardening Checklist', 'https://openexperts.tech/guide-m365-security.html'],
    'onboarding-offboarding-checklist' => ['Employee Onboarding & Offboarding IT Checklist', 'https://openexperts.tech/guide-onboarding-offboarding.html'],
    'm365-licensing-guide'             => ['Microsoft 365 Licensing Cheat Sheet', 'https://openexperts.tech/guide-m365-licensing.html'],
    'vmware-exit-playbook'             => ['The VMware Exit Playbook', 'https://openexperts.tech/guide-vmware-exit.html'],
];

if (!empty($source) && isset($guideLinks[$source])) {
    $guideName = $guideLinks[$source][0];
    $guideUrl  = $guideLinks[$source][1];
    $firstName = explode(' ', trim($name))[0];

    $replySubject = "Your Free Guide: $guideName";
    $replyBody  = "Hi $firstName,\n\n";
    $replyBody .= "Thanks for requesting the $guideName from Open Experts. Here's your link:\n\n";
    $replyBody .= "$guideUrl\n\n";
    $replyBody .= "Bookmark it, share it with your team, or print it out -- it's yours to keep.\n\n";
    $replyBody .= "If you have any questions about what's in the guide, or if you'd like to talk through how it applies to your business, just reply to this email. You'll reach me directly -- no sales team, no runaround.\n\n";
    $replyBody .= "- Alan Bildzukewicz\n";
    $replyBody .= "  Founder, Open Experts\n";
    $replyBody .= "  https://openexperts.tech\n";

    $replyHeaders  = "From: Alan Bildzukewicz <aib@junopi.com>\r\n";
    $replyHeaders .= "Reply-To: aib@junopi.com\r\n";

    mail($email, $replySubject, $replyBody, $replyHeaders, '-f noreply@openexperts.tech');
}

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
