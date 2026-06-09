<?php

// UNCOMMENT THE LINE BELOW TO SHOW TEMPORARILY UNAVAILABLE MESSAGE
//header("Location: temporaryUnavailable.php?page=profile"); exit();

session_start();

include '../php/connect.php';
include '../php/vendorVerification.php';

// Allow public viewing via ?id=123. If no id provided, require login.
$requested_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($requested_id > 0) {
  $vendor_id = $requested_id;
  $is_owner = isset($_SESSION['vendor_id']) && (int) $_SESSION['vendor_id'] === $requested_id;
} else {
  if (!isset($_SESSION['vendor_id'])) {
    header("Location: index.php");
    exit();
  }
  $vendor_id = (int) $_SESSION['vendor_id'];
  $is_owner = true;
}

$canManageHighlights = isset($_SESSION['vendor_id']) && (int) $_SESSION['vendor_id'] === (int) $vendor_id;

$documentTypes = [
    'business verification' => 'Business Verification',
    'government id' => 'Government ID',
    'business permit' => 'Business Permit',
    'tax identification' => 'Tax Identification'
];

function listingImageSrc($imagePath) {
    $imagePath = trim(str_replace('\\', '/', (string) ($imagePath ?? '')));

    if ($imagePath === '') {
        return '../image/planoraLogo.jpg';
    }

    $imagePath = preg_replace('#^/?PlanoraProject/#i', '', $imagePath);

    if (preg_match('~uploads/[^?#]+~i', $imagePath, $match)) {
        $imagePath = $match[0];
    }

    if (preg_match('/^(https?:\/\/|data:image\/|\/)/i', $imagePath)) {
        return $imagePath;
    }

    if (strpos($imagePath, '../') === 0 || strpos($imagePath, './') === 0) {
        return $imagePath;
    }

    return '../' . ltrim($imagePath, '/\\');
}

function latestVendorProfileImage($vendorId) {
    $profileDir = dirname(__DIR__) . '/uploads/profiles';

    if (!is_dir($profileDir)) {
        return '';
    }

    $files = glob($profileDir . '/vendor_' . (int) $vendorId . '_*');

    if (!$files) {
        return '';
    }

    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });

    return 'uploads/profiles/' . basename($files[0]);
}

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

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vendor_highlights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (vendor_id)
)");

// FETCH VENDOR PROFILE
$vendorQuery = mysqli_query($conn, "SELECT * FROM vendors WHERE id='$vendor_id'");
$vendor = mysqli_fetch_assoc($vendorQuery);

if ($vendor && empty($vendor['profile_image'])) {
    $latestProfileImage = latestVendorProfileImage($vendor_id);

    if ($latestProfileImage !== '') {
        $profileImageStmt = $conn->prepare("UPDATE vendors SET profile_image = ? WHERE id = ?");
        $profileImageStmt->bind_param("si", $latestProfileImage, $vendor_id);
        $profileImageStmt->execute();
        $profileImageStmt->close();
        $vendor['profile_image'] = $latestProfileImage;
    }
}

// FETCH VENDOR VERIFICATION DOCUMENT STATUSES
$documentStatuses = [];
foreach ($documentTypes as $key => $label) {
    $documentStatuses[$key] = [
        'label' => $label,
        'status' => 'not verified'
    ];
}

