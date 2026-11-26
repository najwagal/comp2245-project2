# COMP2245 Project 2: Fix-It Desk

A small PHP/MySQL help desk: log in, view tickets, filter them, add notes, change status, and (for admins) manage users.

## Group members

|Name|GitHub username|What they worked on|
|-|-|-|
|Abigail Galloway|najwagal|Database schema, shared includes, login/logout, users page, styles|
|Jemuel Joseph|JemuelJoseph|Ticket list, ticket forms, ticket page, AJAX endpoints, app.js|

## Setup

1. Import the schema (creates the `helpdesk` database, seed data and the `comp2245` DB user):
`mysql -u root -p < schema.sql`
2. Copy `config.example.php` to `config.php` (it is git-ignored) and adjust if needed.
3. Serve the folder with PHP 8.1+, e.g. `php -S localhost:8000`, and open `http://localhost:8000`.

## Seed accounts

All seed accounts use the password `password123`.

* admin@fixit.test (admin)
* keisha@fixit.test (member)
* marcus@fixit.test (member)

