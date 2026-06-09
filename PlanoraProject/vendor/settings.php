<?php

// UNCOMMENT THE LINE BELOW TO SHOW TEMPORARILY UNAVAILABLE MESSAGE
//header("Location: temporaryUnavailable.php?page=settings"); exit();

session_start();

if (!isset($_SESSION['vendor_id'])) {
    header("Location: index.php");
    exit();
}

include '../php/connect.php';
include '../php/vendorProfileImage.php';
include '../php/vendorVerification.php';

$vendor_id = (int) $_SESSION['vendor_id'];
$documentTypes = vendorRequiredDocumentTypes();

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

$passwordColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM vendors LIKE 'password_changed_at'");
if ($passwordColumnCheck && mysqli_num_rows($passwordColumnCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE vendors ADD COLUMN password_changed_at DATETIME NULL");
}

$stmt = $conn->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$vendor = $stmt->get_result()->fetch_assoc();
$stmt->close();

$docsStmt = $conn->prepare("SELECT * FROM vendor_documents WHERE vendor_id = ? ORDER BY uploaded_at DESC");
$docsStmt->bind_param("i", $vendor_id);
$docsStmt->execute();
$documents = $docsStmt->get_result();

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function displayValue($value, $fallback = 'Not set') {
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? e($value) : $fallback;
}

$notice = $_GET['notice'] ?? '';
$vendorName = $vendor['full_name'] ?? ($_SESSION['vendor_name'] ?? 'Vendor');
$profileImage = vendorProfileImagePath($conn, $vendor_id);
$vendorVerificationLabel = vendorVerificationLabel($conn, $vendor_id);
$vendorAddress = trim((string) ($vendor['address'] ?? ''));
$googleMapsUrl = $vendorAddress !== ''
    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($vendorAddress)
    : '';
$lastPasswordChanged = !empty($vendor['password_changed_at'])
    ? date('M d, Y', strtotime($vendor['password_changed_at']))
    : 'Not changed yet';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Planora Settings</title>
  <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

  <link rel="stylesheet" href="../css/settings.css?v=3">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container">

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
        <a href="reviews.php" class="menu-item"><i class="fa-solid fa-star"></i><span>Reviews</span></a>
        <a href="listings.php" class="menu-item"><i class="fa-regular fa-star"></i><span>My Listing</span></a>
        <a href="profile.php" class="menu-item"><i class="fa-solid fa-user"></i><span>Profile</span></a>
        <a href="settings.php" class="menu-item active"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
      </nav>
    </div>

    <div class="vendor-profile">
      <img src="<?php echo e($profileImage); ?>" alt="">

      <div class="vendor-info">
        <h4><?php echo e($vendorName); ?></h4>
        <p><?php echo e($vendorVerificationLabel); ?></p>
      </div>

      <div class="vendor-dropdown">
        <i class="fa-solid fa-chevron-down dropdown-toggle"></i>
        <div class="dropdown-menu" id="dropdownMenu">
          <a href="../php/logout.php" class="sidebar-logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
          </a>
        </div>
      </div>
    </div>
  </aside>

  <main class="main-content">
    <div class="top-header">
      <div>
        <h1>Settings</h1>
        <p>Manage your account and business settings.</p>
      </div>

      <div class="header-profile">
        <i class="fa-regular fa-bell"></i>
        <div class="header-user">
          <img src="<?php echo e($profileImage); ?>" alt="">
          <div>
            <h4><?php echo e($vendorName); ?></h4>
            <span><?php echo e($vendorVerificationLabel); ?></span>
          </div>
        </div>
      </div>
    </div>

    <?php if ($notice): ?>
      <div class="notice <?php echo $notice === 'error' ? 'error' : 'success'; ?>">
        <?php
          $messages = [
              'profile-updated' => 'Settings updated successfully.',
              'password-updated' => 'Password changed successfully.',
              'document-uploaded' => 'Document uploaded and marked as pending.',
              'document-deleted' => 'Pending document deleted successfully.',
              'delete-not-allowed' => 'Only pending documents can be deleted.',
              'password-error' => 'Password change failed. Please check your current password and make sure the new passwords match.',
              'upload-error' => 'Document upload failed. Please upload a supported file.',
              'error' => 'Something went wrong. Please try again.'
          ];
          echo e($messages[$notice] ?? $messages['error']);
        ?>
      </div>
    <?php endif; ?>

    <div class="settings-wrapper">
      <div class="settings-card">
        <div class="settings-left">
          <div class="icon-box"><i class="fa-regular fa-user"></i></div>
          <div>
            <h3>Personal Information</h3>
            <p>View and update your personal details.</p>
          </div>
        </div>

        <div class="settings-details">
          <div><span>Name</span><h4><?php echo displayValue($vendor['full_name'] ?? ''); ?></h4></div>
          <div><span>Phone Number</span><h4><?php echo displayValue($vendor['phone'] ?? ''); ?></h4></div>
          <div><span>Recovery Email</span><h4><?php echo displayValue($vendor['email'] ?? ''); ?></h4></div>
        </div>

        <button class="manage-btn" type="button" data-modal="personalModal">Manage <i class="fa-solid fa-chevron-right"></i></button>
      </div>

      <div class="settings-card">
        <div class="settings-left">
          <div class="icon-box"><i class="fa-solid fa-briefcase"></i></div>
          <div>
            <h3>Business Information</h3>
            <p>View and update your business details.</p>
          </div>
        </div>

        <div class="settings-details">
          <div><span>Business Name</span><h4><?php echo displayValue($vendor['business_name'] ?? ''); ?></h4></div>
          <div><span>Business Contact</span><h4><?php echo displayValue($vendor['phone'] ?? ''); ?></h4></div>
        </div>

        <button class="manage-btn" type="button" data-modal="businessModal">Manage <i class="fa-solid fa-chevron-right"></i></button>
      </div>

      <div class="settings-card">
        <div class="settings-left">
          <div class="icon-box"><i class="fa-solid fa-location-dot"></i></div>
          <div>
            <h3>Address</h3>
            <p>View and update your business address.</p>
          </div>
        </div>

        <div class="settings-details">
          <div><span>Business Address</span><h4><?php echo displayValue($vendor['address'] ?? ''); ?></h4></div>
        </div>

        <div class="settings-actions">
          <?php if ($googleMapsUrl): ?>
            <a class="map-link-btn" href="<?php echo e($googleMapsUrl); ?>" target="_blank" rel="noopener">
              <i class="fa-solid fa-map-location-dot"></i>
              Open in Maps
            </a>
          <?php endif; ?>

          <button class="manage-btn" type="button" data-modal="addressModal">Manage <i class="fa-solid fa-chevron-right"></i></button>
        </div>
      </div>

      <div class="settings-card">
        <div class="settings-left">
          <div class="icon-box"><i class="fa-solid fa-lock"></i></div>
          <div>
            <h3>Change Password</h3>
            <p>Ensure your password is strong.</p>
          </div>
        </div>

        <div class="settings-details">
          <div><span>Last Changed</span><h4><?php echo e($lastPasswordChanged); ?></h4></div>
        </div>

        <button class="manage-btn" type="button" data-modal="passwordModal">Change Password <i class="fa-solid fa-chevron-right"></i></button>
      </div>

      <div class="document-card">
        <div class="document-header">
          <div class="settings-left">
            <div class="icon-box"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
              <h3>Vendor Verification Documents</h3>
              <p>Upload documents to verify your business.</p>
            </div>
          </div>

          <button class="upload-btn" type="button" data-modal="documentModal">
            <i class="fa-solid fa-upload"></i>
            Upload Document
          </button>
        </div>

        <table>
          <thead>
            <tr>
              <th>Document Type</th>
              <th>Description</th>
              <th>Status</th>
              <th>Uploaded On</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($documents && mysqli_num_rows($documents) > 0): ?>
              <?php while ($document = mysqli_fetch_assoc($documents)): ?>
                <?php $documentStatus = strtolower((string) ($document['status'] ?? '')); ?>
                <tr>
                  <td><?php echo e($documentTypes[strtolower($document['document_name'])] ?? $document['document_name']); ?></td>
                  <td><?php echo displayValue($document['description'] ?? '', 'No description'); ?></td>
                  <td><span class="status-badge <?php echo e($documentStatus); ?>"><?php echo e(ucfirst($documentStatus)); ?></span></td>
                  <td><?php echo date('M d, Y', strtotime($document['uploaded_at'])); ?></td>
                  <td>
                    <div class="document-actions">
                      <button class="document-actions-toggle" type="button" aria-label="Document actions">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                      </button>

                      <div class="document-actions-menu">
                        <button
                          class="document-action-item reupload-btn"
                          type="button"
                          data-modal="documentModal"
                          data-document-type="<?php echo e(strtolower($document['document_name'])); ?>"
                          data-description="<?php echo e($document['description'] ?? ''); ?>">
                          <i class="fa-solid fa-upload"></i>
                          Re-upload
                        </button>

                        <form action="../php/deleteVendorDocument.php" method="POST" class="delete-document-form">
                          <input type="hidden" name="document_id" value="<?php echo e($document['id']); ?>">
                          <button
                            class="document-action-item delete-document-btn"
                            type="submit"
                            <?php echo $documentStatus === 'pending' ? '' : 'disabled'; ?>>
                            <i class="fa-solid fa-trash"></i>
                            Delete
                          </button>
                        </form>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="empty-row">No documents uploaded yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="logout-card">
        <div class="logout-left">
          <i class="fa-solid fa-right-from-bracket"></i>
          <div>
            <h3>Logout</h3>
            <p>Sign out from your account.</p>
          </div>
        </div>

        <a href="../php/logout.php" class="logout-action-btn">Logout</a>
      </div>
    </div>
  </main>
</div>

<div class="settings-modal" id="personalModal">
  <div class="modal-overlay"></div>
  <form class="modal-box" action="../php/updateSettings.php" method="POST">
    <input type="hidden" name="section" value="personal">
    <button class="modal-close" type="button"><i class="fa-solid fa-xmark"></i></button>
    <h2>Personal Information</h2>
    <label>Full Name</label>
    <input type="text" name="full_name" value="<?php echo e($vendor['full_name'] ?? ''); ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?php echo e($vendor['email'] ?? ''); ?>" required>
    <label>Phone Number</label>
    <input type="text" name="phone" value="<?php echo e($vendor['phone'] ?? ''); ?>">
    <button class="save-btn" type="submit">Save Changes</button>
  </form>
</div>

<div class="settings-modal" id="businessModal">
  <div class="modal-overlay"></div>
  <form class="modal-box" action="../php/updateSettings.php" method="POST">
    <input type="hidden" name="section" value="business">
    <button class="modal-close" type="button"><i class="fa-solid fa-xmark"></i></button>
    <h2>Business Information</h2>
    <label>Business Name</label>
    <input type="text" name="business_name" value="<?php echo e($vendor['business_name'] ?? ''); ?>" required>
    <label>Business Contact</label>
    <input type="text" name="phone" value="<?php echo e($vendor['phone'] ?? ''); ?>">
    <button class="save-btn" type="submit">Save Changes</button>
  </form>
</div>

<div class="settings-modal" id="addressModal">
  <div class="modal-overlay"></div>
  <form class="modal-box" action="../php/updateSettings.php" method="POST">
    <input type="hidden" name="section" value="address">
    <button class="modal-close" type="button"><i class="fa-solid fa-xmark"></i></button>
    <h2>Address</h2>
    <label>Business Address</label>
    <input type="text" name="address" id="addressInput" value="<?php echo e($vendor['address'] ?? ''); ?>" required>
    <button class="current-location-btn" type="button" id="useCurrentLocationBtn">
      <i class="fa-solid fa-location-crosshairs"></i>
      Use My Current Location
    </button>
    <p class="location-helper" id="locationHelper">Allow location access when your browser asks.</p>
    <button class="save-btn" type="submit">Save Changes</button>
  </form>
</div>

<div class="settings-modal" id="passwordModal">
  <div class="modal-overlay"></div>
  <form class="modal-box" action="../php/changePassword.php" method="POST">
    <button class="modal-close" type="button"><i class="fa-solid fa-xmark"></i></button>
    <h2>Change Password</h2>
    <label>Current Password</label>
    <input type="password" name="current_password" required>
    <label>New Password</label>
    <input type="password" name="new_password" minlength="8" required>
    <label>Confirm New Password</label>
    <input type="password" name="confirm_password" minlength="8" required>
    <button class="save-btn" type="submit">Change Password</button>
  </form>
</div>

<div class="settings-modal" id="documentModal">
  <div class="modal-overlay"></div>
  <form class="modal-box" action="../php/uploadVendorDocument.php" method="POST" enctype="multipart/form-data">
    <button class="modal-close" type="button"><i class="fa-solid fa-xmark"></i></button>
    <h2>Upload Document</h2>
    <label>Document Type</label>
    <select name="document_name" id="documentTypeSelect" required>
      <option value="">Select document type</option>
      <?php foreach ($documentTypes as $value => $label): ?>
        <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
      <?php endforeach; ?>
    </select>
    <label>Description</label>
    <input type="text" name="description" id="documentDescriptionInput" placeholder="Add document notes">
    <label>Document File</label>
    <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
    <button class="save-btn" type="submit">Upload as Pending</button>
  </form>
</div>

<script src="../javascript/settings.js?v=3"></script>
<script src="../javascript/sidebarNotifications.js?v=2"></script>
</body>
</html>
