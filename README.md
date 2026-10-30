# MED
Semester 1 Project

## Movie database

The movies page connects to the `mysterycinema` MySQL database using the four
connection values in `src/parts/db.php`

Create the database tables with `database/table_creations.sql` and add
the sample movies with `database/movies_showcase.sql` do the same with other samples
Passwords are hashed with PHP's `password_hash()` and
checked with `password_verify()`; plaintext passwords are never stored.

Open `http://localhost/MED/src/` through Apache. The PHP page will not work
when opened directly as a file in the browser.

## Page files

- `src/index.php` renders the home page
- `src/movies.php` loads and renders movies using the shared connection in `src/parts/db.php`
- `src/login.php` signs in users and `src/logout.php` clears their session
- `src/create_user.php` creates customer accounts with hashed passwords
- `src/header.php` contains the shared document header and navigation
- `src/footer.php` contains the footer

## Admin page structure

The genre and movie management pages use a small MVC-style structure so future
CMS pages can follow the same pattern:

- `src/manage_*.php` starts the session, connects to the database, creates the
  page controller, and renders the HTML template.
- `src/classes/*AdminController.php` handles request input, validation, and
  page state.
- `src/classes/*Manager.php` contains database queries and persistence logic.
- `src/classes/AdminCsrfToken.php` provides one CSRF token for all admin forms
  in the current session; render it as a hidden `csrf_token` input and validate
  it before processing POST actions.
- `src/classes/AdminPageController.php` contains behavior shared by admin
  controllers, including HTML escaping and redirects.

For another management page, add a manager for that page's database operations
and an admin controller for its actions and validation. Keep the page file as
the entry point and place its HTML in the page template.