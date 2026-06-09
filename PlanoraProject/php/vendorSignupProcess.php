<?php

session_start();

include 'connect.php';

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$business_name = trim($_POST['business_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($full_name === '' || $email === '' || $password === '') {
    die("Please complete all required fields.");
}

if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

$stmt = $conn->prepare("SELECT id FROM vendors WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    die("An account with this email already exists.");
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO vendors (full_name, email, phone, business_name, address, password) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $full_name, $email, $phone, $business_name, $address, $hashed_password);

if ($stmt->execute()) {
    $_SESSION['vendor_id'] = $stmt->insert_id;
    $_SESSION['vendor_name'] = $full_name;
    $_SESSION['vendor_email'] = $email;

    header("Location: ../vendor/index.php?new_account=1");
    exit();
}

echo "ERROR: " . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');

?>
