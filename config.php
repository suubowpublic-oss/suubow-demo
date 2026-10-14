<?php
declare(strict_types=1);
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'suubow_documents';
const DB_USER = 'root';
const DB_PASS = '';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check config.php and database.sql.');
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
function require_admin(): void { require_login(); if (($_SESSION['role'] ?? 'staff') !== 'admin') { http_response_code(403); exit('Ma lihid oggolaansho aad boggan ku gasho.'); } }
function owner_filter(string $alias = ''): array { $prefix = $alias !== '' ? $alias . '.' : ''; return (($_SESSION['role'] ?? 'staff') === 'admin') ? ['', []] : [' AND ' . $prefix . 'uploaded_by = ?', [(int)$_SESSION['user_id']]]; }
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf_token'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            http_response_code(419);
            exit('Session token expired. Please go back and try again.');
        }
    }
}
function categories(): array {
    return [
        'Kala gadasho dhul', 'Kala gadasho beer', 'Kala gadasho gaari/baabuur',
        'Wakaalad gaar ah', 'Wakaalad guud', 'Heshiis laba dhinac ah',
        'Kiro goob ganacsi', 'Kiro beer', 'Kiro guri deegaan', 'Xeer hoosaad',
        'Caddeyn Damaanad', 'Caddeyn Hibo Dhul Deegaan / Beer'
    ];
}
