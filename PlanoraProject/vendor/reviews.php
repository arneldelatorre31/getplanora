<?php

// UNCOMMENT THE LINE BELOW TO SHOW TEMPORARILY UNAVAILABLE MESSAGE
//header("Location: temporaryUnavailable.php?page=reviews"); exit();

session_start();

if (!isset($_SESSION['vendor_id'])) {
    header("Location: index.php");
    exit();
}

include '../php/connect.php';
include '../php/vendorProfileImage.php';
include '../php/vendorVerification.php';

$vendor_id = (int) $_SESSION['vendor_id'];
$sidebarProfileImage = vendorProfileImagePath($conn, $vendor_id);
$vendorVerificationLabel = vendorVerificationLabel($conn, $vendor_id);
$notice = $_GET['notice'] ?? '';

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function peso($value) {
    return '₱' . number_format((float) ($value ?? 0), 2);
}

function clientAvatarUrl($image, $clientName) {
    $image = trim((string) ($image ?? ''));

    if ($image !== '') {
        if (preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }

        $normalized = str_replace('\\', '/', $image);
        $candidates = [
            $normalized,
            '../image/' . basename($normalized),
            '../uploads/profiles/' . basename($normalized),
            '../uploads/listings/' . basename($normalized)
        ];

        foreach ($candidates as $candidate) {
            if (is_file(__DIR__ . '/' . $candidate)) {
                return $candidate;
            }
        }
    }

    $parts = preg_split('/\s+/', trim((string) $clientName));
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        if (strlen($initials) >= 2) {
            break;
        }
    }

    if ($initials === '') {
        $initials = 'CL';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128"><rect width="128" height="128" rx="64" fill="#f5c25c"/><text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="42" font-weight="700" fill="#111">' . e($initials) . '</text></svg>';

    return 'data:image/svg+xml,' . rawurlencode($svg);
}

function reviewStars($rating) {
    $rating = (int) $rating;
    $html = '';

    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'fa-solid' : 'fa-regular';
        $color = $i <= $rating ? '#f4be57' : '#555';
        $html .= '<i class="' . $class . ' fa-star" style="color:' . $color . ';"></i>';
    }

    return $html;
}

function reviewMatchKey($row) {
    return strtolower(trim((string) ($row['client_email'] ?? ''))) . '|'
        . strtolower(trim((string) ($row['service_name'] ?? ''))) . '|'
        . strtolower(trim((string) ($row['type'] ?? ''))) . '|'
        . trim((string) ($row['event_date'] ?? ''));
}