$documentsStmt = $conn->prepare("
    SELECT document_name, status
    FROM vendor_documents
    WHERE vendor_id = ?
    ORDER BY uploaded_at DESC, id DESC
");
$documentsStmt->bind_param("i", $vendor_id);
$documentsStmt->execute();
$documentsResult = $documentsStmt->get_result();

while ($document = $documentsResult->fetch_assoc()) {
    $documentKey = strtolower(trim((string) $document['document_name']));

    if (isset($documentStatuses[$documentKey]) && $documentStatuses[$documentKey]['status'] === 'not verified') {
        $documentStatuses[$documentKey]['status'] = strtolower((string) $document['status']);
    }
}

$documentsStmt->close();

$verificationStatusCounts = array_count_values(array_column($documentStatuses, 'status'));
$allDocumentsVerified = ($verificationStatusCounts['verified'] ?? 0) === count($documentTypes);
$hasPendingDocuments = ($verificationStatusCounts['pending'] ?? 0) > 0;
$hasRejectedDocuments = ($verificationStatusCounts['rejected'] ?? 0) > 0;
$hasMissingDocuments = ($verificationStatusCounts['not verified'] ?? 0) > 0;

if ($allDocumentsVerified) {
    $verificationSummaryTitle = 'Verified Vendor';
    $verificationSummaryText = 'This vendor passed all verification checks.';
    $verificationSummaryIcon = 'fa-shield-halved';
} elseif ($hasPendingDocuments) {
    $verificationSummaryTitle = 'Verification Pending';
    $verificationSummaryText = 'Uploaded documents are waiting for review.';
    $verificationSummaryIcon = 'fa-clock';
} elseif ($hasRejectedDocuments) {
    $verificationSummaryTitle = 'Verification Needs Attention';
    $verificationSummaryText = 'Some documents need to be reviewed or re-uploaded.';
    $verificationSummaryIcon = 'fa-triangle-exclamation';
} elseif ($hasMissingDocuments) {
    $verificationSummaryTitle = 'Not Verified';
    $verificationSummaryText = 'Upload verification documents in Settings to begin verification.';
    $verificationSummaryIcon = 'fa-circle-exclamation';
} else {
    $verificationSummaryTitle = 'Verification Incomplete';
    $verificationSummaryText = 'Complete all verification documents in Settings.';
    $verificationSummaryIcon = 'fa-circle-exclamation';
}

// TOTAL BOOKINGS
$totalBookingsQuery = mysqli_query($conn,
    "SELECT COUNT(*) as total FROM bookings WHERE vendor_id='$vendor_id'");
$totalBookings = mysqli_fetch_assoc($totalBookingsQuery)['total'];

// COMPLETED BOOKINGS
$completedBookingsQuery = mysqli_query($conn,
    "SELECT COUNT(*) as total FROM bookings WHERE vendor_id='$vendor_id' AND status='completed'");
$completedBookings = mysqli_fetch_assoc($completedBookingsQuery)['total'];

// COMPLETION RATE
$completionRate = $totalBookings > 0
    ? round(($completedBookings / $totalBookings) * 100, 1)
    : 0;

// TOTAL EARNINGS
$earningsQuery = mysqli_query($conn,
    "SELECT SUM(total_price) as total
     FROM bookings
     WHERE vendor_id='$vendor_id'
     AND status='completed'");

$earningsRow = mysqli_fetch_assoc($earningsQuery);
$totalEarnings = $earningsRow['total'] ?? 0;


// MEMBER SINCE
$memberSinceQuery = mysqli_query($conn,
    "SELECT MIN(created_at) as earliest
     FROM bookings
     WHERE vendor_id='$vendor_id'");

$memberRow = mysqli_fetch_assoc($memberSinceQuery);

$memberSince = !empty($memberRow['earliest'])
    ? date('M d, Y', strtotime($memberRow['earliest']))
    : 'Recently Joined';

// FETCH LISTINGS (SERVICES)
$listingsQuery = mysqli_query($conn,
    "SELECT * FROM listings WHERE vendor_id='$vendor_id' ORDER BY listing_id DESC LIMIT 3");

// FETCH REVIEWS
$reviewsQuery = mysqli_query($conn,
    "SELECT *
     FROM reviews
     WHERE vendor_id='$vendor_id'
     ORDER BY id DESC
     LIMIT 1");

$review = mysqli_fetch_assoc($reviewsQuery);

$highlightQuery = mysqli_query($conn,
  "SELECT id, image_path
   FROM vendor_highlights
   WHERE vendor_id='$vendor_id'
   ORDER BY uploaded_at DESC, id DESC
   LIMIT 5");

if (!$highlightQuery) {
    die("Highlight Query Error: " . mysqli_error($conn));
}

$highlightMessages = [
    'uploaded' => 'Highlight uploaded successfully.',
    'missing' => 'Please choose an image before uploading.',
    'error' => 'The highlight upload did not complete. Please try again.',
    'large' => 'Highlight image must be 5MB or smaller.',
    'type' => 'Please upload a JPG, PNG, WEBP, or GIF image.',
    'save' => 'Could not save the highlight image. Please try again.',
];

$highlightNotice = '';
$highlightNoticeClass = 'success';

if (isset($_GET['highlight'], $highlightMessages[$_GET['highlight']])) {
    $highlightNotice = $highlightMessages[$_GET['highlight']];

    if ($_GET['highlight'] !== 'uploaded') {
        $highlightNoticeClass = 'error';
    }
}

$profileUpdateMessages = [
    '1' => 'Profile updated successfully.',
    'image-error' => 'Profile image upload failed. Please choose the image again.',
    'image-large' => 'Profile image must be 5MB or smaller.',
    'image-type' => 'Please upload a JPG, PNG, WEBP, or GIF image.',
    'image-save' => 'Could not save the profile image. Please try again.',
    'error' => 'Could not update your profile. Please try again.'
];

$profileUpdateNotice = '';
$profileUpdateClass = 'success';

if (isset($_GET['updated'], $profileUpdateMessages[$_GET['updated']])) {
    $profileUpdateNotice = $profileUpdateMessages[$_GET['updated']];

    if ($_GET['updated'] !== '1') {
        $profileUpdateClass = 'error';
    }
}

// AVERAGE RATING
$ratingQuery = mysqli_query($conn,
    "SELECT
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews
     FROM reviews
     WHERE vendor_id='$vendor_id'");

$ratingRow = mysqli_fetch_assoc($ratingQuery);

$avgRating = round($ratingRow['avg_rating'] ?? 0, 1);
$totalReviews = $ratingRow['total_reviews'] ?? 0;
$vendorAddress = trim((string) ($vendor['address'] ?? ''));
$googleMapsUrl = $vendorAddress !== ''
    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($vendorAddress)
    : '';
$profileImageSrc = listingImageSrc($vendor['profile_image'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <title>Planora Profile</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <!-- CSS -->
  <link rel="stylesheet" href="../css/profile.css?v=8">

  <style>
    .highlight-item{ position:relative; display:inline-block; }
    .highlight-item img{ display:block; width:100%; height:100%; object-fit:cover; }
    .delete-highlight-form{ position:absolute; top:6px; right:6px; }
    .delete-highlight-btn{ background:rgba(0,0,0,0.6); border:0; color:#fff; padding:6px 8px; border-radius:6px; cursor:pointer; }
  </style>

  <!-- GOOGLE FONT -->
  <link rel="preconnect" href="https://fonts.googleapis.com">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- FONT AWESOME -->
  <link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

</head>

<body class="<?php echo $is_owner ? 'owner-profile' : 'public-profile'; ?>">

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

        <a href="bookings.php" class="menu-item">
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

        <a href="profile.php" class="menu-item active">
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

      <img src="<?php echo htmlspecialchars($profileImageSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="">

      <div class="vendor-info">
        <h4><?php echo htmlspecialchars($vendor['full_name'] ?? 'Vendor'); ?></h4>
        <p><?php echo $allDocumentsVerified ? 'Verified Vendor' : 'Not Yet Verified'; ?></p>
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

    <!-- PROFILE HERO -->

    <?php if ($profileUpdateNotice !== ''): ?>
      <p class="profile-update-notice <?php echo htmlspecialchars($profileUpdateClass, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($profileUpdateNotice, ENT_QUOTES, 'UTF-8'); ?>
      </p>
    <?php endif; ?>

    <section class="profile-hero">

      <div class="profile-left">

        <!-- BUSINESS IMAGE -->

        <div class="business-image">

          <img src="<?php echo htmlspecialchars($profileImageSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($vendor['full_name'] ?? 'Vendor profile image', ENT_QUOTES, 'UTF-8'); ?>">

          <?php if ($allDocumentsVerified): ?>
          <div class="verified-badge">

            <i class="fa-solid fa-check"></i>

          </div>
          <?php endif; ?>

        </div>

        <!-- INFO -->

        <div class="profile-info">

          <h1>
            <?php
              echo htmlspecialchars(
                !$is_owner && !empty($vendor['business_name'])
                  ? $vendor['business_name']
                  : ($vendor['full_name'] ?? 'Your full name'),
                ENT_QUOTES,
                'UTF-8'
              );
            ?>
          </h1>

          <?php if ($allDocumentsVerified): ?>
          <div class="verified-label">

            <i class="fa-solid fa-circle-check"></i>

            Verified Vendor

          </div>
          <?php endif; ?>

          <p class="services-line">
            <?php
            // Build services line from listings categories
            $categoriesQuery = mysqli_query($conn,
                "SELECT DISTINCT category FROM listings WHERE vendor_id='$vendor_id'");
            $categories = [];
            while($cat = mysqli_fetch_assoc($categoriesQuery)){
                $categories[] = $cat['category'];
            }
            echo !empty($categories)
                ? htmlspecialchars(implode(' • ', $categories))
                : 'Add listings to showcase your services';
            ?>
          </p>

          <p class="location">
            <i class="fa-solid fa-location-dot"></i>
            <?php echo htmlspecialchars($vendor['address'] ?? 'Location not set'); ?>
          </p>

          <?php if ($googleMapsUrl): ?>
            <a class="profile-map-link" href="<?php echo htmlspecialchars($googleMapsUrl, ENT_QUOTES); ?>" target="_blank" rel="noopener">
              <i class="fa-solid fa-map-location-dot"></i>
              Open address in Google Maps
            </a>
          <?php endif; ?>

          <div class="rating-line">

            <div class="stars">
              <?php
              $fullStars = floor($avgRating);
              for($i = 0; $i < $fullStars; $i++) echo '⭐';
              if($fullStars == 0) echo '☆☆☆☆☆';
              ?>
            </div>

            <span><?php echo $avgRating; ?> (<?php echo $totalReviews; ?> reviews)</span>

          </div>

          <!-- BUTTONS -->

          <?php if (!empty($is_owner)): ?>
          <div class="profile-buttons">

            <button class="edit-btn" id="editProfileBtn">
              <i class="fa-solid fa-pen"></i>
              Edit Profile
            </button>

            <!--<button class="view-btn" id="viewPublicBtn" data-public-url="profile.php?id=<?php echo (int) $vendor_id; ?>">
              <i class="fa-solid fa-eye"></i>
              View Public Profile
            </button>-->

            <button class="share-btn" id="shareProfileBtn">
              <i class="fa-solid fa-share-nodes"></i>
              Share Profile
            </button>

          </div>
          <?php endif; ?>

        </div>

      </div>

      <!-- STATS -->

      <?php if (!empty($is_owner)): ?>
      <div class="stats-grid">

        <div class="stat-box">

          <i class="fa-solid fa-circle-check purple"></i>

          <div>

            <h4>Completed Events</h4>

            <h2><?php echo $completedBookings; ?></h2>

            <p><?php echo $completionRate; ?>% completion rate</p>

          </div>

        </div>

        <div class="stat-box">

          <i class="fa-regular fa-clock yellow"></i>

          <div>

            <h4>Member Since</h4>

            <h2><?php echo $memberSince; ?></h2>

            <p>Active vendor</p>

          </div>

        </div>

      </div>
      <?php endif; ?>

    </section>

    <!-- CONTENT GRID -->

    <section class="content-grid <?php echo $is_owner ? '' : 'public-content-grid'; ?>">

      <!-- ABOUT -->

      <div class="card about-card">

        <h3>About Us</h3>

        <p class="about-text">
          <?php echo !empty($vendor['about'])
              ? nl2br(htmlspecialchars($vendor['about']))
              : 'Tell clients about your business. Click "Edit Profile" to add your description.'; ?>
        </p>

      </div>

      <!-- SERVICES -->

      <div class="card services-card">

        <div class="card-header">

          <h3>Our Services</h3>

          <?php if ($is_owner): ?>
            <a href="listings.php">View All Services</a>
          <?php endif; ?>

        </div>

        <?php if(mysqli_num_rows($listingsQuery) > 0): ?>
            <?php while($listing = mysqli_fetch_assoc($listingsQuery)): ?>
                <div class="service-item">

                  <div class="service-left">

                    <img
                      src="<?php echo htmlspecialchars(listingImageSrc($listing['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                      onerror="this.onerror=null; this.src='../image/planoraLogo.jpg';">

                    <div>

                      <h4><?php echo htmlspecialchars($listing['title']); ?></h4>

                      <span class="package"><?php echo htmlspecialchars($listing['type']); ?></span>

                    </div>

                  </div>

                  <h5>₱<?php echo number_format($listing['price']); ?></h5>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:#888; padding:20px 0;">No services listed yet. Add listings to showcase your work.</p>
        <?php endif; ?>

      </div>

      <?php if ($is_owner): ?>
      <!-- VERIFICATION -->

      <div class="card verification-card">

        <h3>Vendor Verification</h3>

        <ul>

          <?php foreach ($documentStatuses as $documentStatus): ?>
            <?php
              $status = $documentStatus['status'];
              $statusClass = str_replace(' ', '-', $status);
            ?>
            <li>
              <span><?php echo htmlspecialchars($documentStatus['label'], ENT_QUOTES, 'UTF-8'); ?></span>
              <strong class="verification-status <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars(ucwords($status), ENT_QUOTES, 'UTF-8'); ?>
              </strong>
            </li>
          <?php endforeach; ?>

        </ul>

        <div class="verified-box">

          <i class="fa-solid <?php echo htmlspecialchars($verificationSummaryIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>

          <div>

            <h4><?php echo htmlspecialchars($verificationSummaryTitle, ENT_QUOTES, 'UTF-8'); ?></h4>

            <p>
              <?php echo htmlspecialchars($verificationSummaryText, ENT_QUOTES, 'UTF-8'); ?>
            </p>

          </div>

        </div>

      </div>
      <?php endif; ?>

    </section>

    <!-- BOTTOM SECTION -->

    <section class="bottom-grid">

      <!-- GALLERY -->

      <div class="card gallery-card">

        <div class="card-header">

          <h3>Portfolio Highlights</h3>

          <?php if ($canManageHighlights): ?>
          <form class="highlight-upload-form" id="highlightUploadForm" action="../php/uploadVendorHighlight.php" method="POST" enctype="multipart/form-data">
            <label class="highlight-upload-btn" for="highlightImageInput">
              <i class="fa-solid fa-plus"></i>
              Add Highlights
            </label>
            <input id="highlightImageInput" type="file" name="highlight_image" accept="image/*">
          </form>
          <?php endif; ?>

        </div>

        <?php if ($highlightNotice !== ''): ?>
          <p class="highlight-notice <?php echo htmlspecialchars($highlightNoticeClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($highlightNotice, ENT_QUOTES, 'UTF-8'); ?>
          </p>
        <?php endif; ?>

        <div class="gallery-grid">

          <?php if(mysqli_num_rows($highlightQuery) > 0): ?>
              <?php while($highlight = mysqli_fetch_assoc($highlightQuery)): ?>
                  <div class="highlight-item">
                    <img
                      src="<?php echo htmlspecialchars(listingImageSrc($highlight['image_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                      onerror="this.onerror=null; this.src='../image/planoraLogo.jpg';">
                    <?php if ($canManageHighlights): ?>
                      <form class="delete-highlight-form" method="POST" action="../php/deleteVendorHighlight.php" onsubmit="return confirm('Delete this highlight?');">
                        <input type="hidden" name="highlight_id" value="<?php echo (int) $highlight['id']; ?>">
                        <button type="submit" class="delete-highlight-btn" title="Delete highlight">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
              <?php endwhile; ?>
          <?php else: ?>
              <p style="color:#888; grid-column:1/-1; padding:30px 0; text-align:center;">
                No portfolio images yet. Add highlights to build your gallery.
              </p>
          <?php endif; ?>

        </div>

      </div>

      <!-- REVIEWS -->

      <div class="card review-card">

        <div class="card-header">

          <h3>Customer Reviews</h3>

          <?php if ($is_owner): ?>
            <a href="reviews.php">View All Reviews</a>
          <?php endif; ?>

        </div>

        <div class="review-score">

          <h1><?php echo $avgRating > 0 ? $avgRating : '—'; ?></h1>

          <div>

            <?php
            $fullStars = floor($avgRating);
            for($i = 0; $i < $fullStars; $i++) echo '⭐';
            if($fullStars == 0) echo '☆☆☆☆☆';
            ?>

            <p>(<?php echo $totalReviews; ?> reviews)</p>

          </div>

        </div>

        <?php if($review): ?>
        <div class="review-user">

          <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($review['client_name'] ?? 'Client'); ?>&background=f5c25c&color=111">

          <div>

            <h4><?php echo htmlspecialchars($review['client_name'] ?? 'Client'); ?></h4>

            <span><?php echo date('M d, Y', strtotime($review['created_at'] ?? 'now')); ?></span>

            <p>
              <?php echo htmlspecialchars($review['comment'] ?? ''); ?>
            </p>

          </div>

        </div>
        <?php else: ?>
        <p style="color:#888; padding:10px 0;">No reviews yet.</p>
        <?php endif; ?>

      </div>

    </section>

  </main>

</div>

<!-- EDIT PROFILE MODAL -->
<div class="edit-modal" id="editModal">

  <div class="modal-overlay" id="editModalOverlay"></div>

  <div class="modal-box">

    <button class="modal-close" id="editModalClose">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="modal-header">
      <i class="fa-solid fa-user-pen"></i>
      <div>
        <h2>Edit Profile</h2>
        <p>Update your business information</p>
      </div>
    </div>

    <form id="editProfileForm" action="../php/updateProfile.php" method="POST" enctype="multipart/form-data">

      <div class="form-row">

        <div class="form-field">
          <label>Full Name</label>
          <input type="text" name="full_name"
                 value="<?php echo htmlspecialchars($vendor['full_name'] ?? ''); ?>"
                 required>
        </div>

        <div class="form-field">
          <label>Business Name</label>
          <input type="text" name="business_name"
                 value="<?php echo htmlspecialchars($vendor['business_name'] ?? ''); ?>"
                 required>
        </div>

      </div>

      <div class="form-row">

        <div class="form-field">
          <label>Email</label>
          <input type="email" name="email"
                 value="<?php echo htmlspecialchars($vendor['email'] ?? ''); ?>"
                 required>
        </div>

        <div class="form-field">
          <label>Phone</label>
          <input type="text" name="phone"
                 value="<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>">
        </div>

      </div>

      <div class="form-field">
        <label>Address</label>
        <input type="text" name="address"
               value="<?php echo htmlspecialchars($vendor['address'] ?? ''); ?>">
      </div>

      <div class="form-field">
        <label>About Your Business</label>
        <textarea name="about" rows="4"
                  placeholder="Tell clients about your services, experience, and what makes you unique..."><?php echo htmlspecialchars($vendor['about'] ?? ''); ?></textarea>
      </div>

      <div class="form-field">
        <label>Profile Image</label>
        <div class="file-upload">
          <img
            class="profile-upload-preview"
            id="profileUploadPreview"
            src="<?php echo htmlspecialchars($profileImageSrc, ENT_QUOTES, 'UTF-8'); ?>"
            alt="Current profile image">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <span>Click to upload image</span>
          <input type="file" name="profile_image" accept="image/*" id="profileImageInput">
        </div>
      </div>

      <div class="modal-actions">

        <button type="button" class="cancel-btn" id="editCancelBtn">Cancel</button>

        <button type="submit" class="save-btn">
          <i class="fa-solid fa-check"></i>
          Save Changes
        </button>

      </div>

    </form>

  </div>

</div>

<!-- SHARE PROFILE MODAL -->
<div class="share-modal" id="shareModal">

  <div class="modal-overlay" id="shareModalOverlay"></div>

  <div class="share-box">

    <button class="modal-close" id="shareModalClose">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <h2><i class="fa-solid fa-share-nodes"></i> Share Your Profile</h2>

    <p>Copy the link below to share your vendor profile with clients.</p>

    <div class="share-link-row">
      <input type="text" id="profileLink" readonly
             value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/PlanoraProject/vendor/profile.php?id=' . $vendor_id; ?>">
      <button id="copyLinkBtn" class="copy-btn">
        <i class="fa-regular fa-copy"></i>
        Copy
      </button>
    </div>

    <div class="share-socials">

      <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/PlanoraProject/vendor/profile.php?id=' . $vendor_id); ?>"
         target="_blank" class="social-btn fb">
        <i class="fa-brands fa-facebook-f"></i>
        Facebook
      </a>

      <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/PlanoraProject/vendor/profile.php?id=' . $vendor_id); ?>&text=Check out my vendor profile on Planora!"
         target="_blank" class="social-btn tw">
        <i class="fa-brands fa-x-twitter"></i>
        Twitter
      </a>

      <a href="https://wa.me/?text=<?php echo urlencode('Check out my vendor profile on Planora: http://' . $_SERVER['HTTP_HOST'] . '/PlanoraProject/vendor/profile.php?id=' . $vendor_id); ?>"
         target="_blank" class="social-btn wa">
        <i class="fa-brands fa-whatsapp"></i>
        WhatsApp
      </a>

    </div>

  </div>

</div>

<!-- PUBLIC PROFILE PREVIEW MODAL -->
<div class="preview-modal" id="previewModal">

  <div class="modal-overlay" id="previewModalOverlay"></div>

  <div class="preview-box">

    <button class="modal-close" id="previewModalClose">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="preview-header">
      <i class="fa-solid fa-eye"></i>
      <h2>Public Profile Preview</h2>
      <p>This is how clients see your profile</p>
    </div>

    <div class="preview-content">

      <div class="preview-hero">

        <img src="<?php echo htmlspecialchars($profileImageSrc, ENT_QUOTES, 'UTF-8'); ?>"
             class="preview-avatar">

        <div>
          <h1><?php echo htmlspecialchars($vendor['business_name'] ?? 'Your Business Name'); ?></h1>

          <?php if ($allDocumentsVerified): ?>
            <div class="preview-verified">
              <i class="fa-solid fa-circle-check"></i>
              Verified Vendor
            </div>
          <?php endif; ?>

          <p class="preview-location">
            <i class="fa-solid fa-location-dot"></i>
            <?php echo htmlspecialchars($vendor['address'] ?? 'Location not set'); ?>
          </p>

          <div class="preview-rating">
            ⭐ <?php echo $avgRating; ?> (<?php echo $totalReviews; ?> reviews)
          </div>
        </div>

      </div>

      <div class="preview-stats">

        <div>
          <h3><?php echo $totalBookings; ?></h3>
          <p>Bookings</p>
        </div>

        <div>
          <h3><?php echo $completedBookings; ?></h3>
          <p>Completed</p>
        </div>

        <div>
          <h3>₱<?php echo number_format($totalEarnings, 0); ?></h3>
          <p>Earned</p>
        </div>

      </div>

      <div class="preview-about">
        <h3>About</h3>
        <p><?php echo !empty($vendor['about'])
            ? nl2br(htmlspecialchars($vendor['about']))
            : 'No description provided yet.'; ?></p>
      </div>

    </div>

  </div>

</div>

<!-- TOAST NOTIFICATION -->
<div class="toast" id="toast">
  <i class="fa-solid fa-check-circle"></i>
  <span id="toastMessage">Action completed!</span>
</div>

<script>

  // DROPDOWN
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

  // TOAST
  function showToast(message){
    const toast = document.getElementById("toast");
    document.getElementById("toastMessage").textContent = message;
    toast.classList.add("show");
    setTimeout(() => toast.classList.remove("show"), 3000);
  }

  // EDIT PROFILE MODAL
  const editBtn = document.getElementById("editProfileBtn");
  const editModal = document.getElementById("editModal");
  const editClose = document.getElementById("editModalClose");
  const editOverlay = document.getElementById("editModalOverlay");
  const editCancel = document.getElementById("editCancelBtn");
  const viewPublicBtn = document.getElementById("viewPublicBtn");

  if (viewPublicBtn) {
    viewPublicBtn.addEventListener("click", () => {
      const publicUrl = viewPublicBtn.getAttribute("data-public-url");

      if (publicUrl) {
        window.open(publicUrl, "_blank", "noopener");
      }
    });
  }

  if (editBtn) {
    editBtn.addEventListener("click", () => {
      editModal.classList.add("active");
      document.body.style.overflow = "hidden";
    });

    function closeEditModal(){
      editModal.classList.remove("active");
      document.body.style.overflow = "";
    }

    editClose.addEventListener("click", closeEditModal);
    editOverlay.addEventListener("click", closeEditModal);
    editCancel.addEventListener("click", closeEditModal);
  }

  // FILE UPLOAD CLICK
  const fileUpload = document.querySelector(".file-upload");
  const fileInput = document.getElementById("profileImageInput");
  const profileUploadPreview = document.getElementById("profileUploadPreview");

  if(fileUpload && fileInput){
    fileUpload.addEventListener("click", (event) => {
      if (event.target !== fileInput) {
        fileInput.click();
      }
    });

    fileInput.addEventListener("change", function(){
      if(this.files && this.files[0]){
        if (profileUploadPreview) {
          profileUploadPreview.src = URL.createObjectURL(this.files[0]);
        }
        fileUpload.querySelector("span").textContent = this.files[0].name;
      }
    });
  }

  const highlightUploadForm = document.getElementById("highlightUploadForm");
  const highlightImageInput = document.getElementById("highlightImageInput");
  const highlightUploadBtn = document.querySelector(".highlight-upload-btn");

  if(highlightUploadForm && highlightImageInput){
    highlightImageInput.addEventListener("change", function(){
      if(this.files && this.files[0]){
        if(highlightUploadBtn){
          highlightUploadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading';
        }

        highlightUploadForm.submit();
      }
    });
  }

  // SHARE PROFILE MODAL
  const shareBtn = document.getElementById("shareProfileBtn");
  const shareModal = document.getElementById("shareModal");
  const shareClose = document.getElementById("shareModalClose");
  const shareOverlay = document.getElementById("shareModalOverlay");
  const copyLinkBtn = document.getElementById("copyLinkBtn");

  if (shareBtn) {
    shareBtn.addEventListener("click", () => {
      shareModal.classList.add("active");
      document.body.style.overflow = "hidden";
    });

    function closeShareModal(){
      shareModal.classList.remove("active");
      document.body.style.overflow = "";
    }

    shareClose.addEventListener("click", closeShareModal);
    shareOverlay.addEventListener("click", closeShareModal);

    if (copyLinkBtn) {
      copyLinkBtn.addEventListener("click", async () => {
        const profileLink = document.getElementById("profileLink");

        try{
          await navigator.clipboard.writeText(profileLink.value);
          showToast("Profile link copied!");
        } catch(error){
          profileLink.select();
          document.execCommand("copy");
          showToast("Profile link copied!");
        }
      });
    }
  }

  window.addEventListener("keydown", (e) => {
    if(e.key === "Escape" && shareModal.classList.contains("active")){
      closeShareModal();
    }
  });
</script>

<script src="../javascript/profile.js?v=7"></script>

<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
