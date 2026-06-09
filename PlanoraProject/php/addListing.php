<?php

session_start();
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vendor/listings.php");
    exit();
}

if (!isset($_SESSION['vendor_id'])) {
    header("Location: ../vendor/index.php");
    exit();
}

function ensureListingColumn($conn, $column, $definition) {
    $sql = "SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'listings'
            AND COLUMN_NAME = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $column);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = (int) mysqli_fetch_assoc($result)['total'] > 0;

    if (!$exists) {
        mysqli_query($conn, "ALTER TABLE listings ADD COLUMN `$column` $definition");
    }
}

function cleanText($value) {
    return trim((string) ($value ?? ''));
}

function cleanChoiceList($values, $allowed) {
    $values = is_array($values) ? $values : [];
    $clean = [];

    foreach ($values as $value) {
        $value = strtolower(trim((string) $value));

        if (in_array($value, $allowed, true) && !in_array($value, $clean, true)) {
            $clean[] = $value;
        }
    }

    return $clean;
}

function uploadListingFile($fieldName, $options) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Upload failed for {$fieldName}.");
    }

    if ($_FILES[$fieldName]['size'] > $options['max_size']) {
        throw new RuntimeException("The {$fieldName} file is too large.");
    }

    $tmpName = $_FILES[$fieldName]['tmp_name'];
    $originalName = basename($_FILES[$fieldName]['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = $options['extensions'];

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException("Invalid {$fieldName} file type.");
    }

    $mimeType = mime_content_type($tmpName) ?: '';
    $allowedMimeTypes = $options['mime_types'];

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        throw new RuntimeException("Invalid {$fieldName} file content.");
    }

    $uploadDir = "../uploads/listings/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $safeName = uniqid($options['prefix'], true) . '.' . $extension;
    $targetPath = $uploadDir . $safeName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException("Could not save {$fieldName}.");
    }

    return "uploads/listings/" . $safeName;
}

ensureListingColumn($conn, 'requires_security_deposit', "TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
ensureListingColumn($conn, 'security_deposit_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER requires_security_deposit");
ensureListingColumn($conn, 'requires_logistics_fee', "TINYINT(1) NOT NULL DEFAULT 0 AFTER security_deposit_amount");
ensureListingColumn($conn, 'logo', "VARCHAR(255) NULL AFTER image");
ensureListingColumn($conn, 'service_highlights', "TEXT NULL AFTER description");
ensureListingColumn($conn, 'full_description', "TEXT NULL AFTER service_highlights");
ensureListingColumn($conn, 'available_days', "TEXT NULL AFTER full_description");
ensureListingColumn($conn, 'service_areas', "TEXT NULL AFTER available_days");

$vendor_id = (int) $_SESSION['vendor_id'];
$title = cleanText($_POST['title'] ?? '');
$type = strtolower(cleanText($_POST['type'] ?? 'ala carte'));
$description = cleanText($_POST['description'] ?? '');
$fullDescription = cleanText($_POST['full_description'] ?? '');
$price = (float) ($_POST['price'] ?? 0);
$status = strtolower(cleanText($_POST['status'] ?? 'active'));
$requiresSecurityDeposit = (int) ($_POST['requires_security_deposit'] ?? 0) === 1 ? 1 : 0;
$securityDepositAmount = $requiresSecurityDeposit ? (float) ($_POST['security_deposit_amount'] ?? 0) : 0;
$requiresLogisticsFee = (int) ($_POST['requires_logistics_fee'] ?? 0) === 1 ? 1 : 0;
$serviceAreas = cleanText($_POST['service_areas'] ?? '');

$allowedHighlights = ['weddings', 'birthdays', 'corporate', 'private events', 'others'];
$serviceHighlightsList = cleanChoiceList($_POST['service_highlights'] ?? [], $allowedHighlights);
$serviceHighlights = implode(',', $serviceHighlightsList);

$allowedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$availableDaysList = cleanChoiceList($_POST['available_days'] ?? [], $allowedDays);
$availableDays = implode(',', $availableDaysList);

if (!in_array($type, ['package', 'ala carte'], true)) {
    $type = 'ala carte';
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

$category = $serviceHighlightsList[0] ?? 'event';

if ($title === '' || $description === '' || $price < 0 || $serviceAreas === '' || empty($availableDaysList)) {
    die("Please complete all required listing fields.");
}

if ($requiresSecurityDeposit && $securityDepositAmount <= 0) {
    die("Please enter a valid security deposit amount.");
}

try {
    $imagePath = uploadListingFile('image', [
        'max_size' => 5 * 1024 * 1024,
        'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'prefix' => 'listing_',
    ]);

    $logoPath = uploadListingFile('logo', [
        'max_size' => 2 * 1024 * 1024,
        'extensions' => ['jpg', 'jpeg', 'png', 'svg'],
        'mime_types' => ['image/jpeg', 'image/png', 'image/svg+xml', 'text/xml'],
        'prefix' => 'logo_',
    ]);
} catch (RuntimeException $exception) {
    die($exception->getMessage());
}

if ($imagePath === '') {
    die("Please upload a listing image.");
}

$sql = "INSERT INTO listings
        (
            vendor_id,
            title,
            category,
            type,
            description,
            service_highlights,
            full_description,
            available_days,
            service_areas,
            price,
            status,
            requires_security_deposit,
            security_deposit_amount,
            requires_logistics_fee,
            image,
            logo
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "issssssssdsidiss",
    $vendor_id,
    $title,
    $category,
    $type,
    $description,
    $serviceHighlights,
    $fullDescription,
    $availableDays,
    $serviceAreas,
    $price,
    $status,
    $requiresSecurityDeposit,
    $securityDepositAmount,
    $requiresLogisticsFee,
    $imagePath,
    $logoPath
);

if (mysqli_stmt_execute($stmt)) {
    header("Location: ../vendor/listings.php");
    exit();
}

echo "Add listing failed: " . mysqli_error($conn);

?>
