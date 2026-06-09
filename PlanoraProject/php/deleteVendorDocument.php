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
$document_id = (int) ($_POST['document_id'] ?? 0);

if ($document_id <= 0) {
    header("Location: ../vendor/settings.php?notice=error");
    exit();
}

$stmt = $conn->prepare("
    SELECT id, file_path
    FROM vendor_documents
    WHERE id = ?
      AND vendor_id = ?
      AND status = 'pending'
    LIMIT 1
");
$stmt->bind_param("ii", $document_id, $vendor_id);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document) {
    header("Location: ../vendor/settings.php?notice=delete-not-allowed");
    exit();
}

$deleteStmt = $conn->prepare("DELETE FROM vendor_documents WHERE id = ? AND vendor_id = ? AND status = 'pending'");
$deleteStmt->bind_param("ii", $document_id, $vendor_id);
$deleted = $deleteStmt->execute();
$deleteStmt->close();

if (!$deleted) {
    header("Location: ../vendor/settings.php?notice=error");
    exit();
}

$baseUploadDir = realpath(__DIR__ . '/../uploads/vendor_documents');
$filePath = trim((string) ($document['file_path'] ?? ''));
$absolutePath = realpath(__DIR__ . '/../' . ltrim($filePath, '/\\'));

if ($baseUploadDir && $absolutePath && strpos($absolutePath, $baseUploadDir) === 0 && is_file($absolutePath)) {
    unlink($absolutePath);
}

header("Location: ../vendor/settings.php?notice=document-deleted");
exit();

?>
