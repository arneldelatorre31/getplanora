<?php

// GET THE PAGE PARAMETER TO HIGHLIGHT CORRECT MENU ITEM
$activePage = $_GET['page'] ?? 'temporaryUnavailable';

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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Planora - Temporarily Unavailable</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <!-- FONT AWESOME -->
  <link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

  <!-- GOOGLE FONT -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>

    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
    }

    body{
      font-family:'Poppins',sans-serif;
      background:#050505;
      color:white;
      overflow:hidden;
    }

    .dashboard-container{
      display:flex;
      min-height:100vh;
    }

    /* SIDEBAR */

    .sidebar{
      width:270px;
      background:#080808;
      border-right:1px solid rgba(255,255,255,0.06);
      padding:25px 18px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      position:fixed;
      height:100vh;
    }

    .logo{
      margin-bottom:30px;
    }

    .logo img{
      width:60px;
      object-fit:contain;
    }

    /* MENU */

    .sidebar-menu{
      display:flex;
      flex-direction:column;
      gap:12px;
    }

    .menu-item{
      height:52px;
      border:1px solid rgba(255,255,255,0.09);
      border-radius:14px;
      display:flex;
      align-items:center;
      gap:14px;
      padding:0 18px;
      color:#ddd;
      text-decoration:none;
      transition:0.3s ease;
    }

    .menu-item:hover{
      transform:translateX(4px);
      border-color:#f5c25c;
      color:#f5c25c;
      background:rgba(245,194,92,0.05);
    }

    .menu-item.active{
      background:rgba(245,194,92,0.08);
      border-color:#f5c25c;
      color:#f5c25c;
    }

    .menu-item i{
      font-size:17px;
    }

    /* PROFILE */

    .vendor-profile{
      border:1px solid rgba(255,255,255,0.08);
      border-radius:16px;
      padding:12px;
      display:flex;
      align-items:center;
      gap:12px;
    }

    .vendor-profile img{
      width:55px;
      height:55px;
      border-radius:50%;
      object-fit:cover;
    }

    .vendor-info{
      flex:1;
    }

    .vendor-info h4{
      font-size:15px;
      line-height:1.2;
    }

    .vendor-info p{
      color:#f5c25c;
      font-size:12px;
      margin-top:3px;
    }

    /* MAIN */

    .main-content{
      margin-left:270px;
      width:calc(100% - 270px);
      height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px;
      position:relative;
      overflow:hidden;
    }

    .main-content::before{
      content:'';
      position:absolute;
      width:500px;
      height:500px;
      background:rgba(245,194,92,0.08);
      filter:blur(120px);
      border-radius:50%;
      top:-100px;
      right:-100px;
    }

    .maintenance-box{
      position:relative;
      z-index:2;
      width:100%;
      max-width:750px;
      background:rgba(12,12,12,0.95);
      border:1px solid rgba(255,255,255,0.08);
      border-radius:28px;
      padding:70px 50px;
      text-align:center;
      backdrop-filter:blur(20px);
      box-shadow:0 0 60px rgba(0,0,0,0.5);
    }

    .maintenance-icon{
      width:110px;
      height:110px;
      border-radius:50%;
      background:rgba(245,194,92,0.12);
      border:1px solid rgba(245,194,92,0.25);
      display:flex;
      align-items:center;
      justify-content:center;
      margin:0 auto 35px;
    }

    .maintenance-icon i{
      font-size:45px;
      color:#f5c25c;
    }

    .maintenance-box h1{
      font-size:48px;
      margin-bottom:18px;
      line-height:1.2;
    }

    .maintenance-box p{
      font-size:18px;
      color:#9d9d9d;
      line-height:1.7;
      margin-bottom:12px;
    }

    .status-badge{
      display:inline-flex;
      align-items:center;
      gap:10px;
      padding:12px 20px;
      border-radius:999px;
      background:rgba(245,194,92,0.08);
      border:1px solid rgba(245,194,92,0.18);
      color:#f5c25c;
      font-size:14px;
      margin-top:20px;
      font-weight:600;
    }

    .status-dot{
      width:10px;
      height:10px;
      border-radius:50%;
      background:#f5c25c;
      box-shadow:0 0 12px #f5c25c;
    }

    @media(max-width:900px){

      .sidebar{
        display:none;
      }

      .main-content{
        margin-left:0;
        width:100%;
      }

      .maintenance-box h1{
        font-size:36px;
      }

    }

    @media(max-width:600px){

      .maintenance-box{
        padding:50px 25px;
      }

      .maintenance-box h1{
        font-size:28px;
      }

      .maintenance-box p{
        font-size:15px;
      }

    }
    /* DROPDOWN */

    .vendor-dropdown{
    position:relative;
    }

    .dropdown-toggle{
    cursor:pointer;
    padding:8px;
    border-radius:8px;
    transition:.3s;
    }

    .dropdown-toggle:hover{
    background:rgba(255,255,255,0.08);
    }

    /* FIXED DROPDOWN */

    .dropdown-menu{
    position:absolute;
    bottom:55px;
    right:0;
    background:#111;
    border:1px solid rgba(255,255,255,0.08);
    border-radius:12px;
    min-width:150px;
    padding:8px;
    display:none;
    z-index:999;
    box-shadow:0 10px 25px rgba(0,0,0,0.45);
    }

    .dropdown-menu.show{
    display:block;
    }

    .logout-btn{
    width:100%;
    display:flex;
    align-items:center;
    gap:10px;
    text-decoration:none;
    color:#ff5b5b;
    padding:12px;
    border-radius:10px;
    transition:.3s;
    font-size:14px;
    white-space:nowrap;
    }

    .logout-btn:hover{
    background:rgba(255,91,91,0.12);
    }

  </style>
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

        <a href="dashboard.php" class="menu-item<?php echo $activePage == 'dashboard' ? ' active' : ''; ?>">
          <i class="fa-solid fa-table-columns"></i>
          <span>Dashboard</span>
        </a>

        <a href="bookings.php" class="menu-item<?php echo $activePage == 'bookings' ? ' active' : ''; ?>">
          <i class="fa-solid fa-envelope-open-text"></i>
          <span>Bookings</span>
        </a>

        <a href="availability.php" class="menu-item<?php echo $activePage == 'availability' ? ' active' : ''; ?>">
          <i class="fa-solid fa-calendar-days"></i>
          <span>Availability</span>
        </a>

        <a href="messages.php" class="menu-item<?php echo $activePage == 'messages' ? ' active' : ''; ?>">
          <i class="fa-solid fa-message"></i>
          <span>Messages</span>
        </a>

        <a href="earnings.php" class="menu-item<?php echo $activePage == 'earnings' ? ' active' : ''; ?>">
          <i class="fa-solid fa-coins"></i>
          <span>Earnings</span>
        </a>

        <a href="reviews.php" class="menu-item<?php echo $activePage == 'reviews' ? ' active' : ''; ?>">
          <i class="fa-solid fa-star"></i>
          <span>Reviews</span>
        </a>

        <a href="listings.php" class="menu-item<?php echo $activePage == 'listings' ? ' active' : ''; ?>">
          <i class="fa-regular fa-star"></i>
          <span>My Listing</span>
        </a>

        <a href="profile.php" class="menu-item<?php echo $activePage == 'profile' ? ' active' : ''; ?>">
          <i class="fa-solid fa-user"></i>
          <span>Profile</span>
        </a>

        <a href="settings.php" class="menu-item<?php echo $activePage == 'settings' ? ' active' : ''; ?>">
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

  <main class="main-content">

    <div class="maintenance-box">

      <div class="maintenance-icon">
        <i class="fa-solid fa-screwdriver-wrench"></i>
      </div>

      <h1>This Page is Temporarily Unavailable</h1>

      <p>
        We're currently improving this section of Planora
        to give you a better experience.
      </p>

      <p>
        Waiting for developer update and system improvements.
      </p>

      <div class="status-badge">
        <span class="status-dot"></span>
        Maintenance Mode Active
      </div>

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

      if(
        !e.target.closest(".vendor-dropdown")
      ){
        menu.classList.remove("show");
      }

    });

</script>

<script src="../javascript/temporaryUnavailable.js"></script>

<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
