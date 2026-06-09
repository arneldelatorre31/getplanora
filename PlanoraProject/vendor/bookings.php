<?php
session_start();

// Uncomment the line below to show temporarily unavailable message.
//header("Location: temporaryUnavailable.php?page=bookings"); exit();

$currentDate = date("F d, Y");

if (!isset($_SESSION['vendor_id'])) {

    header("Location: index.php");
    exit();

}

include("../php/connect.php");
include("../php/vendorProfileImage.php");
include("../php/vendorVerification.php");
include("../php/bookingStatusAutomation.php");
$vendor_id = $_SESSION['vendor_id'];
$sidebarProfileImage = vendorProfileImagePath($conn, $vendor_id);
$vendorVerificationLabel = vendorVerificationLabel($conn, (int) $vendor_id);

runBookingStatusAutomation($conn, (int) $vendor_id);

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return '&#8369;' . number_format((float) ($value ?? 0), 2);
}

function moneyText($value) {
    return '₱' . number_format((float) ($value ?? 0), 2);
}

function displayDate($value, $format, $fallback = 'Not set') {
    if (empty($value)) {
        return $fallback;
    }

    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : $fallback;
}

function pageAsset($path, $fallback = '../image/default-profile.png') {
    $path = trim((string) ($path ?? ''));

    if ($path === '') {
        return $fallback;
    }

    if (preg_match('/^https?:\/\//i', $path) || str_starts_with($path, 'data:image/')) {
        return $path;
    }

    $normalized = str_replace('\\', '/', $path);
    $withoutLeading = ltrim($normalized, '/');
    $relativeToProject = dirname(__DIR__) . '/' . $withoutLeading;

    if (is_file($relativeToProject)) {
        return '../' . $withoutLeading;
    }

    if (is_file(__DIR__ . '/' . $withoutLeading)) {
        return $withoutLeading;
    }

    $basename = basename($withoutLeading);
    $candidates = [
        'uploads/profiles/' . $basename,
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

function firstValue($row, $keys, $fallback = '') {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
            return $row[$key];
        }
    }

    return $fallback;
}

