<?php
$loginErrorMessages = [
    'incorrect_password' => 'The input password is incorrect.',
    'account_not_found' => 'No account found with that email address.',
];
$loginError = $_GET['error'] ?? '';
$loginErrorMessage = $loginErrorMessages[$loginError] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Planora Login</title>
    <link rel="icon" type="image/jpeg" href="../image/planoraLogo.jpg">

    <!-- GOOGLE FONT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../css/vendorLogin.css?v=3">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>

<div class="container">

    <!-- LEFT -->
    <div class="left-section">

        <div class="logo">

            <div class="logo">
                <img src="../image/planoraLogo.jpg" alt="Planora Logo">
            </div>

            <h2>Planora</h2>

        </div>

        <div class="hero">

            <div class="line"></div>

            <h1>
                Your all in one platform
                for seamless event
                planning.
            </h1>

            <p>
                Plan, manage, and celebrate all with ease.
            </p>

            <ul>

                <li>• List multiple properties with rich media</li>

                <li>• Manage availability and blocked dates</li>

                <li>• Approve bookings and manage packages</li>

                <li>• Build your reputation with client reviews</li>

            </ul>

        </div>

        <!--<div class="stats">

            <div>
                <h3>2,400+</h3>
                <span> Services Listed</span>
            </div>

            <div>
                <h3>18,000+</h3>
                <span>Events Hosted</span>
            </div>

            <div>
                <h3>4.9★</h3>
                <span>Avg. Rating</span>
            </div>

        </div> -->

    </div>

    <!-- RIGHT -->
    <div class="right-section">

        <div class="form-container">

            <!-- LOGIN -->
            <div id="loginForm">

                <h1>Sign in to Planora</h1>

                <p class="subtitle">
                    Access your bookings and venue management tools
                </p>

                <!-- TOGGLE -->
                <div class="account-toggle">

                    <button>
                        Client
                    </button>

                    <button class="active">
                        Vendor
                    </button>

                </div>

                <!-- TABS -->
                <div class="tabs">

                    <button class="tab">
                        Log In
                    </button>

                    <button class="tab-link" id="showSignup">
                        Sign Up
                    </button>

                </div>

                <!-- FORM -->
                <form action="../php/vendorLoginProcess.php" method="POST">

                    <label>EMAIL ADDRESS</label>

                    <input 
                        type="email" 
                        name="email"
                        placeholder="your@email.com"
                        required
                    >

                    <div class="password-row">

                        <label>PASSWORD</label>

                        <a href="#">Forgot password?</a>

                    </div>

                    <input 
                        type="password" 
                        name="password"
                        placeholder="••••••••"
                        required
                    >

                    <div class="checkbox">

                        <input type="checkbox" name="remember">

                        <span>Keep me signed in</span>

                    </div>

                    <button type="submit" class="main-btn">
                        Sign In →
                    </button>

                </form>
                <div class="divider">
                    <span>or continue with</span>
                </div>

                <div class="social-login social-login-bottom">
                    <a class="social-btn" href="../php/vendorOAuthStart.php?provider=google">
                        <span class="social-mark google-mark">G</span>
                        Continue with Google
                    </a>

                    <a class="social-btn" href="../php/vendorOAuthStart.php?provider=facebook">
                        <span class="social-mark facebook-mark">f</span>
                        Continue with Facebook
                    </a>
                </div>

            </div>

            <!-- SIGNUP -->
            <div id="signupForm">

                <h1>Create Account</h1>

                <p class="subtitle">
                    Create your Planora account
                </p>

                <!-- TABS -->
                <div class="tabs">

                    <button class="tab-link" id="showLogin">
                        Log In
                    </button>

                    <button class="tab">
                        Sign Up
                    </button>

                </div>

                <!-- SIGNUP FORM -->
                <form action="../php/vendorSignupProcess.php" method="POST">

                    <label>FULL NAME</label>
                    <input type="text" name="full_name" placeholder="Juan Dela Cruz" required>

                    <label>EMAIL ADDRESS</label>
                    <input type="email" name="email" placeholder="juan@email.com" required>

                    <label>PHONE NUMBER</label>
                    <input type="text" name="phone" placeholder="+63 912 345 6789">

                    <label>BUSINESS NAME</label>
                    <input type="text" name="business_name" placeholder="Elegant Events PH">    

                    <label>PRIMARY ADDRESS</label>
                    <input type="text" name="address" placeholder="Quezon City, Philippines">

                    <label>PASSWORD</label>
                    <input type="password" name="password" placeholder="••••••••" required>

                    <label>CONFIRM PASSWORD</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required>

                    <button type="submit" class="main-btn">
                        Create Account →
                    </button>

                </form>
                <div class="divider">
                    <span>or continue with</span>
                </div>

                <div class="social-login social-login-bottom">
                    <a class="social-btn" href="../php/vendorOAuthStart.php?provider=google">
                        <span class="social-mark google-mark">G</span>
                        Continue with Google
                    </a>

                    <a class="social-btn" href="../php/vendorOAuthStart.php?provider=facebook">
                        <span class="social-mark facebook-mark">f</span>
                        Continue with Facebook
                    </a>
                </div>

            </div>

        </div>

    </div>

</div>

<div class="terms-modal" id="termsModal" aria-hidden="true">
    <div class="terms-dialog" role="dialog" aria-modal="true" aria-labelledby="termsTitle">
        <button type="button" class="modal-close" id="closeTerms" aria-label="Close terms modal">
            &times;
        </button>

        <h2 id="termsTitle">Vendor Payout Terms & Conditions</h2>

        <div class="terms-image-wrap">
            <img src="../image/vendor-payout-terms.jpg" alt="Planora vendor payout terms and conditions">
        </div>

        <div class="terms-actions">
            <button type="button" class="secondary-btn" id="cancelTerms">Cancel</button>
            <button type="button" class="main-btn" id="acceptTerms">Agree and Continue</button>
        </div>
    </div>
</div>

<script src="../javascript/vendorLogin.js?v=3"></script>
<?php if ($loginErrorMessage !== ''): ?>
<script>
    alert(<?= json_encode($loginErrorMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
    window.history.replaceState(null, '', window.location.pathname);
</script>
<?php endif; ?>

</body>
</html>




