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
$bookingId = (int) ($_POST['booking_id'] ?? 0);
$status = strtolower(trim((string) ($_POST['status'] ?? '')));
$allowedStatuses = ['confirmed', 'cancelled', 'completed'];

if ($bookingId <= 0 || !in_array($status, $allowedStatuses, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid booking status request.']);
    exit();
}

runBookingStatusAutomation($conn, $vendorId);

$bookingStmt = $conn->prepare("
    SELECT id, booking_status, event_date
    FROM bookings
    WHERE id = ?
    AND vendor_id = ?
");

if (!$bookingStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not prepare booking lookup.']);
    exit();
}

$bookingStmt->bind_param("ii", $bookingId, $vendorId);
$bookingStmt->execute();
$booking = $bookingStmt->get_result()->fetch_assoc();

if (!$booking) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Booking was not found.']);
    exit();
}

$currentStatus = strtolower((string) ($booking['booking_status'] ?? ''));
$eventDate = (string) ($booking['event_date'] ?? '');
$eventTimestamp = $eventDate !== '' ? strtotime($eventDate) : false;
$todayTimestamp = strtotime(date('Y-m-d'));

if ($currentStatus === 'completed') {
    echo json_encode(['success' => true, 'status' => 'completed']);
    exit();
}

if ($currentStatus === $status) {
    echo json_encode(['success' => true, 'status' => $status]);
    exit();
}

if ($status === 'completed') {
    if ($currentStatus !== 'confirmed') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Only confirmed bookings can be marked as completed.']);
        exit();
    }

    if ($eventTimestamp === false || $eventTimestamp >= $todayTimestamp) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'You can only mark a booking as completed after the event date is over.']);
        exit();
    }
}

if ($status === 'completed') {
    $stmt = $conn->prepare("
        UPDATE bookings
        SET booking_status = ?
        WHERE id = ?
        AND vendor_id = ?
        AND LOWER(booking_status) = 'confirmed'
    ");
} else {
    $stmt = $conn->prepare("
        UPDATE bookings
        SET booking_status = ?
        WHERE id = ?
        AND vendor_id = ?
        AND LOWER(booking_status) IN ('pending', 'confirmed', 'cancelled')
    ");
}

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not prepare status update.']);
    exit();
}

$stmt->bind_param("sii", $status, $bookingId, $vendorId);
$stmt->execute();

if ($stmt->affected_rows < 1) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Booking was not found or cannot be changed.']);
    exit();
}

echo json_encode(['success' => true, 'status' => $status]);
