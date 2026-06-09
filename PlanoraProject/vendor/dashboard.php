<?php

// UNCOMMENT THE LINE BELOW TO SHOW TEMPORARILY UNAVAILABLE MESSAGE
//header("Location: temporaryUnavailable.php?page=dashboard"); exit();

session_start();
$currentDate = date("F d, Y");

if (!isset($_SESSION['vendor_id'])) {

    header("Location: index.php");
    exit();

}

include("../php/connect.php");
include("../php/vendorProfileImage.php");
include("../php/vendorVerification.php");
$vendor_id = $_SESSION['vendor_id'];
$sidebarProfileImage = vendorProfileImagePath($conn, $vendor_id);
$vendorVerificationLabel = vendorVerificationLabel($conn, (int) $vendor_id);

?>

<?php

// SECURE VENDOR FETCH
$stmt = $conn->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$vendor = $stmt->get_result()->fetch_assoc();

// TOTAL BOOKINGS
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE vendor_id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['total'];

// PENDING BOOKINGS
$stmt = $conn->prepare("SELECT COUNT(*) AS pending FROM bookings WHERE vendor_id = ? AND booking_status = 'pending'");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$pendingBookings = $stmt->get_result()->fetch_assoc()['pending'];

// COMPLETED EVENTS
$stmt = $conn->prepare("SELECT COUNT(*) AS completed FROM bookings WHERE vendor_id = ? AND status = 'completed'");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$completedEvents = $stmt->get_result()->fetch_assoc()['completed'];

// TOTAL EARNINGS
$stmt = $conn->prepare("SELECT SUM(total_price) AS earnings FROM bookings WHERE vendor_id = ? AND payment_status = 'paid'");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$totalEarnings = $stmt->get_result()->fetch_assoc()['earnings'] ?? 0;

/* =========================================
   CURRENT MONTH EARNINGS
========================================= */

$stmt = $conn->prepare("SELECT COALESCE(SUM(total_price),0) AS total FROM bookings WHERE vendor_id = ? AND payment_status = 'paid' AND status = 'completed' AND MONTH(event_date) = MONTH(CURRENT_DATE()) AND YEAR(event_date) = YEAR(CURRENT_DATE())");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();

$currentMonthEarnings =
$stmt->get_result()->fetch_assoc()['total'];


/* =========================================
   LAST MONTH EARNINGS
========================================= */