function findBooking($conn, $vendor_id, $client_email, $event_date, $service_name) {
    $stmt = $conn->prepare("
        SELECT b.*, l.title, l.category, l.description AS listing_description, l.image, l.type AS listing_type, l.price AS listing_price
        FROM bookings b
        LEFT JOIN listings l ON b.listing_id = l.listing_id
        WHERE b.vendor_id = ?
        AND b.client_email = ?
        AND (b.event_date = ? OR l.title = ? OR b.event_type = ?)
        ORDER BY
            CASE WHEN b.event_date = ? THEN 0 ELSE 1 END,
            b.id DESC
        LIMIT 1
    ");
    $stmt->bind_param("isssss", $vendor_id, $client_email, $event_date, $service_name, $service_name, $event_date);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $booking ?: [];
}

function buildReviewDetails($conn, $vendor_id, $row, $source) {
    $booking = findBooking(
        $conn,
        $vendor_id,
        $row['client_email'] ?? '',
        $row['event_date'] ?? '',
        $row['service_name'] ?? ''
    );

    $totalPrice = (float) ($booking['total_price'] ?? $booking['listing_price'] ?? 0);
    $logisticsFee = $totalPrice > 0 ? round($totalPrice * 0.05, 2) : 0;
    $securityDeposit = $totalPrice > 0 ? round($totalPrice * 0.10, 2) : 0;
    $totalAmount = $totalPrice + $logisticsFee + $securityDeposit;

    $clientImage = clientAvatarUrl($row['client_image'] ?? ($booking['client_image'] ?? ''), $row['client_name'] ?? 'Client');

    return [
        'source' => $source,
        'title' => $source === 'given' ? 'Review Given Details' : 'Review From Client Details',
        'clientName' => $row['client_name'] ?? 'Client',
        'clientEmail' => $row['client_email'] ?? 'Not set',
        'clientPhone' => $booking['client_phone'] ?? 'Not set',
        'clientImage' => $clientImage,
        'serviceName' => $row['service_name'] ?? ($booking['title'] ?? 'Service'),
        'serviceCategory' => $row['service_category'] ?? ($booking['category'] ?? 'Service'),
        'type' => $row['type'] ?? ($booking['listing_type'] ?? 'package'),
        'eventDate' => !empty($row['event_date']) ? date('M d, Y', strtotime($row['event_date'])) : 'Not set',
        'eventTime' => !empty($booking['event_time']) ? date('h:i A', strtotime($booking['event_time'])) : 'Not set',
        'venue' => $booking['venue'] ?? 'Not set',
        'guestCount' => !empty($booking['guest_count']) ? $booking['guest_count'] . ' guests' : 'Not set',
        'specialRequest' => $booking['special_request'] ?? 'No special request recorded.',
        'bookingStatus' => $booking['booking_status'] ?? 'Not set',
        'paymentStatus' => $booking['payment_status'] ?? 'Not set',
        'listingImage' => !empty($booking['image']) ? $booking['image'] : '../image/planoraLogo.jpg',
        'listingDescription' => $booking['listing_description'] ?? 'No listing description available.',
        'rating' => (float) ($row['rating'] ?? 0),
        'reviewText' => $row['review'] ?? '',
        'createdAt' => !empty($row['created_at']) ? date('M d, Y · h:i A', strtotime($row['created_at'])) : 'Not set',
        'basePrice' => peso($totalPrice),
        'logisticsFee' => peso($logisticsFee),
        'securityDeposit' => peso($securityDeposit),
        'totalAmount' => peso($totalAmount)
    ];
}

$clientReviewsStmt = $conn->prepare("
    SELECT *
    FROM reviews
    WHERE vendor_id = ?
    ORDER BY event_date DESC, id DESC
");
$clientReviewsStmt->bind_param("i", $vendor_id);
$clientReviewsStmt->execute();
$clientReviewsResult = $clientReviewsStmt->get_result();

$givenReviewsStmt = $conn->prepare("
    SELECT *
    FROM vendor_reviews_given
    WHERE vendor_id = ?
    ORDER BY event_date DESC, id DESC
");
$givenReviewsStmt->bind_param("i", $vendor_id);
$givenReviewsStmt->execute();
$givenReviewsResult = $givenReviewsStmt->get_result();
$givenReviews = [];
$givenReviewKeys = [];

while ($givenReview = $givenReviewsResult->fetch_assoc()) {
    $givenReviews[] = $givenReview;
    $givenReviewKeys[reviewMatchKey($givenReview)] = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Planora Reviews</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">
  <link rel="stylesheet" href="../css/reviews.css?v=3">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
</head>
<body>

<div class="dashboard">
  <aside class="sidebar">
    <div>
      <div class="logo">
        <img src="../image/planoraLogo.jpg" alt="Planora Logo">
      </div>

      <nav class="sidebar-menu">
        <a href="dashboard.php" class="menu-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
        <a href="bookings.php" class="menu-item"><i class="fa-solid fa-envelope-open-text"></i><span>Bookings</span></a>
        <a href="availability.php" class="menu-item"><i class="fa-solid fa-calendar-days"></i><span>Availability</span></a>
        <a href="messages.php" class="menu-item"><i class="fa-solid fa-message"></i><span>Messages</span></a>
        <a href="earnings.php" class="menu-item"><i class="fa-solid fa-coins"></i><span>Earnings</span></a>
        <a href="reviews.php" class="menu-item active"><i class="fa-solid fa-star"></i><span>Reviews</span></a>
        <a href="listings.php" class="menu-item"><i class="fa-regular fa-star"></i><span>My Listing</span></a>
        <a href="profile.php" class="menu-item"><i class="fa-solid fa-user"></i><span>Profile</span></a>
        <a href="settings.php" class="menu-item"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
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
          <a href="../php/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
        </div>
      </div>
    </div>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <h1>Reviews</h1>
      <p>See what clients are saying about you and manage the reviews you've given.</p>
    </div>

    <?php if ($notice): ?>
      <div class="notice <?php echo $notice === 'review-sent' ? 'success' : 'error'; ?>">
        <?php echo $notice === 'review-sent' ? 'Review sent to client.' : 'Unable to send review. Please try again.'; ?>
      </div>
    <?php endif; ?>

    <div class="review-tabs">
      <button class="review-tab active" data-target="clients"><i class="fa-regular fa-message"></i>Reviews from Clients</button>
      <button class="review-tab" data-target="given"><i class="fa-regular fa-square-check"></i>Reviews Given to Clients</button>
    </div>

    <div id="clientReviews">
      <div class="table-card">
        <div class="table-top">
          <select aria-label="Filter review type"><option>All Types</option></select>
          <select aria-label="Filter rating"><option>All Ratings</option></select>
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>CLIENT</th>
                <th>EVENT NAME</th>
                <th>TYPE</th>
                <th>EVENT DATE</th>
                <th>RATING</th>
                <th>REVIEW</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($clientReviewsResult->num_rows > 0): ?>
                <?php while ($row = $clientReviewsResult->fetch_assoc()): ?>
                  <?php
                    $details = buildReviewDetails($conn, $vendor_id, $row, 'client');
                    $clientAvatar = $details['clientImage'];
                    $detailsJson = e(json_encode($details, JSON_HEX_APOS | JSON_HEX_QUOT));
                    $clientImageForSave = str_starts_with($clientAvatar, 'data:image/') ? '' : $clientAvatar;
                    $hasGivenReview = isset($givenReviewKeys[reviewMatchKey($row)]);
                    $sendJson = e(json_encode([
                        'clientName' => $row['client_name'] ?? '',
                        'clientEmail' => $row['client_email'] ?? '',
                        'serviceName' => $row['service_name'] ?? '',
                        'serviceCategory' => $row['service_category'] ?? '',
                        'type' => $row['type'] ?? 'package',
                        'eventDate' => $row['event_date'] ?? '',
                        'clientImage' => $clientImageForSave,
                        'clientAvatar' => $clientAvatar
                    ], JSON_HEX_APOS | JSON_HEX_QUOT));
                  ?>
                  <tr>
                    <td>
                      <div class="client-cell">
                        <img src="<?php echo e($clientAvatar); ?>" alt="<?php echo e($row['client_name']); ?>">
                        <div>
                          <h4><?php echo e($row['client_name']); ?></h4>
                          <p><?php echo e($row['client_email']); ?></p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="service-box">
                        <i class="fa-solid fa-camera"></i>
                        <div>
                          <h4><?php echo e($row['service_name']); ?></h4>
                          <p><?php echo e($row['service_category']); ?></p>
                        </div>
                      </div>
                    </td>
                    <td><span class="type <?php echo strtolower($row['type']) === 'package' ? 'package' : 'ala'; ?>"><?php echo e(ucwords($row['type'])); ?></span></td>
                    <td><?php echo !empty($row['event_date']) ? date("M d, Y", strtotime($row['event_date'])) : 'Not set'; ?></td>
                    <td><div class="rating"><div><?php echo reviewStars($row['rating']); ?></div><strong><?php echo number_format((float) $row['rating'], 1); ?></strong></div></td>
                    <td><p class="review-text"><?php echo e($row['review']); ?></p></td>
                    <td>
                      <div class="row-actions">
                        <button class="view-btn js-view-details" type="button" data-details="<?php echo $detailsJson; ?>">View Details</button>
                        <?php if (!$hasGivenReview): ?>
                          <button class="send-review-btn js-send-review" type="button" data-review="<?php echo $sendJson; ?>">
                            <i class="fa-solid fa-paper-plane"></i>
                            Send Review
                          </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="7" class="empty-state">No client reviews found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="givenReviews" style="display:none;">
      <div class="table-card">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>CLIENT</th>
                <th>TYPE OF SERVICE</th>
                <th>TYPE</th>
                <th>EVENT DATE</th>
                <th>RATING</th>
                <th>REVIEW</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($givenReviews) > 0): ?>
                <?php foreach ($givenReviews as $row): ?>
                  <?php
                    $details = buildReviewDetails($conn, $vendor_id, $row, 'given');
                    $clientAvatar = $details['clientImage'];
                    $detailsJson = e(json_encode($details, JSON_HEX_APOS | JSON_HEX_QUOT));
                  ?>
                  <tr>
                    <td>
                      <div class="client-cell">
                        <img src="<?php echo e($clientAvatar); ?>" alt="<?php echo e($row['client_name']); ?>">
                        <div>
                          <h4><?php echo e($row['client_name']); ?></h4>
                          <p><?php echo e($row['client_email']); ?></p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="service-box">
                        <i class="fa-solid fa-cake-candles"></i>
                        <div>
                          <h4><?php echo e($row['service_name']); ?></h4>
                          <p><?php echo e($row['service_category']); ?></p>
                        </div>
                      </div>
                    </td>
                    <td><span class="type <?php echo strtolower($row['type']) === 'package' ? 'package' : 'ala'; ?>"><?php echo e(ucwords($row['type'])); ?></span></td>
                    <td><?php echo !empty($row['event_date']) ? date("M d, Y", strtotime($row['event_date'])) : 'Not set'; ?></td>
                    <td><div class="rating"><div><?php echo reviewStars($row['rating']); ?></div><strong><?php echo number_format((float) $row['rating'], 1); ?></strong></div></td>
                    <td><p class="review-text"><?php echo e($row['review']); ?></p></td>
                    <td><button class="view-btn js-view-details" type="button" data-details="<?php echo $detailsJson; ?>">View Details</button></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="empty-state">No reviews given yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<div class="review-modal" id="reviewDetailsModal">
  <div class="review-modal-overlay"></div>
  <div class="review-modal-box details-modal-box">
    <button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    <h2 id="detailsTitle">Review Details</h2>

    <div class="details-header">
      <div class="details-client">
        <img id="detailsClientImage" src="../image/default-profile.png" alt="">
        <div>
          <div class="client-title-row">
            <h3 id="detailsClientName">Client</h3>
            <span id="detailsSourceBadge">Client</span>
          </div>
          <p><i class="fa-solid fa-phone"></i><span id="detailsClientPhone">Not set</span></p>
          <p><i class="fa-regular fa-envelope"></i><span id="detailsClientEmail">Not set</span></p>
        </div>
      </div>

      <div class="details-meta">
        <span>Event Date</span>
        <strong id="detailsEventDate">Not set</strong>
        <span>Reviewed On</span>
        <strong id="detailsCreatedAt">Not set</strong>
      </div>

      <div class="details-rating">
        <span>Overall Rating</span>
        <div id="detailsStars" class="details-stars"></div>
        <strong><span id="detailsRating">0.0</span> / 5.0</strong>
      </div>
    </div>

    <div class="details-grid">
      <div class="details-left-panel">
        <section class="details-section">
          <h3>Booking Details</h3>
          <div class="detail-list">
            <div><span>Service</span><strong id="detailsServiceName">Service</strong></div>
            <div><span>Category</span><strong id="detailsServiceCategory">Category</strong></div>
            <div><span>Type</span><strong id="detailsType">Type</strong></div>
            <div><span>Event Time</span><strong id="detailsEventTime">Not set</strong></div>
            <div><span>Venue</span><strong id="detailsVenue">Not set</strong></div>
            <div><span>Guests</span><strong id="detailsGuestCount">Not set</strong></div>
            <div><span>Booking Status</span><strong id="detailsBookingStatus">Not set</strong></div>
            <div><span>Payment Status</span><strong id="detailsPaymentStatus">Not set</strong></div>
          </div>
        </section>

        <section class="details-section">
          <h3>Service Availed</h3>
          <div class="availed-service">
            <img id="detailsListingImage" src="../image/planoraLogo.jpg" alt="">
            <div>
              <h4 id="detailsListingName">Service</h4>
              <p id="detailsListingDescription">No listing description available.</p>
              <span id="detailsListingType" class="type package">Package</span>
            </div>
          </div>
        </section>

        <section class="details-section">
          <h3 id="detailsReviewHeading">Review</h3>
          <blockquote id="detailsReviewText">No review text.</blockquote>
        </section>
      </div>

      <div class="details-right-panel">
        <section class="details-section payment-card">
          <h3>Payment Summary</h3>
          <div class="payment-row"><span>Service Price</span><strong id="detailsBasePrice">₱0.00</strong></div>
          <div class="payment-row"><span>Logistics Fee</span><strong id="detailsLogisticsFee">₱0.00</strong></div>
          <div class="payment-row"><span>Security Deposit</span><strong id="detailsSecurityDeposit">₱0.00</strong></div>
          <div class="payment-total"><span>Total Amount</span><strong id="detailsTotalAmount">₱0.00</strong></div>
        </section>

        <section class="details-section">
          <h3>Rating Breakdown</h3>
          <div class="breakdown-row"><span>Quality of Service</span><strong id="breakdownQuality">0.0</strong></div>
          <div class="breakdown-row"><span>Professionalism</span><strong id="breakdownProfessionalism">0.0</strong></div>
          <div class="breakdown-row"><span>Communication</span><strong id="breakdownCommunication">0.0</strong></div>
          <div class="breakdown-row"><span>Value for Money</span><strong id="breakdownValue">0.0</strong></div>
          <div class="breakdown-row"><span>Timeliness</span><strong id="breakdownTimeliness">0.0</strong></div>
        </section>

        <section class="details-section">
          <h3>Client Request</h3>
          <p id="detailsSpecialRequest" class="request-text">No special request recorded.</p>
        </section>
      </div>
    </div>

    <div class="modal-actions">
      <button class="view-btn" type="button" data-close-modal>Close</button>
    </div>
  </div>
</div>

<div class="review-modal" id="sendReviewModal">
  <div class="review-modal-overlay"></div>
  <form class="review-modal-box send-modal-box" action="../php/addVendorReview.php" method="POST">
    <button class="modal-close" type="button" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    <h2>Send Review to Client</h2>
    <p class="send-review-subtitle">Your review will appear under Reviews Given to Clients.</p>

    <input type="hidden" name="client_name" id="sendClientName">
    <input type="hidden" name="client_email" id="sendClientEmail">
    <input type="hidden" name="service_name" id="sendServiceName">
    <input type="hidden" name="service_category" id="sendServiceCategory">
    <input type="hidden" name="type" id="sendType">
    <input type="hidden" name="event_date" id="sendEventDate">
    <input type="hidden" name="client_image" id="sendClientImage">

    <div class="send-client-card">
      <img id="sendClientPreviewImage" src="../image/default-profile.png" alt="">
      <div>
        <h3 id="sendClientPreviewName">Client</h3>
        <p id="sendClientPreviewEmail">client@email.com</p>
        <span id="sendClientPreviewService">Service</span>
      </div>
    </div>

    <label>Rating</label>
    <select name="rating" required>
      <option value="5">5 - Excellent</option>
      <option value="4">4 - Very Good</option>
      <option value="3">3 - Good</option>
      <option value="2">2 - Fair</option>
      <option value="1">1 - Poor</option>
    </select>

    <label>Review</label>
    <textarea name="review" rows="5" placeholder="Write your review for this client..." required></textarea>

    <button class="send-submit-btn" type="submit">
      <i class="fa-solid fa-paper-plane"></i>
      Send Review
    </button>
  </form>
</div>

<script src="../javascript/reviews.js?v=3"></script>
<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
