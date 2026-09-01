# microblog

A minimal old skool Twitter-style social app written in plain PHP with SQLite.

## Features

- User registration and login
- Session-based authentication
- Create posts up to 280 characters
- Public timeline
- Like / unlike posts
- User profiles
- CSRF protection and password hashing
- SQLite database created automatically

## Requirements

- PHP 8.0+
- PDO SQLite extension

## Run locally

```bash
git clone git@github.com:filtrd/Twit.git
cd Twit
php -S localhost:8000
```

Open http://localhost:8000 in your browser.

The SQLite database is created at `data/twit.sqlite` on first request and is ignored by Git.
