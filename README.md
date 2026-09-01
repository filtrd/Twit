# microblog

A minimal old-school microblogging social network written in plain PHP with SQLite.

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

## Project structure

```text
microblog/
├── index.php
├── login.php
├── register.php
├── profile.php
│
├── assets/
│   └── style.css
│
├── inc/
│   ├── config.php
│   ├── database.php
│   ├── post.php
│   ├── like.php
│   └── logout.php
│
├── data/
│   ├── .gitignore
│   └── .gitkeep
│
├── README.md
└── LICENSE
```

- Root PHP files are the public-facing pages.
- `assets/` contains frontend assets such as CSS.
- `inc/` contains backend configuration, database code, and form/action handlers.
- `data/` contains the local SQLite database and is ignored by Git.

## Run locally

```bash
git clone git@github.com:filtrd/microblog.git
cd microblog
php -S localhost:8000
```

Open http://localhost:8000 in your browser.

The SQLite database is created at `data/twit.sqlite` on first request and is ignored by Git.
