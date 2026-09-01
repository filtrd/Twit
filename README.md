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
├── logout.php
├── post.php
├── like.php
│
├── assets/
│   └── style.css
│
├── inc/
│   ├── config.php
│   ├── functions.php
│   └── database.php
│
├── data/
│   ├── .gitignore
│   └── .gitkeep
│
├── README.md
└── LICENSE
```

- Root PHP files are the public-facing pages and action endpoints.
- `assets/` contains frontend assets such as CSS.
- `inc/` contains internal configuration, helper functions, and database code.
- `data/` contains the local SQLite database and is ignored by Git.

## Run locally

```bash
git clone git@github.com:filtrd/microblog.git
cd microblog
php -S localhost:8000
```

Open http://localhost:8000 in your browser.

The SQLite database is created at `data/microblog.sqlite` on first request and is ignored by Git.
