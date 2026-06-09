<?php

if (!function_exists('vendorRequiredDocumentTypes')) {
    function vendorRequiredDocumentTypes() {
        return [
            'business verification' => 'Business Verification',
            'government id' => 'Government ID',
            'business permit' => 'Business Permit',
            'tax identification' => 'Tax Identification'
        ];
    }
}

if (!function_exists('ensureVendorDocumentsTable')) {
    function ensureVendorDocumentsTable($conn) {
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
    }
}

if (!function_exists('vendorDocumentStatuses')) {
    function vendorDocumentStatuses($conn, $vendorId) {
        ensureVendorDocumentsTable($conn);

        $statuses = [];

        foreach (vendorRequiredDocumentTypes() as $key => $label) {
            $statuses[$key] = [
                'label' => $label,
                'status' => 'not verified'
            ];
        }

        $stmt = $conn->prepare("
            SELECT document_name, status
            FROM vendor_documents
            WHERE vendor_id = ?
            ORDER BY uploaded_at DESC, id DESC
        ");
        $stmt->bind_param("i", $vendorId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($document = $result->fetch_assoc()) {
            $documentKey = strtolower(trim((string) $document['document_name']));

            if (isset($statuses[$documentKey]) && $statuses[$documentKey]['status'] === 'not verified') {
                $statuses[$documentKey]['status'] = strtolower((string) $document['status']);
            }
        }

        $stmt->close();

        return $statuses;
    }
}

if (!function_exists('isVendorVerified')) {
    function isVendorVerified($conn, $vendorId) {
        $statuses = vendorDocumentStatuses($conn, $vendorId);
        $verifiedCount = 0;

        foreach ($statuses as $documentStatus) {
            if ($documentStatus['status'] === 'verified') {
                $verifiedCount++;
            }
        }

        return $verifiedCount === count($statuses);
    }
}

if (!function_exists('vendorVerificationLabel')) {
    function vendorVerificationLabel($conn, $vendorId) {
        return isVendorVerified($conn, $vendorId) ? 'Verified Vendor' : 'Not Yet Verified';
    }
}

?>
