# Planora Project Notes

## Project Shape
- This is a PHP/XAMPP vendor dashboard for Planora.
- `vendor/*.php` files are vendor-facing page entry points.
- `client/` is reserved for future client-facing pages and is currently empty.
- `css/` and `javascript/` contain page-specific assets, usually matching the page name.
- `php/` contains server-side actions and shared helpers such as `connect.php`.
- Uploaded user/listing assets live under `uploads/`.

## Runtime
- The app expects XAMPP/Apache with PHP and a local MySQL database.
- Database connection is in `php/connect.php` and currently targets:
  - host: `localhost`
  - user: `root`
  - database: `planorausers`
- Vendor pages generally require `$_SESSION['vendor_id']` and redirect to `index.php` when missing.
- The vendor login entry URL is `http://localhost/PlanoraProject/vendor/index.php`.
- The vendor dashboard entry URL is `http://localhost/PlanoraProject/vendor/dashboard.php`.

## Coding Conventions
- Keep changes scoped to the relevant page plus its paired CSS/JS file.
- Prefer prepared statements for database access.
- Escape rendered user/database values with `htmlspecialchars`, ideally with `ENT_QUOTES` and `UTF-8`.
- Preserve the existing Font Awesome and Google Fonts usage unless a broader redesign is requested.
- Use `php/logout.php` for logout links where possible; some pages still link directly to `index.php`.

## Current Gotchas
- Several pages contain hardcoded/demo content in modals or cards. Check whether a feature should be wired to database data before assuming the mock content is intentional.
- Some peso symbols and bullets appear as mojibake, such as `â‚±` and `â—`. Treat this as an encoding issue and preserve/fix carefully.
- `availability.php` should include `../javascript/availability.js` from inside `vendor/`.
- `reviews.php` currently redirects immediately to `temporaryUnavailable.php?page=reviews`; code below that redirect is unreachable until the redirect line is disabled.
- `bookings.php` has live database rows in the table, but its details modal is still static demo content.

## Verification
- For PHP syntax checks, run `php -l path/to/file.php` when PHP is available on PATH.
- For browser behavior, test through the local XAMPP URL, for example `http://localhost/PlanoraProject/vendor/bookings.php`.
