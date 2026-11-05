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

MVC-style

- `src/parts/admin_auth.php` verifies the signed-in user and checks the current
  database role and active status before admin pages run
- `src/header.php` displays navigation for signed-in administrators
- `src/manage_*.php` checks admin access before creating the page controller
  and rendering the HTML template
- `src/manage_site.php` edits general company information and opening hours
- `src/manage_news.php` provides separate create, edit, publish, and delete
  actions for news articles
- `src/classes/*AdminController.php` handles request input, validation, and
  page state.
- `src/classes/*Manager.php` contains database queries and persistence logic.
- `src/classes/AdminCsrfToken.php` provides one CSRF token for all admin forms, render it as
  a hidden `csrf_token` input and validate
  it before processing POST actions.
- `src/classes/AdminPageController.php` contains behavior shared by admin
  controllers, including HTML escaping and redirects.
  
## Booking setup

`src/book-tickets.php` loads showtimes, ticket prices, seat layouts, and current
reservations from the booking tables in `database/table_creations.sql`. Run that
script, then `database/movies_showcase.sql`, then
`database/screening_showcase.sql` to add demo halls, seats, and 15 screenings
scheduled three to six days ahead. The booking date picker shows the next six
days. Booking customers reference the existing `users.user_id` account table.
The seat and ticket selection is currently frontend-only