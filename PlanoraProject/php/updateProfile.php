<?php

session_start();

if (!isset($_SESSION['vendor_id'])) {
    header("Location: ../vendor/index.php");
    exit();
}

include 'connect.php';

$vendor_id = (int) $_SESSION['vendor_id'];

function ensureVendorColumn($conn, $column, $definition) {
    $column = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query($conn, "SHOW COLUMNS FROM vendors LIKE '$column'");

    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($conn, "ALTER TABLE vendors ADD COLUMN `$column` $definition");
    }
}

function cleanProfileText($value) {
    return trim((string) ($value ?? ''));
}

function redirectProfile($notice) {
    header("Location: ../vendor/profile.php?updated=" . urlencode($notice));
    exit();
}

ensureVendorColumn($conn, 'about', 'TEXT NULL');
ensureVendorColumn($conn, 'profile_image', 'VARCHAR(255) NULL');

$full_name = cleanProfileText($_POST['full_name'] ?? '');
$business_name = cleanProfileText($_POST['business_name'] ?? '');
$email = cleanProfileText($_POST['email'] ?? '');
$phone = cleanProfileText($_POST['phone'] ?? '');
$address = cleanProfileText($_POST['address'] ?? '');
$about = cleanProfileText($_POST['about'] ?? '');
$profile_image = '';

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        redirectProfile('image-error');
    }

    if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
        redirectProfile('image-large');
    }

    $imageInfo = @getimagesize($_FILES['profile_image']['tmp_name']);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    if (!$imageInfo || !isset($allowedTypes[$imageInfo['mime']])) {
        redirectProfile('image-type');
    }

    $uploadDir = dirname(__DIR__) . '/uploads/profiles/';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        redirectProfile('image-save');
    }

    $extension = $allowedTypes[$imageInfo['mime']];
    $fileName = 'vendor_' . $vendor_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
        redirectProfile('image-save');
    }

    $profile_image = 'uploads/profiles/' . $fileName;
}

if ($profile_image !== '') {
    $stmt = $conn->prepare("
        UPDATE vendors
        SET full_name = ?, business_name = ?, email = ?, phone = ?, address = ?, about = ?, profile_image = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssssi", $full_name, $business_name, $email, $phone, $address, $about, $profile_image, $vendor_id);
} else {
    $stmt = $conn->prepare("
        UPDATE vendors
        SET full_name = ?, business_name = ?, email = ?, phone = ?, address = ?, about = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssssi", $full_name, $business_name, $email, $phone, $address, $about, $vendor_id);
}

if (!$stmt || !$stmt->execute()) {
    redirectProfile('error');
}

$_SESSION['vendor_name'] = $full_name;
$_SESSION['vendor_email'] = $email;

if ($profile_image !== '') {
    $_SESSION['vendor_profile_image'] = $profile_image;
}

redirectProfile('1');

?>
