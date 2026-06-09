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
    <link rel="stylesheet" href="../css/vendorLogin.css">
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

        <div class="stats">

            <div>
                <h3>2,400+</h3>
                <span>Venues Listed</span>
            </div>

            <div>
                <h3>18,000+</h3>
                <span>Events Hosted</span>
            </div>

            <div>
                <h3>4.9★</h3>
                <span>Avg. Rating</span>
            </div>

        </div>

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

                    <button  class="active">
                        Client
                    </button>

                    <button>
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
                <form>

                    <label>EMAIL ADDRESS</label>

                    <input type="email" placeholder="your@email.com">

                    <div class="password-row">

                        <label>PASSWORD</label>

                        <a href="#">Forgot password?</a>

                    </div>

                    <input type="password" placeholder="••••••••">

                    <div class="checkbox">

                        <input type="checkbox">

                        <span>Keep me signed in</span>

                    </div>

                    <button class="main-btn">
                        Sign In →
                    </button>

                </form>

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
                <form>

                    <label>FULL NAME</label>
                    <input type="text" placeholder="Juan Dela Cruz">

                    <label>EMAIL ADDRESS</label>
                    <input type="email" placeholder="juan@email.com">

                    <label>PHONE NUMBER</label>
                    <input type="text" placeholder="+63 912 345 6789">

                    <label>BUSINESS NAME</label>
                    <input type="text" placeholder="Elegant Events PH">

                    <label>PRIMARY ADDRESS</label>
                    <input type="text" placeholder="Quezon City, Philippines">

                    <label>PASSWORD</label>
                    <input type="password" placeholder="••••••••">

                    <label>CONFIRM PASSWORD</label>
                    <input type="password" placeholder="••••••••">

                    <button class="main-btn">
                        Create Account →
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<script src="../javascript/vendorLogin.js"></script>

</body>
</html>