?>
<?php
// TOTAL BOOKINGS
$stmt = $conn->prepare("
SELECT COUNT(*) AS total
FROM bookings
WHERE vendor_id = ?
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['total'];


// PENDING
$stmt = $conn->prepare("
SELECT COUNT(*) AS total
FROM bookings
WHERE vendor_id = ?
AND booking_status = 'pending'
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$pendingBookings = $stmt->get_result()->fetch_assoc()['total'];


// COMPLETED
$stmt = $conn->prepare("
SELECT COUNT(*) AS total
FROM bookings
WHERE vendor_id = ?
AND booking_status = 'completed'
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$completedEvents = $stmt->get_result()->fetch_assoc()['total'];

// CONFIRMED
$stmt = $conn->prepare("
SELECT COUNT(*) AS total
FROM bookings
WHERE vendor_id = ?
AND booking_status = 'confirmed'
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$confirmedBookings = $stmt->get_result()->fetch_assoc()['total'];


// CANCELLED
$stmt = $conn->prepare("
SELECT COUNT(*) AS total
FROM bookings
WHERE vendor_id = ?
AND booking_status = 'cancelled'
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$cancelledCount = $stmt->get_result()->fetch_assoc()['total'];
?>
<?php
$stmt = $conn->prepare("
SELECT
    b.*,
    l.title,
    l.category,
    l.type AS listing_type,
    l.description AS listing_description,
    l.image AS listing_image,
    l.price AS listing_price
FROM bookings b
LEFT JOIN listings l
    ON b.listing_id = l.listing_id
WHERE b.vendor_id = ?
ORDER BY b.id DESC
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();

$bookingsResult = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Planora - Booking Requests</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <!-- CSS -->
  <link rel="stylesheet" href="../css/bookings.css?v=8"/>

  <!-- FONT AWESOME -->
  <link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

  <!-- GOOGLE FONT -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>

<div class="dashboard">

  <!-- SIDEBAR -->

  <aside class="sidebar">

    <div>

      <!-- LOGO -->

      <div class="logo">
        <img src="../image/planoraLogo.jpg" alt="Planora Logo">
      </div>

      <!-- MENU -->

      <nav class="sidebar-menu">

        <a href="dashboard.php" class="menu-item">
          <i class="fa-solid fa-table-columns"></i>
          <span>Dashboard</span>
        </a>

        <a href="bookings.php" class="menu-item active">
          <i class="fa-solid fa-envelope-open-text"></i>
          <span>Bookings</span>
        </a>

        <a href="availability.php" class="menu-item">
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

    <!-- PROFILE -->

    <div class="vendor-profile">

      <img src="<?php echo e($sidebarProfileImage); ?>" alt="">

      <div class="vendor-info">
        <h4><?php echo $_SESSION['vendor_name'] ?? 'Vendor'; ?></h4>
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

  <!-- MAIN -->

  <main class="main-content">

    <!-- HEADER -->

    <div class="top-header">

      <div>

        <h1>Booking Request</h1>

        <div class="booking-stats">
          <span class="pending"><?php echo $pendingBookings; ?> pending</span>
          <span class="confirmed"><?php echo $confirmedBookings; ?> confirmed</span>
          <span class="completed"><?php echo $completedEvents; ?> completed</span>
          <span class="cancelled"><?php echo $cancelledCount; ?> cancelled</span>
        </div>

      </div>

      <!--<button class="export-btn" id="exportBtn">
        <i class="fa-solid fa-download"></i>
        Export Report
      </button> -->

    </div>

    <!-- FILTERS -->

    <div class="filters-wrapper">

      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="bookingSearch" placeholder="Search client, service, or package..." autocomplete="off">
      </div>

      <div class="type-filter">
        <select id="bookingTypeFilter" aria-label="Filter bookings by listing type">
          <option value="all">All Types</option>
          <option value="package">Package</option>
          <option value="ala-carte">Ala Carte</option>
        </select>
        <i class="fa-solid fa-chevron-down"></i>
      </div>

      <div class="filter-buttons">

        <button class="filter-btn active" type="button" data-status="all">All</button>
        <button class="filter-btn" type="button" data-status="pending">Pending</button>
        <button class="filter-btn" type="button" data-status="confirmed">Confirmed</button>
        <button class="filter-btn" type="button" data-status="completed">Completed</button>
        <button class="filter-btn" type="button" data-status="cancelled">Cancelled</button>

      </div>

    </div>

    <!-- TABLE -->

    <div class="table-container">

      <table>

        <thead>

          <tr>
            <th>CLIENT</th>
            <th>EVENT CATEGORY</th>
            <th>EVENT NAME</th>
            <th>PRICE</th>
            <th>TYPE</th>
            <th>EVENT DATE</th>
            <th>STATUS</th>
            <th>ACTIONS</th>
          </tr>

        </thead>

        <tbody id="bookingsTableBody">

          <?php while($row = $bookingsResult->fetch_assoc()) { ?>
            <?php
              $clientImage = pageAsset(firstValue($row, ['client_image', 'client_profile_image', 'profile_image']), '../image/planoraLogo.jpg');
              $listingImage = pageAsset($row['listing_image'] ?? '', '../image/planoraLogo.jpg');
              $status = strtolower((string) ($row['booking_status'] ?? 'pending'));
              $listingType = strtolower((string) ($row['listing_type'] ?? 'package'));
              $eventTimestamp = !empty($row['event_date']) ? strtotime($row['event_date']) : false;
              $eventDateOver = $eventTimestamp !== false && $eventTimestamp < strtotime(date('Y-m-d'));
              $canComplete = $status === 'confirmed' && $eventDateOver;
              $eventName = firstValue($row, ['event_name', 'event_type'], 'Booking Event');
              $venue = firstValue($row, ['venue', 'event_venue'], 'Not set');
              $venueNote = firstValue($row, ['venue_address', 'event_location', 'location', 'city'], '');
              $guestCount = firstValue($row, ['guest_count', 'guests'], 'Not set');
              $clientMessage = firstValue($row, ['special_request', 'message', 'notes'], 'No message from client.');
              $requestedAt = firstValue($row, ['created_at', 'requested_at', 'booking_date'], '');
              $basePrice = (float) ($row['listing_price'] ?? $row['total_price'] ?? 0);
              $totalPrice = (float) ($row['total_price'] ?? $basePrice);
              $logisticsFee = max(0, $totalPrice - $basePrice);
              $details = [
                  'clientName' => $row['client_name'] ?? 'Client',
                  'clientPhone' => $row['client_phone'] ?? 'Not set',
                  'clientEmail' => $row['client_email'] ?? 'Not set',
                  'clientImage' => $clientImage,
                  'eventName' => $eventName,
                  'eventDate' => displayDate($row['event_date'] ?? '', 'F d, Y'),
                  'eventDay' => displayDate($row['event_date'] ?? '', 'l'),
                  'eventTime' => displayDate($row['event_time'] ?? '', 'h:i A'),
                  'venue' => $venue,
                  'venueNote' => $venueNote,
                  'guestCount' => $guestCount,
                  'message' => $clientMessage,
                  'serviceTitle' => $row['title'] ?? 'Service',
                  'serviceCategory' => $row['category'] ?? 'Service',
                  'listingType' => ucwords(str_replace(['_', '-'], ' ', $listingType)),
                  'listingDescription' => $row['listing_description'] ?? 'No listing description available.',
                  'listingImage' => $listingImage,
                  'status' => ucfirst($status),
                  'statusClass' => $status,
                  'canComplete' => $canComplete,
                  'eventDateOver' => $eventDateOver,
                  'requestedAt' => $requestedAt ? displayDate($requestedAt, 'F d, Y h:i A') : 'Not set',
                  'bookingId' => (int) $row['id'],
                  'basePrice' => moneyText($basePrice),
                  'logisticsFee' => moneyText($logisticsFee),
                  'totalPrice' => moneyText($totalPrice)
              ];
              $searchText = strtolower(implode(' ', [
                  $row['client_name'] ?? '',
                  $row['client_phone'] ?? '',
                  $row['client_email'] ?? '',
                  $row['title'] ?? '',
                  $row['category'] ?? '',
                  $row['listing_type'] ?? '',
                  $eventName
              ]));
            ?>
            <tr class="booking-row" data-id="<?php echo (int) $row['id']; ?>" data-status="<?php echo e($status); ?>" data-can-complete="<?php echo $canComplete ? '1' : '0'; ?>" data-type="<?php echo e($listingType === 'package' ? 'package' : 'ala-carte'); ?>" data-search="<?php echo e($searchText); ?>">
              <td>
                <div class="client-info">
                  <img src="<?php echo e($clientImage); ?>" alt="<?php echo e($row['client_name'] ?? 'Client'); ?>">
                  <div>
                    <h4><?php echo e($row['client_name'] ?? 'Client'); ?></h4>
                    <p><?php echo e($row['client_phone'] ?? 'Not set'); ?></p>
                    <span><?php echo e($row['client_email'] ?? 'Not set'); ?></span>
                  </div>
                </div>
              </td>
              <td>
                <div class="service-type">
                  <i class="fa-solid fa-concierge-bell"></i>
                  <?php echo e($row['category'] ?? 'Service'); ?>
                </div>
              </td>
              <td><?php echo e($row['title'] ?? 'Service'); ?></td>
              <td class="price"><?php echo money($row['total_price']); ?></td>
              <td>
                <span class="type <?php echo $listingType == 'package' ? 'package' : 'ala-carte'; ?>">
                  <?php echo e(ucwords(str_replace(['_', '-'], ' ', $listingType))); ?>
                </span>
              </td>
              <td>
                <div class="event-date">
                  <i class="fa-regular fa-calendar"></i>
                  <div>
                    <h5><?php echo displayDate($row['event_date'] ?? '', 'M d, Y'); ?></h5>
                    <p><?php echo displayDate($row['event_date'] ?? '', 'l'); ?></p>
                  </div>
                </div>
              </td>
              <td>
                <span class="status <?php echo e($status); ?>-status">
                  &bull; <?php echo e(ucfirst($row['booking_status'])); ?>
                </span>
              </td>
              <td>
                <div class="actions">
                  <button class="view-btn js-view-booking" type="button" data-details="<?php echo e(json_encode($details, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)); ?>">
                    <i class="fa-regular fa-eye"></i> View
                  </button>
                  <div class="row-menu">
                    <button class="more-btn js-more-booking" type="button" aria-label="More options" aria-expanded="false">
                      <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <div class="row-menu-panel">
                      <button type="button" class="js-status-action" data-status="confirmed">
                        <i class="fa-solid fa-check"></i>
                        Confirm
                      </button>
                      <button type="button" class="js-status-action" data-status="completed" <?php echo $canComplete ? '' : 'disabled'; ?>>
                        <i class="fa-solid fa-circle-check"></i>
                        Complete
                      </button>
                      <button type="button" class="js-status-action danger" data-status="cancelled">
                        <i class="fa-solid fa-xmark"></i>
                        Cancel
                      </button>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php } ?>
          <tr id="emptyBookingsRow" <?php echo $bookingsResult->num_rows == 0 ? '' : 'hidden'; ?>>
            <td colspan="8" class="empty-bookings">No bookings found.</td>
          </tr>

        </tbody>

      </table>

    </div>

    <!-- FOOTER -->

    <div class="table-footer">

      <p id="bookingFooterText">Showing 0 requests</p>

      <div class="pagination" id="bookingPagination">

        <button type="button" id="prevPage" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
        <div id="pageButtons" class="page-buttons"></div>
        <button type="button" id="nextPage" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>

      </div>

    </div>

  </main>

</div>

<!-- BOOKING MODAL OVERLAY -->

<div class="booking-modal-overlay" id="bookingModal">

  <div class="booking-modal">

    <!-- CLOSE BUTTON -->

    <button class="close-modal" id="closeModal">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <!-- HEADER -->

    <div class="modal-header">

      <div class="modal-title">
        <div class="modal-icon">
          <i class="fa-regular fa-calendar"></i>
        </div>

        <div>
          <h2>Booking Request Details</h2>
          <p>Requested on May 30, 2025 • 10:30 AM</p>
        </div>
      </div>

      <div class="status-badge pending">
        ● Pending
      </div>

    </div>

    <!-- BODY -->

    <div class="modal-body">

      <!-- LEFT -->

      <div class="modal-left">

        <!-- CLIENT INFO -->

        <div class="modal-card">

          <h3>CLIENT INFORMATION</h3>

          <div class="client-info">

            <img src="https://i.pravatar.cc/100?img=32">

            <div>

              <div class="client-top">
                <h4>Maria Santos</h4>

                <span>Verified</span>
              </div>

              <p>
                <i class="fa-solid fa-phone"></i>
                +63 912 345 6789
              </p>

              <p>
                <i class="fa-regular fa-envelope"></i>
                maria.santos@email.com
              </p>

            </div>

          </div>

        </div>

        <!-- BOOKING SUMMARY -->

        <div class="modal-card">

          <h3>BOOKING SUMMARY</h3>

          <div class="summary-item">
            <i class="fa-regular fa-calendar"></i>

            <div>
              <span>EVENT NAME</span>
              <h4>Alvin & Trisha Wedding</h4>
            </div>
          </div>

          <div class="summary-item">
            <i class="fa-regular fa-calendar"></i>

            <div>
              <span>Event Date</span>
              <h4>June 7, 2025</h4>
            </div>
          </div>

          <div class="summary-item">
            <i class="fa-solid fa-location-dot"></i>

            <div>
              <span>VENUE</span>
              <h4>Bali Garden Resort</h4>
              <p>Tagaytay City</p>
            </div>
          </div>

          <div class="summary-item">
            <i class="fa-solid fa-users"></i>

            <div>
              <span>GUEST COUNT</span>
              <h4>150 pax</h4>
            </div>
          </div>

          <div class="summary-item">
            <i class="fa-regular fa-message"></i>

            <div>
              <span>MESSAGE FROM CLIENT</span>

              <p class="client-message">
                We would like a classic and elegant wedding setup for our special day.
              </p>
            </div>
          </div>

        </div>

      </div>

      <!-- CENTER -->

      <div class="modal-center">

        <!-- PACKAGE -->

        <div class="modal-card">

          <h3>PACKAGE LISTING</h3>

          <div class="listing-item">

            <img src="https://images.unsplash.com/photo-1519225421980-715cb0215aed?q=80&w=1200&auto=format&fit=crop">

            <div class="listing-content">

              <div>
                <h4>Elegant Wedding Package</h4>

                <p>
                  Full coordination, styling, catering, and elegant venue setup.
                </p>
              </div>

              <div class="listing-bottom">

                <span class="listing-tag">
                  Package
                </span>

                <h5>₱75,000</h5>

              </div>

            </div>

          </div>

        </div>
        <div class="modal-card">

          <h3>Ala Carte Listings ( 1 )</h3>

          <div class="listing-item">

            <img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=1200">

            <div class="listing-content">

              <div>
                <h4>Photo & Video Coverage</h4>

                <p>
                  Full coordination, styling, catering, and elegant venue setup.
                </p>
              </div>

              <div class="listing-bottom">

                <span class="listing-tag">
                  Ala Carte
                </span>

                <h5>₱5,000</h5>

              </div>

            </div>

          </div>

        </div>

        <!-- INCLUSIONS -->

        <div class="modal-card">

          <h3>INCLUSIONS</h3>

          <div class="inclusions">

            <div>
              <i class="fa-solid fa-circle-check"></i>
              Event Coordination
            </div>

            <div>
              <i class="fa-solid fa-circle-check"></i>
              Venue Styling
            </div>

            <div>
              <i class="fa-solid fa-circle-check"></i>
              Catering
            </div>

            <div>
              <i class="fa-solid fa-circle-check"></i>
              Basic Sound System
            </div>

          </div>

        </div>

      </div>

      <!-- RIGHT -->

      <div class="modal-right">

        <!-- ACTIONS -->

        <div class="modal-card">

          <h3>ACTIONS</h3>

          <button class="confirm-btn" type="button">
            <i class="fa-solid fa-check"></i>
            Confirm Booking
          </button>

          <button class="complete-btn" type="button">
            <i class="fa-solid fa-circle-check"></i>
            Mark Completed
          </button>

          <button class="message-btn" type="button">
            <i class="fa-regular fa-message"></i>
            Send Message
          </button>

          <button class="decline-btn" type="button">
            <i class="fa-solid fa-xmark"></i>
            Decline Request
          </button>

        </div>

        <!-- NOTES -->

        <div class="modal-card">

          <h3>NOTES</h3>

          <textarea placeholder="Add notes here..."></textarea>

        </div>

        <!-- TOTAL -->

        <div class="modal-card total-card">

          <div class="total-row">
            <span>Package Price</span>
            <span>₱75,000</span>
          </div>

          <div class="total-row">
            <span>Ala Carte Price</span>
            <span>₱5,000</span>
          </div>

          <div class="total-row">
            <span>Logistics Fee</span>
            <span>₱5,000</span>
          </div>

          <div class="grand-total">
            <span>TOTAL AMOUNT</span>
            <h2>₱85,000</h2>
          </div>

        </div>

      </div>

    </div>

  </div>

</div>

<script>

  const toggle = document.querySelector(".dropdown-toggle");
  const menu = document.getElementById("dropdownMenu");

  toggle.addEventListener("click", () => {
    menu.classList.toggle("show");
  });

  window.addEventListener("click", function(e){

    if(
      !e.target.closest(".vendor-dropdown")
    ){
      menu.classList.remove("show");
    }

  });

</script>

<script src="../javascript/bookings.js?v=5"></script>
<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
