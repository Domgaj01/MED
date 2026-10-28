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