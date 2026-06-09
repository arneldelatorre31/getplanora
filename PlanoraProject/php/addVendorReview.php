<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vendor/reviews.php");
    exit();
}

if (!isset($_SESSION['vendor_id'])) {
    header("Location: ../vendor/index.php");
    exit();
}

include 'connect.php';

$vendor_id = (int) $_SESSION['vendor_id'];
$client_name = trim($_POST['client_name'] ?? '');
$client_email = trim($_POST['client_email'] ?? '');
$service_name = trim($_POST['service_name'] ?? '');
$service_category = trim($_POST['service_category'] ?? '');
$type = strtolower(trim($_POST['type'] ?? 'package'));
$event_date = trim($_POST['event_date'] ?? '');
$rating = (int) ($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');
$client_image = trim($_POST['client_image'] ?? '');

if (!in_array($type, ['package', 'ala carte'], true)) {
    $type = 'package';
}

if ($client_name === '' || $client_email === '' || $service_name === '' || $event_date === '' || $rating < 1 || $rating > 5 || $review === '') {
    header("Location: ../vendor/reviews.php?notice=review-error");
    exit();
}

$duplicateStmt = $conn->prepare("
    SELECT id
    FROM vendor_reviews_given
    WHERE vendor_id = ?
    AND client_email = ?
    AND service_name = ?
    AND type = ?
    AND event_date = ?
    LIMIT 1
");
$duplicateStmt->bind_param("issss", $vendor_id, $client_email, $service_name, $type, $event_date);
$duplicateStmt->execute();
$duplicateReview = $duplicateStmt->get_result()->fetch_assoc();
$duplicateStmt->close();

if ($duplicateReview) {
    header("Location: ../vendor/reviews.php?notice=review-sent");
    exit();
}

$stmt = $conn->prepare("
    INSERT INTO vendor_reviews_given
    (vendor_id, client_name, client_email, service_name, service_category, type, event_date, rating, review, client_image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "issssssiss",
    $vendor_id,
    $client_name,
    $client_email,
    $service_name,
    $service_category,
    $type,
    $event_date,
    $rating,
    $review,
    $client_image
);

if ($stmt->execute()) {
    header("Location: ../vendor/reviews.php?notice=review-sent");
    exit();
}

header("Location: ../vendor/reviews.php?notice=review-error");
exit();

?>
