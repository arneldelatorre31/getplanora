<?php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['vendor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

include 'connect.php';

$vendor_id = (int) $_SESSION['vendor_id'];

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS vendor_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NULL,
        attachment_path VARCHAR(255) NULL,
        attachment_name VARCHAR(255) NULL,
        read_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vendor_pair (sender_id, receiver_id),
        INDEX idx_receiver_read (receiver_id, read_at),
        INDEX idx_created_at (created_at)
    )
");

$readColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM vendor_messages LIKE 'read_at'");
if ($readColumnCheck && mysqli_num_rows($readColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE vendor_messages ADD COLUMN read_at DATETIME NULL AFTER attachment_name");
}

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

function cleanMessage($value) {
    return trim((string) ($value ?? ''));
}

function vendorExists($conn, $vendorId) {
    $stmt = $conn->prepare("SELECT id FROM vendors WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $vendorId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function formatMessage($row, $currentVendorId) {
    return [
        'id' => (int) $row['id'],
        'senderId' => (int) $row['sender_id'],
        'receiverId' => (int) $row['receiver_id'],
        'message' => $row['message'] ?? '',
        'attachmentPath' => $row['attachment_path'] ? '../' . $row['attachment_path'] : '',
        'attachmentName' => $row['attachment_name'] ?? '',
        'createdAt' => $row['created_at'],
        'time' => date('g:i A', strtotime($row['created_at'])),
        'direction' => (int) $row['sender_id'] === $currentVendorId ? 'sent' : 'received'
    ];
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$receiver_id = (int) ($_POST['receiver_id'] ?? $_GET['receiver_id'] ?? 0);

if ($action === 'unread_count') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM vendor_messages WHERE receiver_id = ? AND read_at IS NULL");
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);

    respond(['success' => true, 'unreadCount' => $total]);
}

if ($receiver_id <= 0 || $receiver_id === $vendor_id || !vendorExists($conn, $receiver_id)) {
    respond(['success' => false, 'message' => 'Invalid receiver.'], 400);
}

if ($action === 'list') {
    $afterId = (int) ($_GET['after_id'] ?? 0);
    $markRead = (int) ($_GET['mark_read'] ?? 0);

    $stmt = $conn->prepare("
        SELECT *
        FROM vendor_messages
        WHERE id > ?
        AND (
            (sender_id = ? AND receiver_id = ?)
            OR
            (sender_id = ? AND receiver_id = ?)
        )
        ORDER BY id ASC
    ");
    $stmt->bind_param("iiiii", $afterId, $vendor_id, $receiver_id, $receiver_id, $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $messages[] = formatMessage($row, $vendor_id);
    }

    if ($markRead === 1) {
        $markReadStmt = $conn->prepare("
            UPDATE vendor_messages
            SET read_at = NOW()
            WHERE sender_id = ?
            AND receiver_id = ?
            AND read_at IS NULL
        ");
        $markReadStmt->bind_param("ii", $receiver_id, $vendor_id);
        $markReadStmt->execute();
    }

    respond(['success' => true, 'messages' => $messages]);
}

if ($action === 'send') {
    $message = cleanMessage($_POST['message'] ?? '');
    $attachmentPath = '';
    $attachmentName = '';

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/messages/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $originalName = basename($_FILES['attachment']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt'];

        if (!in_array($extension, $allowed, true)) {
            respond(['success' => false, 'message' => 'File type is not allowed.'], 400);
        }

        if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
            respond(['success' => false, 'message' => 'Attachment must be 5MB or smaller.'], 400);
        }

        $safeName = 'message_' . $vendor_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . $safeName;

        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            respond(['success' => false, 'message' => 'Unable to upload attachment.'], 500);
        }

        $attachmentPath = 'uploads/messages/' . $safeName;
        $attachmentName = $originalName;
    }

    if ($message === '' && $attachmentPath === '') {
        respond(['success' => false, 'message' => 'Message or attachment is required.'], 400);
    }

    $stmt = $conn->prepare("
        INSERT INTO vendor_messages (sender_id, receiver_id, message, attachment_path, attachment_name)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $vendor_id, $receiver_id, $message, $attachmentPath, $attachmentName);

    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'Unable to send message.'], 500);
    }

    $messageId = $stmt->insert_id;
    $stmt = $conn->prepare("SELECT * FROM vendor_messages WHERE id = ?");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    respond(['success' => true, 'message' => formatMessage($row, $vendor_id)]);
}

respond(['success' => false, 'message' => 'Unknown action.'], 400);
