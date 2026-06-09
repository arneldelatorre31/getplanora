<?php

if (!function_exists('vendorProfileImagePath')) {
    function vendorProfileImagePath($conn, $vendorId, $fallback = '../image/planoraLogo.jpg') {
        $vendorId = (int) $vendorId;

        if ($vendorId <= 0 || !$conn) {
            return $fallback;
        }

        $stmt = $conn->prepare("SELECT profile_image FROM vendors WHERE id = ? LIMIT 1");

        if (!$stmt) {
            return $fallback;
        }

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $path = trim(str_replace('\\', '/', (string) ($row['profile_image'] ?? '')));

        if ($path === '') {
            return $fallback;
        }

        $path = preg_replace('#^/?PlanoraProject/#i', '', $path);

        if (preg_match('~uploads/[^?#]+~i', $path, $match)) {
            $path = $match[0];
        }

        if (preg_match('/^(https?:\/\/|data:image\/|\/)/i', $path)) {
            return $path;
        }

        if (strpos($path, '../') === 0 || strpos($path, './') === 0) {
            return $path;
        }

        return '../' . ltrim($path, '/');
    }
}

?>
