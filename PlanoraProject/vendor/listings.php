<?php

// UNCOMMENT THE LINE BELOW TO SHOW TEMPORARILY UNAVAILABLE MESSAGE
//header("Location: temporaryUnavailable.php?page=listings"); exit();

session_start();

include '../php/connect.php';
include '../php/vendorProfileImage.php';
include '../php/vendorVerification.php';

$vendor_id = (int) $_SESSION['vendor_id'];
$sidebarProfileImage = vendorProfileImagePath($conn, $vendor_id);
$vendorVerificationLabel = vendorVerificationLabel($conn, $vendor_id);

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function listingImageSrc($imagePath) {
    $imagePath = trim(str_replace('\\', '/', (string) ($imagePath ?? '')));

    if ($imagePath === '') {
        return '../image/planoraLogo.jpg';
    }

    $imagePath = preg_replace('#^/?PlanoraProject/#i', '', $imagePath);

    if (preg_match('~uploads/listings/[^?#]+~i', $imagePath, $match)) {
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

// FILTER TYPE
$typeFilter = "";

if(isset($_GET['type'])){

    $type = $_GET['type'];

    $type = strtolower($type);

    if($type == "package"){

        $typeFilter = "AND type='package'";

    }

    elseif($type == "ala carte"){

        $typeFilter = "AND type='ala carte'";

    }

}

// FETCH LISTINGS
$sql = "SELECT * FROM listings

WHERE vendor_id='$vendor_id'

$typeFilter

ORDER BY listing_id DESC";

$result = mysqli_query($conn, $sql);



// PACKAGE COUNT
$packageQuery = mysqli_query($conn,

"SELECT COUNT(*) as total
FROM listings

WHERE vendor_id='$vendor_id'
AND type='package'");

$packageCount = mysqli_fetch_assoc($packageQuery)['total'];



// ALA CARTE COUNT
$alaCarteQuery = mysqli_query($conn,

"SELECT COUNT(*) as total
FROM listings

WHERE vendor_id='$vendor_id'
AND type='ala carte'");

$alaCarteCount = mysqli_fetch_assoc($alaCarteQuery)['total'];



// TOTAL LISTINGS
$totalQuery = mysqli_query($conn,

"SELECT COUNT(*) as total
FROM listings

WHERE vendor_id='$vendor_id'");

$totalListings = mysqli_fetch_assoc($totalQuery)['total'];



// ACTIVE LISTINGS
$activeQuery = mysqli_query($conn,

"SELECT COUNT(*) as total
FROM listings

WHERE vendor_id='$vendor_id'
AND status='active'");

$activeListings = mysqli_fetch_assoc($activeQuery)['total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Planora - My Listing</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <!-- CSS -->
  <link rel="stylesheet" href="../css/listings.css?v=5"/>

  <!-- FONT AWESOME -->
  <link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

  <!-- GOOGLE FONT -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>

<div class="dashboard-container">

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

        <a href="listings.php" class="menu-item active">
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

      <img src="<?php echo htmlspecialchars($sidebarProfileImage, ENT_QUOTES, 'UTF-8'); ?>" alt="">

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
        <h1>My Listing</h1>
        <p><?php echo $totalListings; ?> total listings &bull; <span><?php echo $activeListings; ?> active</span></p>
      </div>

      <!-- ADD LISTING BUTTON -->
      <button id="openModalBtn" class="add-listing-btn">
          + Add New Listing
      </button>

      <!-- MODAL -->
      <div class="listing-modal" id="listingModal">

          <div class="modal-overlay"></div>

          <div class="modal-container">

              <!-- CLOSE -->
              <button class="close-modal" id="closeModalBtn">
                  <i class="fa-solid fa-xmark"></i>
              </button>

              <!-- LEFT PANEL -->
              <div class="modal-sidebar">

                  <div class="sidebar-logo">

                      <img src="../image/planoraLogo.jpg" alt="">

                      <div>
                          <h2>PLANORA</h2>
                          <span>WHERE GREAT EVENTS BEGIN</span>
                      </div>

                  </div>

                  <h1>Add New Listing</h1>

                  <p>
                      Create your listing and get discovered by more clients.
                  </p>

                  <div class="stepper">

                      <div class="step active">
                          <div class="circle">1</div>

                          <div>
                              <h4>Listing Details</h4>
                              <p>Basic information about your service</p>
                          </div>
                      </div>

                      <div class="step">
                          <div class="circle">2</div>

                          <div>
                              <h4>Availability</h4>
                              <p>Set your availability and service areas</p>
                          </div>
                      </div>

                      <div class="step">
                          <div class="circle">3</div>

                          <div>
                              <h4>Publish</h4>
                              <p>Review your listing and publish</p>
                          </div>
                      </div>

                  </div>

                  <div class="modal-help">
                      <i class="fa-solid fa-headset"></i>
                      <div>
                          <h4>Need Help?</h4>
                          <p>We're here to help you set up your listing.</p>
                      </div>
                  </div>

              </div>

              <!-- RIGHT PANEL -->
              <div class="modal-content">

                  <form id="listingForm" action="../php/addListing.php" method="POST" enctype="multipart/form-data">

                      <!-- STEP 1 -->
                      <div class="form-step active">

                          <div class="step-header">

                              <div class="icon-box">
                                  <i class="fa-regular fa-rectangle-list"></i>
                              </div>

                              <div>
                                  <h2>Listing Details</h2>

                                  <p>
                                      Tell us more about your business and the services you offer.
                                  </p>
                              </div>

                          </div>

                          <div class="form-grid listing-details-grid">

                              <div class="form-group">
                                  <label for="title">Listing Title <span>*</span></label>

                                  <input id="title" type="text" name="title" placeholder="e.g. Elegant Wedding Package" required>

                                  <small>Choose a clear title that describes your service.</small>
                              </div>

                              <div class="form-group">
                                  <label>Category <span>*</span></label>

                                  <div class="category-options">

                                      <button type="button" class="category-card active" data-type="ala carte">
                                          <i class="fa-regular fa-gem"></i>

                                          <div>
                                              <h4>Ala Carte Listing</h4>
                                              <p>Individual services or add-ons</p>
                                          </div>
                                      </button>

                                      <button type="button" class="category-card" data-type="package">
                                          <i class="fa-solid fa-briefcase"></i>

                                          <div>
                                              <h4>Package Listing</h4>
                                              <p>Complete packages or bundled services</p>
                                          </div>
                                      </button>

                                  </div>

                                  <small>Choose the category that best fits your listing.</small>
                              </div>

                              <input type="hidden" id="listingType" name="type" value="ala carte" required>

                              <div class="form-group">
                                  <label>Require Security Deposit? <span>*</span></label>

                                  <div class="segmented-choice">
                                      <label class="choice-card yes active">
                                          <input type="radio" name="requires_security_deposit" value="1" checked>
                                          <i class="fa-regular fa-circle-check"></i>
                                          Yes
                                      </label>

                                      <label class="choice-card no">
                                          <input type="radio" name="requires_security_deposit" value="0">
                                          <i class="fa-regular fa-circle-xmark"></i>
                                          No
                                      </label>
                                  </div>

                                  <small>A security deposit helps protect both you and your clients.</small>
                              </div>

                               <div class="form-group">
                                  <label>Require Logistics Fee? <span>*</span></label>

                                  <div class="segmented-choice">
                                      <label class="choice-card yes active">
                                          <input type="radio" name="requires_logistics_fee" value="1" checked>
                                          <i class="fa-regular fa-circle-check"></i>
                                          Yes
                                      </label>

                                      <label class="choice-card no">
                                          <input type="radio" name="requires_logistics_fee" value="0">
                                          <i class="fa-regular fa-circle-xmark"></i>
                                          No
                                      </label>
                                  </div>

                                  <small>Logistics fee covers travel, transportation, or setup cost.</small>
                              </div> 

                              <div class="form-group" id="securityAmountGroup">
                                  <label for="securityDepositAmount">Security Deposit Amount (PHP) <span>*</span></label>

                                  <div class="input-with-icon">
                                      <input id="securityDepositAmount" type="number" name="security_deposit_amount" min="0" step="0.01" placeholder="e.g. 5000" required>
                                      <i class="fa-solid fa-peso-sign"></i>
                                  </div>

                                  <small>Enter the amount you require as security deposit.</small>
                              </div>

                              <div class="form-group logo-group">
                                  <label for="logoUpload">Logo (Optional)</label>

                                  <label class="upload-box compact" for="logoUpload" data-upload-label="Upload your logo">
                                      <i class="fa-solid fa-cloud-arrow-up"></i>
                                      <strong>Upload your logo</strong>
                                      <span>PNG, JPG or SVG (Max. 2MB)</span>
                                      <input id="logoUpload" type="file" name="logo" accept=".png,.jpg,.jpeg,.svg,image/png,image/jpeg,image/svg+xml">
                                  </label>
                              </div>

                          </div>

                          <div class="form-group">
                              <label for="description">Short Description <span>*</span></label>

                              <textarea id="description" name="description" placeholder="Write a short summary about your service..." required></textarea>

                              <small>This will be shown to potential clients.</small>
                          </div>

                          <div class="form-group full">
                              <label>Service Highlight <em>(Choose all that apply)</em></label>

                              <div class="highlight-options">
                                  <label><input type="checkbox" name="service_highlights[]" value="weddings"><i class="fa-regular fa-gem"></i> Weddings</label>
                                  <label><input type="checkbox" name="service_highlights[]" value="birthdays"><i class="fa-solid fa-cake-candles"></i> Birthdays</label>
                                  <label><input type="checkbox" name="service_highlights[]" value="corporate"><i class="fa-solid fa-briefcase"></i> Corporate</label>
                                  <label><input type="checkbox" name="service_highlights[]" value="private events"><i class="fa-solid fa-people-group"></i> Private Events</label>
                                  <label><input type="checkbox" name="service_highlights[]" value="others"><i class="fa-regular fa-circle-dot"></i> Others</label>
                              </div>

                              <small>Select the events you typically cater to.</small>
                          </div>

                          <div class="button-group">

                              <button type="button" class="cancel-btn" data-close-modal>
                                  Cancel
                              </button>

                              <button type="button" class="next-btn">
                                  Next Step
                                  <i class="fa-solid fa-arrow-right"></i>
                              </button>

                          </div>

                      </div>

                      <!-- STEP 2 -->
                      <div class="form-step">

                          <div class="step-header">

                              <div class="icon-box">
                                  <i class="fa-solid fa-calendar-days"></i>
                              </div>

                              <div>
                                  <h2>Availability</h2>

                                  <p>
                                      Set your pricing, service schedule, and coverage details.
                                  </p>
                              </div>

                          </div>

                          <div class="form-grid">

                              <div class="form-group">
                                  <label for="price">Base Price (PHP) <span>*</span></label>

                                  <input id="price" type="number" name="price" min="0" step="0.01" placeholder="e.g. 25000" required>
                              </div>

                              <div class="form-group">
                                  <label for="status">Status <span>*</span></label>

                                  <select id="status" name="status" required>

                                      <option value="active">Active</option>
                                      <option value="inactive">Inactive</option>

                                  </select>
                              </div>

                          </div>

                          <div class="form-group">
                              <label>Available Days <span>*</span></label>

                              <div class="day-options">
                                  <label><input type="checkbox" name="available_days[]" value="Monday">Mon</label>
                                  <label><input type="checkbox" name="available_days[]" value="Tuesday">Tue</label>
                                  <label><input type="checkbox" name="available_days[]" value="Wednesday">Wed</label>
                                  <label><input type="checkbox" name="available_days[]" value="Thursday">Thu</label>
                                  <label><input type="checkbox" name="available_days[]" value="Friday">Fri</label>
                                  <label><input type="checkbox" name="available_days[]" value="Saturday">Sat</label>
                                  <label><input type="checkbox" name="available_days[]" value="Sunday">Sun</label>
                              </div>
                          </div>

                          <div class="form-grid">
                              <div class="form-group">
                                  <label for="serviceAreas">Service Areas <span>*</span></label>

                                  <textarea id="serviceAreas" name="service_areas" placeholder="e.g. Cebu City, Mandaue, Lapu-Lapu" required></textarea>
                              </div>

                              <div class="form-group">
                                  <label for="imageUpload">Listing Image <span>*</span></label>

                                  <label class="upload-box compact" for="imageUpload" data-upload-label="Upload listing image">
                                      <i class="fa-solid fa-image"></i>
                                      <strong>Upload listing image</strong>
                                      <span>PNG, JPG or WEBP (Max. 5MB)</span>
                                      <input id="imageUpload" type="file" name="image" accept="image/*" required>
                                  </label>
                              </div>
                          </div>

                          <div class="form-group">
                              <label for="fullDescription">Full Description</label>

                              <textarea id="fullDescription" name="full_description" placeholder="Add package inclusions, setup requirements, limits, or other details clients should know."></textarea>
                          </div>

                          <div class="button-group">

                              <button type="button" class="prev-btn">
                                  Back
                              </button>

                              <button type="button" class="next-btn">
                                  Next Step
                                  <i class="fa-solid fa-arrow-right"></i>
                              </button>

                          </div>

                      </div>

                      <!-- STEP 3 -->
                      <div class="form-step">

                          <div class="step-header">

                              <div class="icon-box">
                                  <i class="fa-solid fa-circle-check"></i>
                              </div>

                              <div>
                                  <h2>Publish</h2>

                                  <p>
                                      Final review before publishing.
                                  </p>
                              </div>

                          </div>

                          <div class="review-box">

                              <h3>Ready to Publish</h3>

                              <p>
                                  Please review your information before submitting.
                              </p>

                              <dl id="listingReview"></dl>

                          </div>

                          <div class="button-group">

                              <button type="button" class="prev-btn">
                                  Back
                              </button>

                              <button type="submit" class="publish-btn">
                                  Publish Listing
                              </button>

                          </div>

                      </div>


                  </form>

              </div>

          </div>

      </div>

    </div>

    <!-- STATS -->

    <div class="stats-grid">

      <div class="stat-card">
        <i class="fa-solid fa-gift"></i>

        <div>
          <h2><?php echo $packageCount; ?></h2>
          <p>Packages</p>
        </div>
      </div>

      <div class="stat-card">
        <i class="fa-solid fa-bell-concierge"></i>

        <div>
          <h2><?php echo $alaCarteCount; ?></h2>
          <p>Ala Carte Services</p>
        </div>
      </div>

      <div class="stat-card">
        <i class="fa-solid fa-list"></i>

        <div>
          <h2><?php echo $totalListings; ?></h2>
          <p>Total Listings</p>
        </div>
      </div>

      <div class="stat-card">
        <i class="fa-solid fa-circle-check"></i>

        <div>
          <h2><?php echo $activeListings; ?></h2>
          <p>Active Listings</p>
        </div>
      </div>

    </div>

    <!-- FILTERS -->

    <div class="listing-controls">

      <a href="listings.php?type=package"
        class="tab <?php
        if(!isset($_GET['type']) || strtolower($_GET['type'])=="package")
        echo 'active';
        ?>">

        Packages

      </a>

      <a href="listings.php?type=ala carte"
        class="tab <?php
        if(isset($_GET['type']) && strtolower($_GET['type'])=="ala carte")
        echo 'active';
        ?>">

        Ala Carte Services

      </a>

      <div class="controls-right">

        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search listing...">
        </div>

        <div class="status-filter">
          <select id="statusFilter" aria-label="Filter listings by status">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <i class="fa-solid fa-chevron-down"></i>
        </div>

        <button class="grid-btn active" type="button" data-view="grid" aria-label="Grid view">
          <i class="fa-solid fa-grip"></i>
        </button>

        <button class="grid-btn" type="button" data-view="list" aria-label="List view">
          <i class="fa-solid fa-bars"></i>
        </button>

      </div>

    </div>

    <!-- CARDS -->

    <div class="listing-grid">

    <?php

    if(mysqli_num_rows($result) > 0){

        while($listing = mysqli_fetch_assoc($result)){

    ?>

        <?php
            $imageSrc = listingImageSrc($listing['image'] ?? '');
            $statusClass = strtolower($listing['status']);
        ?>

        <div class="listing-card"
             data-title="<?php echo htmlspecialchars(strtolower($listing['title']), ENT_QUOTES); ?>"
             data-category="<?php echo htmlspecialchars(strtolower($listing['category']), ENT_QUOTES); ?>"
             data-status="<?php echo htmlspecialchars($statusClass, ENT_QUOTES); ?>">

            <div class="card-image">

                <img
                  src="<?php echo e($imageSrc); ?>"
                  alt="<?php echo e($listing['title']); ?>"
                  onerror="this.onerror=null; this.src='../image/planoraLogo.jpg';">

                <span class="tag">
                    <?php echo strtoupper($listing['type']); ?>
                </span>

                <span class="status <?php echo htmlspecialchars($statusClass, ENT_QUOTES); ?>">
                    <?php echo $listing['status']; ?>
                </span>

            </div>

            <div class="card-content">

                <h3>
                    <?php echo $listing['title']; ?>
                </h3>

                <span class="category">
                    <?php echo ucwords($listing['category']); ?>
                </span>

                <p>
                    <?php echo $listing['description']; ?>
                </p>

                <h2>
                    &#8369;<?php echo number_format((float) $listing['price'], 2); ?>
                </h2>

            </div>

            <div class="card-actions">

                <a href="../php/editListings.php?listing_id=<?php echo $listing['listing_id']; ?>"
                  class="edit-btn">

                    <i class="fa-solid fa-pen"></i>
                    Edit

                </a>

                <a href="../php/deleteListing.php?listing_id=<?php echo $listing['listing_id']; ?>"
                  class="delete-btn"

                  onclick="return confirm('Delete this listing?')">

                    <i class="fa-solid fa-trash"></i>
                    Delete

                </a>

                <button type="button" class="more-btn" aria-label="More actions">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>

            </div>

        </div>

    <?php

        }

    }else{

        echo "<h2 class='empty-listings'>No Listings Found</h2>";

    }

    ?>

    </div>

  </main>

</div>

<script src="../javascript/listings.js?v=4"></script>
<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
