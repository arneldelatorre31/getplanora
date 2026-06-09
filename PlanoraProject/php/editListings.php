<?php

session_start();
include 'connect.php';

if (!isset($_SESSION['vendor_id'])) {
    header("Location: ../vendor/index.php");
    exit();
}

$vendor_id = (int) $_SESSION['vendor_id'];
$listing_id = (int) ($_GET['listing_id'] ?? $_GET['id'] ?? $_POST['listing_id'] ?? 0);

if ($listing_id <= 0) {
    header("Location: ../vendor/listings.php");
    exit();
}

if (isset($_POST['update'])) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $type = strtolower(trim($_POST['type'] ?? 'package'));
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $status = strtolower(trim($_POST['status'] ?? 'active'));

    if (!in_array($type, ['package', 'ala carte'], true)) {
        $type = 'package';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }

    $sql = "UPDATE listings
            SET title = ?, category = ?, type = ?, description = ?, price = ?, status = ?
            WHERE listing_id = ? AND vendor_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "ssssdsii",
        $title,
        $category,
        $type,
        $description,
        $price,
        $status,
        $listing_id,
        $vendor_id
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../vendor/listings.php");
        exit();
    }

    $error = "Update failed: " . mysqli_error($conn);
}

$sql = "SELECT * FROM listings WHERE listing_id = ? AND vendor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $listing_id, $vendor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$listing = mysqli_fetch_assoc($result);

