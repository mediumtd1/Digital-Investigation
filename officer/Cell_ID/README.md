# Police Station PHP + MySQL Project

A simple PHP application to manage police station records using a MySQL database.

## Setup

1. Install and run XAMPP or another PHP/MySQL server.
2. Update `config.php` with your MySQL credentials if needed.
3. Open `http://localhost/police_station/install.php` in your browser.
4. Click the button to create the database and table automatically.
5. After success, open `http://localhost/police_station/index.php`.
6. For advanced lookup, use `http://localhost/police_station/search.php`.

> If you prefer manual setup, you can also import `database.sql` into MySQL.

## Files

- `config.php` - database connection settings
- `db.php` - PDO connection helper
- `index.php` - police station list and navigation
- `create.php` - add a new police station
- `update.php` - edit a police station
- `delete.php` - remove a police station
- `import.php` - import police stations from CSV
- `search.php` - search police stations with state/district dropdowns and map display
- `database.sql` - database table schema
