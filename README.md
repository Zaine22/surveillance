# Surveillance Project - Laravel Backend

This repository contains the **Laravel backend** for the Surveillance Project. The complete system architecture is composed of:

- **Laravel** (Backend & API)
- **Next.js** (Frontend)
- **Crawler** (Data Extraction)
- **AI** (Processing & Analysis)

## Tech Stack Overview

This Laravel application utilizes the following specific tools and versions:

- **PHP 8.4**
- **Laravel 12**
- **Laravel Horizon 5** (Queue management)
- **Laravel Prompts**
- **Laravel Sanctum 4** (API Authentication)
- **Laravel Pint 1** (Code formatting)
- **Pest PHP 4** (Testing Framework)
- **Tailwind CSS v4**

## Prerequisites

- **PHP 8.4**
- **Composer**
- **Node.js & npm**
- **Laravel Herd** (Recommended for local development on macOS)

## Getting Started

Follow these steps to set up the project locally:

1. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Environment Configuration**
   Copy the example environment file and generate a new application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Update the `.env` file with your database and queue connection details.

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Serve the Application**
   It natively runs on [Laravel Herd](https://herd.laravel.com/). The project will be available locally at `http://survillance.test` (or your customized Herd domain).
   If you need to compile frontend assets (like Tailwind v4 styles):
   ```bash
   npm run dev
   ```

## Development Commands

### Testing with Pest

This application utilizes **Pest 4** for testing. Pest provides an elegant syntax and incorporates browser testing, smoke testing, and fast type coverage.

To run the test suite:
```bash
php artisan test
```
To run tests with a specific filter:
```bash
php artisan test --filter=TestName
```

### Code Formatting with Pint

Before committing changes, ensure your code matches the project's formatting standards using **Laravel Pint**:
```bash
./vendor/bin/pint --dirty
```

### Horizon (Queues)

If your environment utilizes queued jobs (e.g., crawler processing, background AI generation), simply run Horizon (make sure your `.env` specifies a supported queue driver like `redis`):
```bash
php artisan horizon
```

## Structure Notes

This project adheres to the streamlined Laravel 12 application structure:
* `bootstrap/app.php` is the single file used to configure routing, middleware, and exceptions.
* Artisan commands are auto-registered.
* No `app/Http/Middleware` directory is present by default.

## API & Authentication

For seamless interaction with the Next.js frontend or other microservices (like the Crawler or AI elements), API routing is structured within `routes/api.php` and authentication is managed via **Laravel Sanctum** using tokens or cookie-based session authentication depending on the setup.

---
*This is an automated README tailored for the Laravel segment of the overarching Surveillance ecosystem.*