if (!$listing) {
    header("Location: ../vendor/listings.php");
    exit();
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function listingImageSrc($imagePath) {
    $imagePath = trim((string) ($imagePath ?? ''));

    if ($imagePath === '') {
        return '../image/planoraLogo.jpg';
    }

    if (preg_match('/^(https?:\/\/|data:image\/|\/)/i', $imagePath)) {
        return $imagePath;
    }

    if (strpos($imagePath, '../') === 0 || strpos($imagePath, './') === 0) {
        return $imagePath;
    }

    return '../' . ltrim($imagePath, '/\\');
}

$imageSrc = listingImageSrc($listing['image'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planora - Edit Listing</title>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            min-height:100vh;
            background:#050505;
            background-image:
                radial-gradient(ellipse at 10% 20%, rgba(245,194,92,0.05) 0%, transparent 45%),
                radial-gradient(ellipse at 90% 80%, rgba(212,175,55,0.04) 0%, transparent 45%);
            color:white;
            font-family:'Inter', Arial, sans-serif;
            padding:32px;
        }

        a{
            text-decoration:none;
        }

        .edit-shell{
            max-width:1180px;
            margin:0 auto;
        }

        .top-bar{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            padding-bottom:26px;
            margin-bottom:28px;
            border-bottom:1px solid rgba(255,255,255,0.06);
        }

        .brand{
            display:flex;
            align-items:center;
            gap:16px;
        }

        .brand img{
            width:58px;
            height:58px;
            border-radius:14px;
            object-fit:cover;
        }

        .brand h1{
            font-size:32px;
            line-height:1.1;
            background:linear-gradient(135deg, #fff 0%, #cfcfcf 100%);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            background-clip:text;
        }

        .brand p{
            margin-top:6px;
            color:#f5c25c;
            font-size:12px;
            font-weight:700;
            letter-spacing:0.5px;
        }

        .back-link{
            min-height:48px;
            padding:0 18px;
            border:1px solid #2a2a2a;
            border-radius:12px;
            background:linear-gradient(135deg, #111 0%, #161616 100%);
            color:#ddd;
            display:inline-flex;
            align-items:center;
            gap:10px;
            font-size:14px;
            font-weight:600;
            transition:0.3s;
        }

        .back-link:hover{
            border-color:#f5c25c;
            color:#f5c25c;
            transform:translateY(-2px);
        }

        .edit-layout{
            display:grid;
            grid-template-columns:360px 1fr;
            gap:28px;
            align-items:start;
        }

        .preview-card,
        .form-panel{
            background:linear-gradient(165deg, #0f0f0f 0%, #141414 100%);
            border:1px solid #1f1f1f;
            border-radius:22px;
            overflow:hidden;
            box-shadow:0 18px 60px rgba(0,0,0,0.28);
        }

        .preview-image{
            position:relative;
            height:245px;
            background:linear-gradient(135deg, #111 0%, #1a1a1a 100%);
            overflow:hidden;
        }

        .preview-image img{
            width:100%;
            height:100%;
            object-fit:cover;
            display:block;
        }

        .preview-image::after{
            content:'';
            position:absolute;
            left:0;
            right:0;
            bottom:0;
            height:80px;
            background:linear-gradient(to top, #0f0f0f, transparent);
        }

        .tag,
        .status{
            position:absolute;
            top:16px;
            z-index:2;
            padding:8px 13px;
            border-radius:10px;
            font-size:12px;
            font-weight:700;
            backdrop-filter:blur(10px);
        }

        .tag{
            left:16px;
            background:linear-gradient(135deg, #f5c25c 0%, #d4af37 100%);
            color:#111;
        }

        .status{
            right:16px;
            background:linear-gradient(135deg, #0f8a43 0%, #0a6b33 100%);
            color:white;
        }

        .status.inactive{
            background:linear-gradient(135deg, #555 0%, #444 100%);
        }

        .preview-content{
            padding:24px;
        }

        .preview-content h2{
            font-size:22px;
            margin-bottom:10px;
        }

        .preview-content .category{
            color:#f5c25c;
            font-size:13px;
            font-weight:700;
            text-transform:uppercase;
        }

        .preview-content p{
            color:#8f8f8f;
            line-height:1.6;
            margin:14px 0;
            font-size:14px;
        }

        .price{
            display:flex;
            align-items:center;
            gap:10px;
            margin-top:18px;
            color:#f5c25c;
            font-size:28px;
            font-weight:800;
        }

        .form-panel{
            padding:34px;
        }

        .panel-header{
            display:flex;
            align-items:center;
            gap:18px;
            margin-bottom:30px;
        }

        .icon-box{
            width:60px;
            height:60px;
            border-radius:16px;
            background:rgba(212,175,55,0.12);
            color:#d4af37;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:22px;
            flex-shrink:0;
        }

        .panel-header h2{
            font-size:26px;
            margin-bottom:5px;
        }

        .panel-header p{
            color:#888;
            font-size:14px;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:22px;
        }

        form{
            display:flex;
            flex-direction:column;
            gap:22px;
        }

        .form-group{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .form-group.full{
            grid-column:1 / -1;
        }

        label{
            color:white;
            font-size:14px;
            font-weight:700;
        }

        input,
        textarea,
        select{
            width:100%;
            padding:16px;
            border:1px solid #222;
            border-radius:14px;
            background:#111;
            color:white;
            outline:none;
            font-family:inherit;
            font-size:14px;
            transition:0.3s;
        }

        input:focus,
        textarea:focus,
        select:focus{
            border-color:#f5c25c;
            box-shadow:0 0 0 3px rgba(245,194,92,0.08);
        }

        input::placeholder,
        textarea::placeholder{
            color:#555;
        }

        textarea{
            min-height:150px;
            resize:vertical;
        }

        .actions{
            display:flex;
            justify-content:flex-end;
            gap:14px;
            padding-top:12px;
            border-top:1px solid rgba(255,255,255,0.05);
        }

        button{
            min-height:52px;
            padding:0 26px;
            border:none;
            border-radius:14px;
            background:linear-gradient(135deg, #f5c25c 0%, #d4af37 100%);
            color:black;
            font-size:15px;
            font-weight:800;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            gap:10px;
            transition:0.3s;
        }

        button:hover{
            transform:translateY(-2px);
            box-shadow:0 10px 30px rgba(245,194,92,0.24);
        }

        .cancel-link{
            min-height:52px;
            padding:0 22px;
            border-radius:14px;
            background:#222;
            color:#fff;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            transition:0.3s;
        }

        .cancel-link:hover{
            background:#303030;
        }

        .error{
            margin-bottom:20px;
            padding:14px 16px;
            border:1px solid rgba(255,103,103,0.3);
            border-radius:14px;
            background:rgba(255,103,103,0.08);
            color:#ff7b7b;
            font-size:14px;
        }

        @media(max-width:950px){
            body{
                padding:20px;
            }

            .top-bar{
                align-items:flex-start;
                flex-direction:column;
            }

            .edit-layout{
                grid-template-columns:1fr;
            }

            .preview-card{
                order:2;
            }
        }

        @media(max-width:650px){
            .form-grid{
                grid-template-columns:1fr;
            }

            .form-group.full{
                grid-column:auto;
            }

            .form-panel{
                padding:24px;
            }

            .actions{
                flex-direction:column-reverse;
            }

            button,
            .cancel-link,
            .back-link{
                width:100%;
                justify-content:center;
            }
        }
    </style>
</head>
<body>

<div class="edit-shell">
    <header class="top-bar">
        <div class="brand">
            <img src="../image/planoraLogo.jpg" alt="Planora Logo">
            <div>
                <h1>Planora</h1>
                <p>WHERE GREAT EVENTS BEGIN</p>
            </div>
        </div>

        <a href="../vendor/listings.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Listings
        </a>
    </header>

    <main class="edit-layout">
        <aside class="preview-card">
            <div class="preview-image">
                <img
                    src="<?php echo e($imageSrc); ?>"
                    alt="<?php echo e($listing['title']); ?>"
                    onerror="this.onerror=null; this.src='../image/planoraLogo.jpg';">
                <span class="tag"><?php echo e(ucwords($listing['type'])); ?></span>
                <span class="status <?php echo e($listing['status']); ?>">
                    <?php echo e(ucwords($listing['status'])); ?>
                </span>
            </div>

            <div class="preview-content">
                <span class="category"><?php echo e($listing['category']); ?></span>
                <h2><?php echo e($listing['title']); ?></h2>
                <p><?php echo e($listing['description']); ?></p>
                <div class="price">
                    <i class="fa-solid fa-peso-sign"></i>
                    <?php echo e(number_format((float) $listing['price'], 2)); ?>
                </div>
            </div>
        </aside>

        <section class="form-panel">
            <div class="panel-header">
                <div class="icon-box">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>

                <div>
                    <h2>Listing Information</h2>
                    <p>Update the details clients will see on your listing card.</p>
                </div>
            </div>

            <?php if (isset($error)) { ?>
                <p class="error"><?php echo e($error); ?></p>
            <?php } ?>

            <form method="POST">
                <input type="hidden"
                       name="listing_id"
                       value="<?php echo e($listing['listing_id']); ?>">

                <div class="form-grid">
                    <div class="form-group full">
                        <label for="title">Listing Name</label>
                        <input id="title"
                               type="text"
                               name="title"
                               value="<?php echo e($listing['title']); ?>"
                               placeholder="Listing name"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="wedding" <?php if ($listing['category'] === 'wedding') echo 'selected'; ?>>Wedding</option>
                            <option value="corporate" <?php if ($listing['category'] === 'corporate') echo 'selected'; ?>>Corporate</option>
                            <option value="event" <?php if ($listing['category'] === 'event') echo 'selected'; ?>>Event</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="type">Listing Type</label>
                        <select id="type" name="type" required>
                            <option value="package" <?php if ($listing['type'] === 'package') echo 'selected'; ?>>Package</option>
                            <option value="ala carte" <?php if ($listing['type'] === 'ala carte') echo 'selected'; ?>>Ala Carte</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price">Price</label>
                        <input id="price"
                               type="number"
                               name="price"
                               value="<?php echo e($listing['price']); ?>"
                               min="0"
                               step="0.01"
                               placeholder="Price"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php if ($listing['status'] === 'active') echo 'selected'; ?>>Active</option>
                            <option value="inactive" <?php if ($listing['status'] === 'inactive') echo 'selected'; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label for="description">Description</label>
                        <textarea id="description"
                                  name="description"
                                  placeholder="Description"><?php echo e($listing['description']); ?></textarea>
                    </div>
                </div>

                <div class="actions">
                    <a href="../vendor/listings.php" class="cancel-link">Cancel</a>
                    <button type="submit" name="update">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>
