<?php

function planoraColumnExists(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) ($row['total'] ?? 0) > 0;
}

function planoraBookingCreatedColumn(mysqli $conn): ?string {
    foreach (['created_at', 'requested_at', 'booking_date'] as $column) {
        if (planoraColumnExists($conn, 'bookings', $column)) {
            return $column;
        }
    }

    return null;
}

function runBookingStatusAutomation(mysqli $conn, ?int $vendorId = null): void {
    $createdColumn = planoraBookingCreatedColumn($conn);
    $vendorSql = $vendorId !== null ? " AND vendor_id = ?" : "";

    if ($createdColumn !== null) {
        $sql = "
            UPDATE bookings
            SET booking_status = 'cancelled'
            WHERE LOWER(booking_status) = 'pending'
            AND {$createdColumn} IS NOT NULL
            AND {$createdColumn} <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            {$vendorSql}
        ";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            if ($vendorId !== null) {
                $stmt->bind_param("i", $vendorId);
            }

            $stmt->execute();
        }
    }

    $sql = "
        UPDATE bookings
        SET booking_status = 'completed'
        WHERE LOWER(booking_status) = 'confirmed'
        AND event_date IS NOT NULL
        AND event_date < DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        {$vendorSql}
    ";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        if ($vendorId !== null) {
            $stmt->bind_param("i", $vendorId);
        }

        $stmt->execute();
    }
}

function ensureListingUnavailableDatesTable(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS listing_unavailable_dates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            listing_id INT NOT NULL,
            unavailable_date DATE NOT NULL,
            reason VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_listing_date (listing_id, unavailable_date),
            INDEX idx_vendor_listing_date (vendor_id, listing_id, unavailable_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