$stmt = $conn->prepare(" SELECT COALESCE(SUM(total_price),0) AS total
    FROM bookings
    WHERE vendor_id = ?
    AND payment_status = 'paid'
    AND status = 'completed'
    AND MONTH(event_date)=MONTH(DATE_SUB(CURRENT_DATE(),INTERVAL 1 MONTH))
    AND YEAR(event_date)=YEAR(DATE_SUB(CURRENT_DATE(),INTERVAL 1 MONTH))
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();

$lastMonthEarnings =
$stmt->get_result()->fetch_assoc()['total'];


/* =========================================
   PERCENT CHANGE
========================================= */

$percentageChange = 0;

if($lastMonthEarnings > 0){

    $percentageChange =
    (($currentMonthEarnings - $lastMonthEarnings)
    / $lastMonthEarnings) * 100;

}

$percentageChange = round($percentageChange,1);

/* =========================================
   YEARLY CHART DATA
========================================= */

$yearLabels = [];
$yearData = [];

$stmt = $conn->prepare(" SELECT
    MONTH(event_date) as month,
    SUM(total_price) as earnings

    FROM bookings

    WHERE vendor_id = ?
    AND payment_status='paid'
    AND status='completed'
    AND YEAR(event_date)=YEAR(CURRENT_DATE())

    GROUP BY MONTH(event_date)

    ORDER BY MONTH(event_date)
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();

$result = $stmt->get_result();

$months = [
'Jan','Feb','Mar','Apr',
'May','Jun','Jul','Aug',
'Sep','Oct','Nov','Dec'
];

$earningsMap = array_fill(1,12,0);

while($row = $result->fetch_assoc()){

    $earningsMap[$row['month']] =
    $row['earnings'];

}

for($i=1;$i<=12;$i++){

    $yearLabels[] = $months[$i-1];
    $yearData[] = $earningsMap[$i];

}

// FETCH UPCOMING BOOKINGS
$stmt = $conn->prepare("SELECT b.*, l.title, l.image FROM bookings b 
                        LEFT JOIN listings l ON b.listing_id = l.listing_id 
                        WHERE b.vendor_id = ? AND b.booking_status != 'completed' 
                        ORDER BY b.event_date ASC LIMIT 3");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$upcomingResult = $stmt->get_result();

/* =========================================
   RECENT REVIEWS
========================================= */

$stmt = $conn->prepare("SELECT
        client_name,
        service_name,
        rating,
        review,
        client_image,
        event_date
    FROM reviews
    WHERE vendor_id = ?
    ORDER BY event_date DESC
    LIMIT 2
");

$stmt->bind_param("i", $vendor_id);
$stmt->execute();

$recentReviews = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Planora Dashboard</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <!-- GOOGLE FONT -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- FONT AWESOME -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link rel="stylesheet" href="../css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        <a href="dashboard.php" class="menu-item active">
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
        <p><?php echo htmlspecialchars($vendorVerificationLabel, ENT_QUOTES, 'UTF-8'); ?></p>
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

    <main class="main">

      <!-- TOPBAR -->

      <div class="topbar">

        <div class="welcome">
          <h1>Welcome back,
            <?php echo $_SESSION['vendor_name'] ?? 'Vendor'; ?></h1>
          <p><?php echo $currentDate; ?>, <?php echo $pendingBookings; ?> pending requests</p>
        </div>

      </div>

      <!-- STATS -->

      <div class="stats-grid">

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fa-regular fa-calendar"></i>
          </div>

          <div class="stat-content">
            <h3>Total Bookings</h3>
            <h2><?php echo $totalBookings; ?></h2>
            <p>↑ 18% this month</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fa-regular fa-file-lines"></i>
          </div>

          <div class="stat-content">
            <h3>Pending Requests</h3>
            <h2><?php echo $pendingBookings; ?></h2>
            <p>View & respond</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fa-solid fa-check"></i>
          </div>

          <div class="stat-content">
            <h3>Completed Events</h3>
            <h2><?php echo $completedEvents; ?></h2>
            <p>View history</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fa-regular fa-credit-card"></i>
          </div>

          <div class="stat-content">
            <h3>Total Earnings</h3>
            <h2>₱<?php echo number_format($totalEarnings, 2); ?></h2>
            <p>↑ 15% this month</p>
          </div>
        </div>

      </div>

      <!-- CONTENT -->

      <div class="content-grid">

        <!-- LEFT -->

        <div class="card">

          <div class="card-header">
            <h2>Upcoming Bookings</h2>

            <button class="outline-btn" id="calendarViewBtn">
              View Calendar
            </button>
          </div>

          <!-- ITEM -->

          <?php while($booking = $upcomingResult->fetch_assoc()) { ?>
            <div class="booking-item">
              <div class="booking-left">
                <img src="<?php echo htmlspecialchars($booking['image'] ?? '../image/planoraLogo.jpg'); ?>" alt="">
                <div class="booking-info">
                  <h3><?php echo htmlspecialchars($booking['title'] ?? 'title'); ?></h3>
                  <div class="booking-meta">
                    <span><i class="fa-regular fa-calendar"></i> <?php echo date("M d, Y", strtotime($booking['event_date'])); ?></span>
                    <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($booking['venue'] ?? 'Venue TBD'); ?></span>
                  </div>
                </div>
              </div>
              <div class="booking-right">
                <div class="status <?php echo strtolower($booking['booking_status']); ?>">
                  <?php echo ucfirst($booking['booking_status']); ?>
                </div>
                <div class="price">₱<?php echo number_format($booking['total_price']); ?></div>
              </div>
            </div>
          <?php } ?>

          <?php if($upcomingResult->num_rows == 0) echo "<p style='padding:20px; color:#888;'>No upcoming bookings.</p>"; ?>

          <a href="bookings.php" class="view-link" id="viewBookingsBtn">
            View All Bookings
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>

        <!-- RIGHT -->

        <div>

          <!-- CHART -->

          <div class="card">

            <div class="card-header">
              <h2>Earnings Overview</h2>

              <div class="earnings-dropdown">

                  <button class="outline-btn" id="periodBtn">
                      This Year
                  </button>

                  <div class="earnings-menu" id="earningsMenu">

                      <div data-period="today">Today</div>
                      <div data-period="week">This Week</div>
                      <div data-period="month">This Month</div>
                      <div data-period="year">This Year</div>
                      <div data-period="all">All Time</div>

                  </div>

              </div>
            </div>

            <h1 style="font-size:48px;">
                ₱<?php echo number_format($currentMonthEarnings,2); ?>
            </h1>

            <p
            style="
            margin-top:10px;
            color:
            <?php echo $percentageChange >= 0 ? '#57d98b' : '#ff5b5b'; ?>
            "
            >
            <?php echo $percentageChange >= 0 ? '↑' : '↓'; ?>

            <?php echo abs($percentageChange); ?>%

            from last month
            </p>

            <div class="chart-box">
                <canvas id="earningsChart"></canvas>
            </div>

          </div>

          <!-- REVIEWS -->

          <div class="card" style="margin-top:25px;">

            <div class="card-header">
              <h2>Recent Reviews</h2>

              <a href="reviews.php" class="view-link" id="viewReviewsBtn">
                View All
              </a>
            </div>

            <?php if($recentReviews->num_rows > 0): ?>

            <?php while($review = $recentReviews->fetch_assoc()): ?>

                <div class="review">

                    <img
                        src="../uploads/listings/<?php echo htmlspecialchars($review['client_image']); ?>"
                        alt="<?php echo htmlspecialchars($review['client_name']); ?>"
                    >

                    <div class="review-content">

                        <h4>
                            <?php echo htmlspecialchars($review['client_name']); ?>
                            -
                            <?php echo htmlspecialchars($review['service_name']); ?>
                        </h4>

                        <div class="stars">

                            <?php
                            for($i = 1; $i <= 5; $i++){
                                echo ($i <= $review['rating']) ? '★' : '☆';
                            }
                            ?>

                        </div>

                        <p>
                            <?php echo htmlspecialchars($review['review']); ?>
                        </p>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-reviews">
                <p>No reviews yet.</p>
            </div>

        <?php endif; ?>

          </div>

        </div>

      </div>

    </main>

  </div>

  <script>

    const labels =
    <?php echo json_encode($yearLabels); ?>;

    const earnings =
    <?php echo json_encode($yearData); ?>;

    const ctx =
    document.getElementById('earningsChart');

    new Chart(ctx,{

        type:'line',

        data:{

            labels:labels,

            datasets:[{

                data:earnings,

                borderColor:'#f4b84a',

                borderWidth:4,

                tension:.4,

                fill:false,

                pointRadius:4,

                pointHoverRadius:7

            }]

        },

        options:{

            responsive:true,

            maintainAspectRatio:false,

            plugins:{
                legend:{
                    display:false
                }
            },

            scales:{

                x:{
                    grid:{
                        display:false
                    },
                    ticks:{
                        color:'#999'
                    }
                },

                y:{
                    grid:{
                        color:'rgba(255,255,255,.05)'
                    },
                    ticks:{
                        color:'#999'
                    }
                }

            }

        }

    });


    const periodBtn = document.getElementById("periodBtn");
    const earningsMenu = document.getElementById("earningsMenu");

    if(periodBtn && earningsMenu){

        periodBtn.addEventListener("click",(e)=>{

            e.stopPropagation();

            earningsMenu.classList.toggle("show");

        });

        document.addEventListener("click",()=>{

            earningsMenu.classList.remove("show");

        });

    }

  </script>
  
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

  <script src="../javascript/dashboard.js"></script>

<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
