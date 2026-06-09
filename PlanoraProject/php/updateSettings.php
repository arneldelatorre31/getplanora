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
$section = $_POST['section'] ?? '';

if ($section === 'personal') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $stmt = $conn->prepare("UPDATE vendors SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $vendor_id);

    if ($stmt->execute()) {
        $_SESSION['vendor_name'] = $full_name;
        $_SESSION['vendor_email'] = $email;
        header("Location: ../vendor/settings.php?notice=profile-updated");
        exit();
    }
}

if ($section === 'business') {
    $business_name = trim($_POST['business_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $stmt = $conn->prepare("UPDATE vendors SET business_name = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssi", $business_name, $phone, $vendor_id);

    if ($stmt->execute()) {
        header("Location: ../vendor/settings.php?notice=profile-updated");
        exit();
    }
}

if ($section === 'address') {
    $address = trim($_POST['address'] ?? '');

    $stmt = $conn->prepare("UPDATE vendors SET address = ? WHERE id = ?");
    $stmt->bind_param("si", $address, $vendor_id);

    if ($stmt->execute()) {
        header("Location: ../vendor/settings.php?notice=profile-updated");
        exit();
    }
}

header("Location: ../vendor/settings.php?notice=error");
exit();

?>
