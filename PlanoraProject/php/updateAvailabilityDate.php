<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['vendor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit();
}

include 'connect.php';
include 'bookingStatusAutomation.php';

$vendorId = (int) $_SESSION['vendor_id'];
$listingId = (int) ($_POST['listing_id'] ?? 0);
$date = trim((string) ($_POST['date'] ?? ''));
$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$timestamp = strtotime($date);

if ($listingId <= 0 || !$timestamp || !in_array($action, ['block', 'unblock'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid availability request.']);
    exit();
}

$date = date('Y-m-d', $timestamp);

ensureListingUnavailableDatesTable($conn);
runBookingStatusAutomation($conn, $vendorId);

$listingStmt = $conn->prepare("SELECT listing_id FROM listings WHERE listing_id = ? AND vendor_id = ?");
$listingStmt->bind_param("ii", $listingId, $vendorId);
$listingStmt->execute();

if ($listingStmt->get_result()->num_rows < 1) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Listing was not found.']);
    exit();
}

if ($action === 'block') {
    $stmt = $conn->prepare("
        INSERT INTO listing_unavailable_dates (vendor_id, listing_id, unavailable_date, reason)
        VALUES (?, ?, ?, 'Vendor marked not available')
        ON DUPLICATE KEY UPDATE vendor_id = VALUES(vendor_id), reason = VALUES(reason)
    ");
    $stmt->bind_param("iis", $vendorId, $listingId, $date);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        DELETE FROM listing_unavailable_dates
        WHERE vendor_id = ?
        AND listing_id = ?
        AND unavailable_date = ?
    ");
    $stmt->bind_param("iis", $vendorId, $listingId, $date);
    $stmt->execute();
}

echo json_encode([
    'success' => true,
    'date' => $date,
    'status' => $action === 'block' ? 'unavailable' : 'available'
]);

