<?php

// UNCOMMENT THE LINE BELOW TO SHOW TEMPORARILY UNAVAILABLE MESSAGE
header("Location: temporaryUnavailable.php?page=messages"); exit();

session_start();

if (!isset($_SESSION['vendor_id'])) {
    header("Location: index.php");
    exit();
}

include '../php/connect.php';
include '../php/vendorVerification.php';

$vendor_id = (int) $_SESSION['vendor_id'];
$vendorVerificationLabel = vendorVerificationLabel($conn, $vendor_id);

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS vendor_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NULL,
        attachment_path VARCHAR(255) NULL,
        attachment_name VARCHAR(255) NULL,
        read_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vendor_pair (sender_id, receiver_id),
        INDEX idx_receiver_read (receiver_id, read_at),
        INDEX idx_created_at (created_at)
    )
");

$readColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM vendor_messages LIKE 'read_at'");
if ($readColumnCheck && mysqli_num_rows($readColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE vendor_messages ADD COLUMN read_at DATETIME NULL AFTER attachment_name");
}

$unreadQuery = $conn->prepare("SELECT COUNT(*) AS total FROM vendor_messages WHERE receiver_id = ? AND read_at IS NULL");
$unreadQuery->bind_param("i", $vendor_id);
$unreadQuery->execute();
$unreadCount = (int) ($unreadQuery->get_result()->fetch_assoc()['total'] ?? 0);

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function avatarPath($path) {
    $path = trim((string) ($path ?? ''));

    if ($path === '') {
        return '../image/planoraLogo.jpg';
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    $normalized = ltrim(str_replace('\\', '/', $path), '/');

    if (is_file(dirname(__DIR__) . '/' . $normalized)) {
        return '../' . $normalized;
    }

    return '../image/planoraLogo.jpg';
}

$currentVendorStmt = $conn->prepare("SELECT id, full_name, business_name, profile_image FROM vendors WHERE id = ?");
$currentVendorStmt->bind_param("i", $vendor_id);
$currentVendorStmt->execute();
$currentVendor = $currentVendorStmt->get_result()->fetch_assoc() ?: [];

$contactsStmt = $conn->prepare("
    SELECT id, full_name, business_name, email, phone, profile_image
    FROM vendors
    WHERE id <> ?
    ORDER BY full_name ASC, business_name ASC
");
$contactsStmt->bind_param("i", $vendor_id);
$contactsStmt->execute();
$contactsResult = $contactsStmt->get_result();

$contacts = [];
while ($contact = $contactsResult->fetch_assoc()) {
    $contacts[] = $contact;
}

$firstContact = $contacts[0] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Planora - Messages</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <link rel="stylesheet" href="../css/messages.css?v=3"/>

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body
  data-current-vendor-id="<?php echo $vendor_id; ?>"
  data-current-vendor-name="<?php echo e($currentVendor['full_name'] ?? ($_SESSION['vendor_name'] ?? 'Vendor')); ?>"
>

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

        <a href="availability.php" class="menu-item">
          <i class="fa-solid fa-calendar-days"></i>
          <span>Availability</span>
        </a>

        <a href="messages.php" class="menu-item active">
          <i class="fa-solid fa-message"></i>
          <span>Messages</span>
          <span class="sidebar-notification" id="sidebarUnreadBadge" <?php echo $unreadCount > 0 ? '' : 'hidden'; ?>>
            <?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?>
          </span>
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

      <img src="<?php echo e(avatarPath($currentVendor['profile_image'] ?? '')); ?>" alt="">

      <div class="vendor-info">
        <h4><?php echo e($_SESSION['vendor_name'] ?? 'Vendor'); ?></h4>
        <p><?php echo e($vendorVerificationLabel); ?></p>
      </div>

      <div class="vendor-dropdown">

        <i class="fa-solid fa-chevron-down dropdown-toggle"></i>

        <div class="dropdown-menu" id="dropdownMenu">

          <a href="../php/logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
          </a>

        </div>

      </div>

    </div>

  </aside>

  <main class="main-content">

    <div class="messages-wrapper">

      <div class="conversation-list">

        <div class="messages-header">
          <h1>Messages</h1>

          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="conversationSearch" placeholder="Search conversation..." autocomplete="off">
          </div>
        </div>

        <div class="chat-list" id="chatList">
          <?php if (count($contacts) > 0): ?>
            <?php foreach ($contacts as $index => $contact): ?>
              <?php
                $contactName = $contact['full_name'] ?: 'Vendor';
                $businessName = $contact['business_name'] ?: 'Vendor Service';
                $contactAvatar = avatarPath($contact['profile_image'] ?? '');
                $searchText = strtolower($contactName . ' ' . $businessName . ' ' . ($contact['email'] ?? '') . ' ' . ($contact['phone'] ?? ''));
              ?>
              <button
                class="chat-item <?php echo $index === 0 ? 'active-chat' : ''; ?>"
                type="button"
                data-receiver-id="<?php echo (int) $contact['id']; ?>"
                data-name="<?php echo e($contactName); ?>"
                data-business="<?php echo e($businessName); ?>"
                data-avatar="<?php echo e($contactAvatar); ?>"
                data-search="<?php echo e($searchText); ?>"
              >
                <img src="<?php echo e($contactAvatar); ?>" alt="<?php echo e($contactName); ?>">

                <div class="chat-info">
                  <div class="top-row">
                    <h3><?php echo e($contactName); ?></h3>
                    <span data-last-time>Ready</span>
                  </div>

                  <p class="service"><?php echo e($businessName); ?></p>

                  <div class="tags">
                    <span>Vendor</span>
                    <span class="blue">Live Chat</span>
                  </div>

                  <p class="preview" data-preview>Start a conversation.</p>
                </div>
              </button>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-conversations">No other vendors found yet.</div>
          <?php endif; ?>
        </div>

      </div>

      <div class="chat-area">

        <div class="chat-top">

          <div class="chat-user">
            <img id="activeReceiverAvatar" src="<?php echo e($firstContact ? avatarPath($firstContact['profile_image'] ?? '') : '../image/planoraLogo.jpg'); ?>" alt="">

            <div>
              <h2 id="activeReceiverName"><?php echo e($firstContact['full_name'] ?? 'Select a conversation'); ?></h2>
              <p id="activeReceiverBusiness"><?php echo e($firstContact['business_name'] ?? 'Choose a vendor to start chatting.'); ?></p>

              <div class="tags">
                <span>Vendor</span>
                <span class="blue">Live Chat</span>
              </div>
            </div>
          </div>

          <button class="booking-btn" id="viewBookingBtn" type="button">
            View Booking Request
          </button>

        </div>

        <div class="chat-messages" id="chatMessages">
          <div class="empty-chat">Select a vendor to view messages.</div>
        </div>

        <div class="chat-input">

          <button class="attach-btn" id="attachBtn" type="button" aria-label="Attach file">
            <i class="fa-solid fa-paperclip"></i>
          </button>

          <input type="file" id="attachmentInput" hidden>

          <input
            type="text"
            id="messageInput"
            placeholder="Type your message..."
            autocomplete="off"
          >

          <button class="emoji-btn" id="emojiBtn" type="button" aria-label="Add emoji">
            <i class="fa-regular fa-face-smile"></i>
          </button>

          <div class="emoji-panel" id="emojiPanel" hidden>
            <button type="button">😊</button>
            <button type="button">👍</button>
            <button type="button">🙏</button>
            <button type="button">❤️</button>
            <button type="button">🎉</button>
            <button type="button">📅</button>
            <button type="button">✅</button>
            <button type="button">✨</button>
          </div>

          <button class="send-btn" id="sendBtn" type="button">
            Send
          </button>

        </div>

        <div class="attachment-preview" id="attachmentPreview" hidden></div>

      </div>

    </div>

  </main>

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

  <script src="../javascript/messages.js?v=3"></script>
<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
