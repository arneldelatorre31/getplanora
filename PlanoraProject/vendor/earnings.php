<?php

// UNCOMMENT THE LINE BELOW TO SHOW TEMPORARILY UNAVAILABLE MESSAGE
header("Location: temporaryUnavailable.php?page=earnings"); exit();

session_start();
$current_year = date("Y");

if (!isset($_SESSION['vendor_id'])) {
    header("Location: index.php");
    exit();
}

include "../php/connect.php";
include "../php/vendorProfileImage.php";
include "../php/vendorVerification.php";

$vendor_id = $_SESSION['vendor_id'];
$sidebarProfileImage = vendorProfileImagePath($conn, $vendor_id);
$vendorVerificationLabel = vendorVerificationLabel($conn, (int) $vendor_id);

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return '&#8369;' . number_format((float) ($value ?? 0), 2);
}

function maskAccountNumber($value) {
    $digits = preg_replace('/\D+/', '', (string) ($value ?? ''));

    if ($digits === '') {
        return 'No account number';
    }

    return '&bull;&bull;&bull;&bull; ' . substr($digits, -4);
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS vendor_payout_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    bank_name VARCHAR(120) NOT NULL,
    account_name VARCHAR(120) NOT NULL,
    account_number VARCHAR(80) NOT NULL,
    account_type VARCHAR(40) NOT NULL DEFAULT 'Savings',
    is_primary TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX vendor_payout_methods_vendor_id_idx (vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    bank_name VARCHAR(120) NULL,
    account_name VARCHAR(120) NULL,
    account_number VARCHAR(80) NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    payout_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX payouts_vendor_id_idx (vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$payoutMethodMessage = '';
$payoutMethodError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_payout_method') {
    $bankName = trim((string) ($_POST['bank_name'] ?? ''));
    $accountName = trim((string) ($_POST['account_name'] ?? ''));
    $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
    $accountType = trim((string) ($_POST['account_type'] ?? 'Savings'));
    $validAccountTypes = ['Savings', 'Checking', 'Current'];

    if (!in_array($accountType, $validAccountTypes, true)) {
        $accountType = 'Savings';
    }

    if ($bankName === '' || $accountName === '' || $accountNumber === '') {
        $payoutMethodError = 'Please complete all required bank details.';
    } elseif (!preg_match('/^[0-9\-\s]{6,30}$/', $accountNumber)) {
        $payoutMethodError = 'Account number must be 6 to 30 digits.';
    } else {
        $conn->begin_transaction();

        try {
            $clearPrimaryStmt = $conn->prepare("UPDATE vendor_payout_methods SET is_primary = 0 WHERE vendor_id = ?");
            $clearPrimaryStmt->bind_param("i", $vendor_id);
            $clearPrimaryStmt->execute();

            $methodStmt = $conn->prepare("
                INSERT INTO vendor_payout_methods (vendor_id, bank_name, account_name, account_number, account_type, is_primary)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $methodStmt->bind_param("issss", $vendor_id, $bankName, $accountName, $accountNumber, $accountType);
            $methodStmt->execute();

            $conn->commit();
            $_SESSION['payout_method_message'] = 'Payout method saved.';
            header("Location: earnings.php#payout-info");
            exit();
        } catch (Throwable $error) {
            $conn->rollback();
            $payoutMethodError = 'Could not save payout method right now.';
        }
    }
}

if (isset($_SESSION['payout_method_message'])) {
    $payoutMethodMessage = $_SESSION['payout_method_message'];
    unset($_SESSION['payout_method_message']);
}

?>

<?php

$stmt = $conn->prepare("
SELECT COALESCE(SUM(total_price), 0) AS total_earnings
FROM bookings
WHERE vendor_id = ?
");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$total_earnings = (float) ($stmt->get_result()->fetch_assoc()['total_earnings'] ?? 0);

?>

<?php

$totalBookingsQuery = "SELECT COUNT(*) AS total_bookings
FROM bookings
WHERE vendor_id = '$vendor_id'
";

$totalBookingsResult = mysqli_query($conn, $totalBookingsQuery);

$totalBookingsData = mysqli_fetch_assoc($totalBookingsResult);

$total_bookings = $totalBookingsData['total_bookings'];

?>

<?php

$completedBookingsQuery = "SELECT COUNT(*) AS completed_bookings
FROM bookings
WHERE vendor_id = '$vendor_id'
AND (LOWER(status) = 'completed' OR LOWER(booking_status) = 'completed')
";

$completedBookingsResult = mysqli_query($conn, $completedBookingsQuery);

$completedBookingsData = mysqli_fetch_assoc($completedBookingsResult);

$completed_bookings = $completedBookingsData['completed_bookings'];

?>

<?php

$averageBookingQuery = "SELECT AVG(total_price) AS average_booking
FROM bookings
WHERE vendor_id = '$vendor_id'
";

$averageBookingResult = mysqli_query($conn, $averageBookingQuery);

$averageBookingData = mysqli_fetch_assoc($averageBookingResult);

$average_booking = $averageBookingData['average_booking'];

if (!$average_booking) {
    $average_booking = 0;
}

?>

<?php

$packageQuery = "SELECT SUM(total_price) AS package_earnings
FROM bookings
WHERE vendor_id = '$vendor_id'
AND LOWER(type) = 'package'
";

$packageResult = mysqli_query($conn, $packageQuery);

$packageData = mysqli_fetch_assoc($packageResult);

$package_earnings = $packageData['package_earnings'];

if (!$package_earnings) {
    $package_earnings = 0;
}

?>

<?php

$packageQuery = "SELECT SUM(total_price) AS package_total
                 FROM bookings
                 WHERE vendor_id = '$vendor_id'
                 AND LOWER(type) = 'package'";

$packageResult = mysqli_query($conn, $packageQuery);
$packageData = mysqli_fetch_assoc($packageResult);

$packageTotal = $packageData['package_total'] ?? 0;


$alaCarteQuery = "SELECT SUM(total_price) AS ala_total
                  FROM bookings
                  WHERE vendor_id = '$vendor_id'
                  AND LOWER(REPLACE(type, '-', ' ')) = 'ala carte'";

$alaCarteResult = mysqli_query($conn, $alaCarteQuery);
$alaCarteData = mysqli_fetch_assoc($alaCarteResult);

$alaCarteTotal = $alaCarteData['ala_total'] ?? 0;


?>

<?php
// Fetch primary payout method
$payoutMethodStmt = $conn->prepare("
SELECT *
FROM vendor_payout_methods
WHERE vendor_id = ?
ORDER BY is_primary DESC, updated_at DESC
LIMIT 1
");
$payoutMethodStmt->bind_param("i", $vendor_id);
$payoutMethodStmt->execute();
$payoutMethod = $payoutMethodStmt->get_result()->fetch_assoc();

// Fetch latest payout history row
$payoutQuery = "SELECT * FROM payouts
WHERE vendor_id = '$vendor_id'
ORDER BY payout_date DESC
LIMIT 1";

$payoutResult = mysqli_query($conn, $payoutQuery);
$payout = $payoutResult ? mysqli_fetch_assoc($payoutResult) : null;

?>

<?php

$totalPayoutQuery = "SELECT SUM(amount) AS total_payouts
FROM payouts
WHERE vendor_id = '$vendor_id'";

$totalPayoutResult = mysqli_query($conn, $totalPayoutQuery);
$totalPayoutData = $totalPayoutResult ? mysqli_fetch_assoc($totalPayoutResult) : ['total_payouts' => 0];

$totalPayouts = $totalPayoutData['total_payouts'] ?? 0;

$currentBalance = $total_earnings - $totalPayouts;

?>

<?php

$listingsQuery = "SELECT
    listings.title,
    listings.category,
    listings.type,

    COUNT(bookings.listing_id) AS total_bookings,

    SUM(bookings.total_price) AS total_earnings

FROM listings

LEFT JOIN bookings
ON listings.listing_id = bookings.listing_id

WHERE listings.vendor_id = '$vendor_id'

GROUP BY listings.listing_id
";

$listingsResult = mysqli_query($conn, $listingsQuery);

?>
<?php

$overviewYearQuery = "SELECT MAX(YEAR(event_date)) AS overview_year
FROM bookings
WHERE vendor_id = '$vendor_id'
AND event_date IS NOT NULL";

$overviewYearResult = mysqli_query($conn, $overviewYearQuery);
$overviewYearData = $overviewYearResult ? mysqli_fetch_assoc($overviewYearResult) : null;
$overview_year = (int) ($overviewYearData['overview_year'] ?? $current_year);

if ($overview_year <= 0) {
    $overview_year = (int) $current_year;
}

$monthlyOverview = [];

for ($month = 1; $month <= 12; $month++) {
    $monthlyOverview[$month] = [
        'label' => date('M Y', mktime(0, 0, 0, $month, 1, $overview_year)),
        'earnings' => 0,
        'bookings' => 0
    ];
}

$monthlyOverviewQuery = "SELECT
    MONTH(event_date) AS month_number,
    COUNT(*) AS booking_count,
    COALESCE(SUM(total_price), 0) AS earnings_total
FROM bookings
WHERE vendor_id = '$vendor_id'
AND YEAR(event_date) = '$overview_year'
GROUP BY MONTH(event_date)
ORDER BY MONTH(event_date)
";

$monthlyOverviewResult = mysqli_query($conn, $monthlyOverviewQuery);

if ($monthlyOverviewResult) {
    while ($overviewRow = mysqli_fetch_assoc($monthlyOverviewResult)) {
        $monthNumber = (int) $overviewRow['month_number'];

        if (isset($monthlyOverview[$monthNumber])) {
            $monthlyOverview[$monthNumber]['earnings'] = (float) $overviewRow['earnings_total'];
            $monthlyOverview[$monthNumber]['bookings'] = (int) $overviewRow['booking_count'];
        }
    }
}

$maxMonthlyEarnings = max(array_column($monthlyOverview, 'earnings'));
$maxMonthlyBookings = max(array_column($monthlyOverview, 'bookings'));

$yearlyOverview = [];
$startYear = $current_year - 4;

for ($year = $startYear; $year <= $current_year; $year++) {
    $yearlyOverview[$year] = [
        'label' => (string) $year,
        'earnings' => 0,
        'bookings' => 0
    ];
}

$yearlyOverviewQuery = "SELECT
    YEAR(event_date) AS year_number,
    COUNT(*) AS booking_count,
    COALESCE(SUM(total_price), 0) AS earnings_total
FROM bookings
WHERE vendor_id = '$vendor_id'
AND YEAR(event_date) BETWEEN '$startYear' AND '$current_year'
GROUP BY YEAR(event_date)
ORDER BY YEAR(event_date)
";

$yearlyOverviewResult = mysqli_query($conn, $yearlyOverviewQuery);

if ($yearlyOverviewResult) {
    while ($overviewRow = mysqli_fetch_assoc($yearlyOverviewResult)) {
        $yearNumber = (int) $overviewRow['year_number'];

        if (isset($yearlyOverview[$yearNumber])) {
            $yearlyOverview[$yearNumber]['earnings'] = (float) $overviewRow['earnings_total'];
            $yearlyOverview[$yearNumber]['bookings'] = (int) $overviewRow['booking_count'];
        }
    }
}

$overviewChartData = [
    'monthly' => array_values($monthlyOverview),
    'yearly' => array_values($yearlyOverview)
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Planora - Earnings</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <!-- CSS -->
  <link rel="stylesheet" href="../css/earnings.css?v=7">

  <!-- GOOGLE FONT -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- FONT AWESOME -->
  <link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

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

        <a href="earnings.php" class="menu-item active">
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

        <h1>Earnings</h1>

        <div class="tabs">

          <button class="tab active">Overview</button>
          <button class="tab">Payout Info</button>

        </div>

      </div>

      <button class="date-btn">
        <i class="fa-regular fa-calendar"></i>
        Jan 1, <?php echo $overview_year; ?> - Dec 31, <?php echo $overview_year; ?>
        <i class="fa-solid fa-chevron-down"></i>
      </button>

    </div>

    <!-- STATS -->

    <div class="stats-grid">

      <div class="stat-card green">

        <div>
          <p>Total Earnings</p>
          <h2><?php echo money($total_earnings); ?></h2>
          <span>&nearr; Completed booking revenue</span>
        </div>

        <div class="icon">
          <i class="fa-solid fa-wallet"></i>
        </div>

      </div>

      <div class="stat-card blue">

        <div>
          <p>Total Bookings</p>
          <h2><?php echo $total_bookings; ?></h2>
          <span>&nearr; All booking requests</span>
        </div>

        <div class="icon">
          <i class="fa-solid fa-calendar-check"></i>
        </div>

      </div>

      <div class="stat-card purple">

        <div>
          <p>Average Booking Value</p>
          <h2><?php echo money($average_booking); ?></h2>
          <span>&nearr; Completed bookings only</span>
        </div>

        <div class="icon">
          <i class="fa-solid fa-chart-simple"></i>
        </div>

      </div>

      <div class="stat-card green">

        <div>
          <p>Total Completed Bookings</p>
          <h2><?php echo $completed_bookings; ?></h2>
          <span><?php echo $total_bookings > 0 ? round(($completed_bookings / $total_bookings) * 100) : 0; ?>% of total bookings</span>
        </div>

        <div class="icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>

      </div>

    </div>

    <!-- CHART SECTION -->

    <div class="content-grid">

      <!-- LEFT -->

      <div class="chart-card">

        <div class="card-header">

          <h3>Earnings & Bookings Overview</h3>

          <div class="overview-filter">
            <select id="overviewPeriod" aria-label="Filter earnings overview">
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
            </select>
            <i class="fa-solid fa-chevron-down"></i>
          </div>

        </div>

        <!-- LEGEND -->

        <div class="legend">

          <button class="legend-item active" type="button" data-chart-metric="earnings">
            <span class="green-dot"></span>
            Earnings (&#8369;)
          </button>

          <button class="legend-item active" type="button" data-chart-metric="bookings">
            <span class="blue-dot"></span>
            Number of Bookings
          </button>

        </div>

        <!-- GRAPH -->

        <div class="graph" id="earningsOverviewGraph">
          <p class="empty-chart">No overview data yet.</p>
        </div>

      </div>

      <!-- RIGHT -->

      <div class="listing-type-card">

        <div class="listing-chart-container">
          <canvas id="listingPieChart"></canvas>
        </div>

        <div class="listing-stats">
            <div class="stat-item">
              <span class="dot package"></span>
                <div>
                  <h4>Package Listings</h4>
                  <p><?php echo money($packageTotal); ?></p>
                </div>
            </div>

            <div class="stat-item">
              <span class="dot ala"></span>
                <div>
                  <h4>Ala Carte Listings</h4>
                  <p><?php echo money($alaCarteTotal); ?></p>
                </div>
            </div>

            <div class="total-box">
              <h4>Total</h4>
              <h2><?php echo money($packageTotal + $alaCarteTotal); ?></h2>
            </div>
          </div>

      </div>

    </div>

    <!-- BOTTOM SECTION -->

    <div class="bottom-grid">

      <!-- TABLE -->

      <div class="table-card">

        <div class="card-header">
          <h3>Earnings by Listings</h3>
        </div>

        <table>

          <thead>

            <tr>
              <th>LISTING NAME</th>
              <th>TYPE OF SERVICE</th>
              <th>TYPE</th>
              <th>TOTAL BOOKINGS</th>
              <th>TOTAL EARNINGS</th>
            </tr>

          </thead>

          <tbody>
            
            <?php while($row = mysqli_fetch_assoc($listingsResult)) { ?>

            <tr>
              <td>
                <?php echo e($row['title']); ?>
              </td>
                <!-- SERVICE TYPE -->
              <td>
                <?php echo e($row['category']); ?>
              </td>

              <!-- TYPE -->
              <td>

                <?php if($row['type'] == 'package') { ?>

                  <span class="type-badge package-badge">
                      Package
                  </span>

                <?php } else { ?>

                  <span class="type-badge ala-badge">
                    Ala Carte
                  </span>

                <?php } ?>

                </td>

              <!-- BOOKINGS -->
              <td>
                <?php echo (int) $row['total_bookings']; ?>
              </td>

              <!-- EARNINGS -->
              <td>
                <?php echo money($row['total_earnings']); ?>
              </td>

            </tr>

            <?php } ?>

          </tbody>

        </table>

      </div>

      <!-- PAYOUT -->

      <div class="payout-card" id="payout-info">

        <h3>Payout Info</h3>

        <p class="payout-sub">
          Manage your payout methods and payout history.
        </p>

        <?php if ($payoutMethodMessage !== '') { ?>
          <div class="payout-alert success"><?php echo e($payoutMethodMessage); ?></div>
        <?php } ?>

        <?php if ($payoutMethodError !== '') { ?>
          <div class="payout-alert error"><?php echo e($payoutMethodError); ?></div>
        <?php } ?>

        <div class="balance-box">

          <div>
            <p>Current Balance</p>
            <h2><?php echo money($currentBalance); ?></h2>
          </div>

          <button class="request-btn" type="button" id="openPayoutMethodModal">
            <i class="fa-solid fa-plus"></i>
            Add Payout Method
          </button>

        </div>

        <!-- METHOD -->

        <div class="method-box <?php echo $payoutMethod ? '' : 'empty-method'; ?>">

          <div class="method-left">

            <div class="bank-icon">
              <i class="fa-solid fa-building-columns"></i>
            </div>

            <div>
              <?php if ($payoutMethod) { ?>
                <h4><?php echo e($payoutMethod['bank_name']); ?></h4>
                <p><?php echo maskAccountNumber($payoutMethod['account_number']); ?> &bull; <?php echo e($payoutMethod['account_type']); ?></p>
                <span><?php echo e($payoutMethod['account_name']); ?></span>
              <?php } else { ?>
                <h4>No payout method yet</h4>
                <p>Add bank details to receive payouts.</p>
                <span>Bank account required</span>
              <?php } ?>
            </div>

          </div>

          <?php if ($payoutMethod) { ?>
            <span class="primary-badge">Primary</span>
          <?php } ?>

        </div>

        <!-- RECENT -->

        <div class="recent-payout">

          <div class="recent-header">

            <h4>Recent Payouts</h4>

            <button class="view-all-btn">
              View All
            </button>

          </div>

          <?php if ($payout) { ?>
          <div class="recent-item">

            <div>
              <h5><?php echo !empty($payout['payout_date']) ? e(date("M j, Y", strtotime($payout['payout_date']))) : 'Date not set'; ?></h5>
              <p><?php echo $payoutMethod ? e($payoutMethod['bank_name']) . ' ' . maskAccountNumber($payoutMethod['account_number']) : 'Payout processed'; ?></p>
            </div>

            <span class="completed-status">
              <?php echo e($payout['status']); ?>
            </span>

            <h4><?php echo money($payout['amount']); ?></h4>

          </div>
          <?php } else { ?>
          <div class="recent-item empty-recent">
            <div>
              <h5>No payout history yet</h5>
              <p>Your completed payout requests will appear here.</p>
            </div>
          </div>
          <?php } ?>

        </div>

      </div>

    </div>

</main>

</div>

<div class="payout-modal-overlay" id="payoutMethodModal" aria-hidden="true">
  <div class="payout-modal" role="dialog" aria-modal="true" aria-labelledby="payoutMethodTitle">
    <button class="close-payout-modal" type="button" id="closePayoutMethodModal" aria-label="Close payout method form">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="payout-modal-header">
      <div class="modal-icon">
        <i class="fa-solid fa-building-columns"></i>
      </div>
      <div>
        <h2 id="payoutMethodTitle">Add Payout Method</h2>
        <p>Save the bank account where Planora should send your vendor payouts.</p>
      </div>
    </div>

    <form class="payout-form" method="POST" action="earnings.php#payout-info">
      <input type="hidden" name="action" value="save_payout_method">

      <label>
        <span>Bank Name</span>
        <input type="text" name="bank_name" placeholder="BDO, BPI, Metrobank..." required maxlength="120">
      </label>

      <label>
        <span>Account Holder Name</span>
        <input type="text" name="account_name" placeholder="Registered account name" required maxlength="120">
      </label>

      <label>
        <span>Account Number</span>
        <input type="text" name="account_number" placeholder="Enter account number" required maxlength="30" inputmode="numeric">
      </label>

      <label>
        <span>Account Type</span>
        <select name="account_type" required>
          <option value="Savings">Savings</option>
          <option value="Checking">Checking</option>
          <option value="Current">Current</option>
        </select>
      </label>

      <div class="form-actions">
        <button class="cancel-payout-btn" type="button" id="cancelPayoutMethod">Cancel</button>
        <button class="save-payout-btn" type="submit">
          <i class="fa-solid fa-floppy-disk"></i>
          Save Method
        </button>
      </div>
    </form>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

window.earningsOverviewData = <?php echo json_encode($overviewChartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;

const packageTotal = <?php echo $packageTotal ?? 0; ?>;
const alaCarteTotal = <?php echo $alaCarteTotal ?? 0; ?>;

const ctx = document.getElementById('listingPieChart');

new Chart(ctx, {
    type: 'doughnut',

    data: {
        labels: ['Package', 'Ala Carte'],

        datasets: [{
            data: [packageTotal, alaCarteTotal],

            backgroundColor: [
                '#f4b942',
                '#3b82f6'
            ],

            borderWidth: 0
        }]
    },

    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
    }
});

</script>

<script src="../javascript/earnings.js?v=7"></script>
<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
