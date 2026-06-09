<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vendor/settings.php");
    exit();
}

if (!isset($_SESSION['vendor_id'])) {
    header("Location: ../vendor/index.php");
    exit();
}

include 'connect.php';

$vendor_id = (int) $_SESSION['vendor_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($current_password === '' || $new_password !== $confirm_password || strlen($new_password) < 8) {
    header("Location: ../vendor/settings.php?notice=password-error");
    exit();
}

$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM vendors LIKE 'password_changed_at'");
if ($columnCheck && mysqli_num_rows($columnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE vendors ADD COLUMN password_changed_at DATETIME NULL");
}

$stmt = $conn->prepare("SELECT password FROM vendors WHERE id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$vendor = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stored_password = $vendor['password'] ?? '';
$isHashedPassword = preg_match('/^\$2y\$|\$2a\$|\$argon2i\$|\$argon2id\$/', $stored_password) === 1;
$currentPasswordMatches = $isHashedPassword
    ? password_verify($current_password, $stored_password)
    : hash_equals($stored_password, $current_password);

if (!$vendor || !$currentPasswordMatches) {
    header("Location: ../vendor/settings.php?notice=password-error");
    exit();
}

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE vendors SET password = ?, password_changed_at = NOW() WHERE id = ?");
$update->bind_param("si", $hashed_password, $vendor_id);

if ($update->execute()) {
    header("Location: ../vendor/settings.php?notice=password-updated");
    exit();
}

header("Location: ../vendor/settings.php?notice=password-error");
exit();

?>
