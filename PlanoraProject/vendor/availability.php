<?php

// UNCOMMENT THE LINE BELOW TO SHOW TEMPORARILY UNAVAILABLE MESSAG
// header("Location: temporaryUnavailable.php?page=availability"); exit();

session_start();

if (!isset($_SESSION['vendor_id'])) {
    header("Location: index.php");
    exit();
}

include '../php/connect.php';
include '../php/vendorProfileImage.php';
include '../php/vendorVerification.php';
include '../php/bookingStatusAutomation.php';

$vendor_id = (int) $_SESSION['vendor_id'];
$sidebarProfileImage = vendorProfileImagePath($conn, $vendor_id);
$vendorVerificationLabel = vendorVerificationLabel($conn, $vendor_id);

ensureListingUnavailableDatesTable($conn);
runBookingStatusAutomation($conn, $vendor_id);

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function peso($value) {
    return '&#8369;' . number_format((float) ($value ?? 0), 2);
}

function assetPath($path, $fallback = '../image/planoraLogo.jpg') {
    $path = trim((string) ($path ?? ''));

    if ($path === '') {
        return $fallback;
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    $normalized = ltrim(str_replace('\\', '/', $path), '/');

    if (is_file(dirname(__DIR__) . '/' . $normalized)) {
        return '../' . $normalized;
    }

    $basename = basename($normalized);
    $candidates = [
        'uploads/listings/' . $basename,
        'image/' . $basename
    ];

    foreach ($candidates as $candidate) {
        if (is_file(dirname(__DIR__) . '/' . $candidate)) {
            return '../' . $candidate;
        }
    }

    return $fallback;
}

$listingsStmt = $conn->prepare("
    SELECT listing_id, title, category, type, price, image
    FROM listings
    WHERE vendor_id = ?
    ORDER BY
        CASE WHEN LOWER(type) = 'package' THEN 0 ELSE 1 END,
        title ASC,
        listing_id DESC
");
$listingsStmt->bind_param("i", $vendor_id);
$listingsStmt->execute();
$listingsResult = $listingsStmt->get_result();

$packageListings = [];
$alaCarteListings = [];

while ($listing = $listingsResult->fetch_assoc()) {
    $listing['type_normalized'] = strtolower(trim((string) ($listing['type'] ?? '')));

    if ($listing['type_normalized'] === 'package') {
        $packageListings[] = $listing;
    } else {
        $alaCarteListings[] = $listing;
    }
}

$bookingMap = [];
$bookingsStmt = $conn->prepare("
    SELECT listing_id, event_date, LOWER(booking_status) AS booking_status, COUNT(*) AS total
    FROM bookings
    WHERE vendor_id = ?
    AND event_date IS NOT NULL
    AND booking_status IN ('pending', 'confirmed', 'completed', 'cancelled')
    GROUP BY listing_id, event_date, LOWER(booking_status)
");
$bookingsStmt->bind_param("i", $vendor_id);
$bookingsStmt->execute();
$bookingsResult = $bookingsStmt->get_result();

while ($booking = $bookingsResult->fetch_assoc()) {
    $listingId = (int) $booking['listing_id'];
    $date = date('Y-m-d', strtotime($booking['event_date']));
    $status = $booking['booking_status'] === 'completed' ? 'confirmed' : $booking['booking_status'];

    if (!isset($bookingMap[$listingId])) {
        $bookingMap[$listingId] = [];
    }

    if (!isset($bookingMap[$listingId][$date])) {
        $bookingMap[$listingId][$date] = [
            'confirmed' => 0,
            'pending' => 0,
            'unavailable' => 0,
            'blocked' => 0
        ];
    }

    if ($status === 'cancelled') {
        $status = 'unavailable';
    }

    if (isset($bookingMap[$listingId][$date][$status])) {
        $bookingMap[$listingId][$date][$status] += (int) $booking['total'];
    }
}

$unavailableStmt = $conn->prepare("
    SELECT listing_id, unavailable_date
    FROM listing_unavailable_dates
    WHERE vendor_id = ?
");
$unavailableStmt->bind_param("i", $vendor_id);
$unavailableStmt->execute();
$unavailableResult = $unavailableStmt->get_result();

while ($blockedDate = $unavailableResult->fetch_assoc()) {
    $listingId = (int) $blockedDate['listing_id'];
    $date = date('Y-m-d', strtotime($blockedDate['unavailable_date']));

    if (!isset($bookingMap[$listingId])) {
        $bookingMap[$listingId] = [];
    }

    if (!isset($bookingMap[$listingId][$date])) {
        $bookingMap[$listingId][$date] = [
            'confirmed' => 0,
            'pending' => 0,
            'unavailable' => 0,
            'blocked' => 0
        ];
    }

    $bookingMap[$listingId][$date]['unavailable'] += 1;
    $bookingMap[$listingId][$date]['blocked'] += 1;
}

function renderListingCards($listings, $bookingMap) {
    if (count($listings) === 0) {
        echo '<div class="empty-state">No listings found in this category.</div>';
        return;
    }

    foreach ($listings as $listing) {
        $listingId = (int) $listing['listing_id'];
        $typeLabel = strtolower($listing['type'] ?? '') === 'package' ? 'Package' : 'Ala Carte';
        $bookingsJson = e(json_encode($bookingMap[$listingId] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
        ?>
        <div class="listing-card" data-listing-id="<?php echo $listingId; ?>" data-bookings="<?php echo $bookingsJson; ?>">
          <div class="listing-info">
            <img src="<?php echo e(assetPath($listing['image'] ?? '')); ?>" alt="<?php echo e($listing['title']); ?>">
            <div class="listing-details">
              <h2><?php echo e($listing['title']); ?></h2>
              <p><?php echo e($listing['category'] ?? 'Service'); ?></p>
              <div class="badge"><?php echo e($typeLabel); ?></div>
              <div class="price"><?php echo peso($listing['price'] ?? 0); ?></div>
              <button class="edit-availability-btn" type="button" data-edit-availability>
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Calendar
              </button>
            </div>
          </div>

          <div class="calendar" data-calendar>
            <div class="calendar-header">
              <button class="month-nav prev-month" type="button" aria-label="Previous month">
                <i class="fa-solid fa-chevron-left"></i>
              </button>
              <h3 data-month-label>Month Year</h3>
              <button class="month-nav next-month" type="button" aria-label="Next month">
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>

            <div class="weekdays">
              <div>Sun</div>
              <div>Mon</div>
              <div>Tue</div>
              <div>Wed</div>
              <div>Thu</div>
              <div>Fri</div>
              <div>Sat</div>
            </div>

            <div class="days" data-days></div>

            <div class="legend">
              <span><i class="legend-dot confirmed-dot"></i>Confirmed</span>
              <span><i class="legend-dot pending-dot"></i>Pending</span>
              <span><i class="legend-dot unavailable-dot"></i>Not Available</span>
              <span><i class="legend-ring"></i>Available</span>
            </div>
          </div>
        </div>
        <?php
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <title>Planora - Availability</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
  />

  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
  />

  <link rel="stylesheet" href="../css/availability.css?v=3">
</head>

<body>

<div class="container">

  <aside class="sidebar">

    <div>

      <div class="logo">
        <img src="../image/planoraLogo.jpg" alt="Planora Logo">
      </div>

      <nav class="sidebar-menu">

        <a href="dashboard.php" class="menu-item">
          <i class="fa-solid fa-table-columns"></i>
          <span>Dashboard</span>
        </a>

        <a href="bookings.php" class="menu-item">
          <i class="fa-solid fa-envelope-open-text"></i>
          <span>Bookings</span>
        </a>

        <a href="availability.php" class="menu-item active">
          <i class="fa-solid fa-calendar-days"></i>
          <span>Availability</span>
        </a>

        <a href="messages.php" class="menu-item">
          <i class="fa-solid fa-message"></i>
          <span>Messages</span>
        </a>

        <a href="earnings.php" class="menu-item">
          <i class="fa-solid fa-coins"></i>
          <span>Earnings</span>
        </a>

        <a href="reviews.php" class="menu-item">
          <i class="fa-solid fa-star"></i>
          <span>Reviews</span>
        </a>

        <a href="listings.php" class="menu-item">
          <i class="fa-regular fa-star"></i>
          <span>My Listing</span>
        </a>

        <a href="profile.php" class="menu-item">
          <i class="fa-solid fa-user"></i>
          <span>Profile</span>
        </a>

        <a href="settings.php" class="menu-item">
          <i class="fa-solid fa-gear"></i>
          <span>Settings</span>
        </a>

      </nav>

    </div>

    <div class="vendor-profile">

      <img src="<?php echo e($sidebarProfileImage); ?>" alt="">

      <div class="vendor-info">
        <h4><?php echo e($_SESSION['vendor_name'] ?? 'Vendor'); ?></h4>
        <p><?php echo e($vendorVerificationLabel); ?></p>
      </div>

      <div class="vendor-dropdown">

        <i class="fa-solid fa-chevron-down dropdown-toggle"></i>

        <div class="dropdown-menu" id="dropdownMenu">

          <a href="index.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
          </a>

        </div>

      </div>

    </div>

  </aside>

  <main class="main-content">

    <div class="top-bar">

      <div>
        <h1>Availability</h1>
        <p>Manage booking availability for each listing.</p>
      </div>

      <button class="sync-btn" id="syncCalendarBtn" type="button">
        <i class="fa-solid fa-rotate"></i>
        Sync Calendar
      </button>

    </div>

    <div class="sync-notice" id="syncNotice" <?php echo isset($_GET['synced']) ? '' : 'hidden'; ?>>Calendar synced from current booking records.</div>

    <div class="tabs">

      <button class="tab-btn active" type="button" data-tab="package">
        <i class="fa-solid fa-box"></i>
        Package Listings
      </button>

      <button class="tab-btn" type="button" data-tab="alacarte">
        <i class="fa-solid fa-utensils"></i>
        Ala Carte Listings
      </button>

    </div>

    <div id="package" class="listings-section active">
      <?php renderListingCards($packageListings, $bookingMap); ?>
    </div>

    <div id="alacarte" class="listings-section">
      <?php renderListingCards($alaCarteListings, $bookingMap); ?>
    </div>

  </main>

</div>

<script>
  const toggle = document.querySelector(".dropdown-toggle");
  const menu = document.getElementById("dropdownMenu");

  toggle.addEventListener("click", () => {
    menu.classList.toggle("show");
  });

  window.addEventListener("click", function(e){
    if(!e.target.closest(".vendor-dropdown")){
      menu.classList.remove("show");
    }
  });
</script>

<script src="../javascript/availability.js?v=4"></script>

</body>
</html>
