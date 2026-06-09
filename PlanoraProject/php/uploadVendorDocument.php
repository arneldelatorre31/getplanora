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
$document_name = trim($_POST['document_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$allowedDocumentTypes = [
    'business verification',
    'government id',
    'business permit',
    'tax identification'
];

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vendor_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    document_name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (vendor_id)
)");

$document_name = strtolower($document_name);

if (!in_array($document_name, $allowedDocumentTypes, true) || !isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../vendor/settings.php?notice=upload-error");
    exit();
}

$allowedTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp'
];

$fileType = mime_content_type($_FILES['document_file']['tmp_name']);

if (!in_array($fileType, $allowedTypes, true)) {
    header("Location: ../vendor/settings.php?notice=upload-error");
    exit();
}

$uploadDir = '../uploads/vendor_documents/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$originalName = basename($_FILES['document_file']['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$safeName = 'vendor_' . $vendor_id . '_' . uniqid('doc_', true);

if ($extension !== '') {
    $safeName .= '.' . $extension;
}

$targetPath = $uploadDir . $safeName;

if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $targetPath)) {
    header("Location: ../vendor/settings.php?notice=upload-error");
    exit();
}

$filePath = 'uploads/vendor_documents/' . $safeName;
$status = 'pending';

$existing = $conn->prepare("SELECT id FROM vendor_documents WHERE vendor_id = ? AND LOWER(document_name) = ? LIMIT 1");
$existing->bind_param("is", $vendor_id, $document_name);
$existing->execute();
$existingDocument = $existing->get_result()->fetch_assoc();
$existing->close();

if ($existingDocument) {
    $document_id = (int) $existingDocument['id'];
    $stmt = $conn->prepare("UPDATE vendor_documents SET description = ?, file_path = ?, status = ?, uploaded_at = CURRENT_TIMESTAMP WHERE id = ? AND vendor_id = ?");
    $stmt->bind_param("sssii", $description, $filePath, $status, $document_id, $vendor_id);
} else {
    $stmt = $conn->prepare("INSERT INTO vendor_documents (vendor_id, document_name, description, file_path, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $vendor_id, $document_name, $description, $filePath, $status);
}

if ($stmt->execute()) {
    header("Location: ../vendor/settings.php?notice=document-uploaded");
    exit();
}

header("Location: ../vendor/settings.php?notice=upload-error");
exit();

?>
