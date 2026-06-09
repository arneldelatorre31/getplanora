<?php

session_start();

if (!isset($_SESSION['vendor_id'])) {
    header("Location: ../vendor/index.php");
    exit();
}

include 'connect.php';

$vendor_id = (int) $_SESSION['vendor_id'];

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vendor_highlights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (vendor_id)
)");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vendor/profile.php");
    exit();
}

if (!isset($_FILES['highlight_image']) || $_FILES['highlight_image']['error'] === UPLOAD_ERR_NO_FILE) {
    header("Location: ../vendor/profile.php?highlight=missing");
    exit();
}

if ($_FILES['highlight_image']['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../vendor/profile.php?highlight=error");
    exit();
}

if ($_FILES['highlight_image']['size'] > 5 * 1024 * 1024) {
    header("Location: ../vendor/profile.php?highlight=large");
    exit();
}

$tmpName = $_FILES['highlight_image']['tmp_name'];
$originalName = basename($_FILES['highlight_image']['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$mimeType = mime_content_type($tmpName) ?: '';

if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
    header("Location: ../vendor/profile.php?highlight=type");
    exit();
}

$uploadDir = "../uploads/highlights/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$safeName = uniqid('highlight_' . $vendor_id . '_', true) . '.' . $extension;
$targetPath = $uploadDir . $safeName;

if (!move_uploaded_file($tmpName, $targetPath)) {
    header("Location: ../vendor/profile.php?highlight=save");
    exit();
}

$imagePath = "uploads/highlights/" . $safeName;
$stmt = $conn->prepare("INSERT INTO vendor_highlights (vendor_id, image_path) VALUES (?, ?)");
$stmt->bind_param("is", $vendor_id, $imagePath);
$stmt->execute();

header("Location: ../vendor/profile.php?highlight=uploaded");
exit();

?>
