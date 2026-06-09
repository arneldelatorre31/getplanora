<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vendor/profile.php");
    exit();
}

if (!isset($_SESSION['vendor_id'])) {
    header("Location: ../vendor/index.php");
    exit();
}

include 'connect.php';

$vendor_id = (int) $_SESSION['vendor_id'];
$highlight_id = (int) ($_POST['highlight_id'] ?? 0);

if ($highlight_id <= 0) {
    header("Location: ../vendor/profile.php?notice=error");
    exit();
}

$stmt = $conn->prepare("SELECT id, image_path FROM vendor_highlights WHERE id = ? AND vendor_id = ? LIMIT 1");
$stmt->bind_param("ii", $highlight_id, $vendor_id);
$stmt->execute();
$highlight = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$highlight) {
    header("Location: ../vendor/profile.php?notice=not-found");
    exit();
}

$deleteStmt = $conn->prepare("DELETE FROM vendor_highlights WHERE id = ? AND vendor_id = ?");
$deleteStmt->bind_param("ii", $highlight_id, $vendor_id);
$deleted = $deleteStmt->execute();
$deleteStmt->close();

if (!$deleted) {
    header("Location: ../vendor/profile.php?notice=error");
    exit();
}

$baseUploadDir = realpath(__DIR__ . '/../uploads/highlights');
$filePath = trim((string) ($highlight['image_path'] ?? ''));
$absolutePath = realpath(__DIR__ . '/../' . ltrim($filePath, '/\\'));

if ($baseUploadDir && $absolutePath && strpos($absolutePath, $baseUploadDir) === 0 && is_file($absolutePath)) {
    @unlink($absolutePath);
}

header("Location: ../vendor/profile.php?notice=highlight-deleted");
exit();

?>
