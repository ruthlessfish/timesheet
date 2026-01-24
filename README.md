# Time Tracking Application for Freelance Web Developers<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>



A comprehensive time-tracking application built with Laravel 12, designed specifically for freelance web developers to manage clients, projects, time entries, and invoices.<p align="center">

<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>

## Features<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>

<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>

### Client Management<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>

- Create and manage client profiles</p>

- Store contact information and billing details

- Set default hourly rates per client## About Laravel

- Track client activity status

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

### Project Management

- Organize work by client and project- [Simple, fast routing engine](https://laravel.com/docs/routing).

- Set project-specific hourly rates and budgets- [Powerful dependency injection container](https://laravel.com/docs/container).

- Track project status (active, on hold, completed, cancelled)- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.

- Monitor project timelines with start and end dates- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).

- View total hours and earnings per project- Database agnostic [schema migrations](https://laravel.com/docs/migrations).

- [Robust background job processing](https://laravel.com/docs/queues).

### Time Tracking- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

- Start/stop timer for time entries

- Manual time entry with descriptionLaravel is accessible, powerful, and provides tools required for large, robust applications.

- Track billable and non-billable hours

- Automatic duration calculation## Learning Laravel

- Override hourly rates per time entry

- Associate time entries with specific projectsLaravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.



### Invoice GenerationIf you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

- Create invoices from time entries

- Automatic invoice numbering## Laravel Sponsors

- Support for tax calculations

- Multiple invoice statuses (draft, sent, paid, overdue, cancelled)We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

- Export invoices to PDF

- Track invoice items and totals### Premium Partners



### Dashboard & Reports- **[Vehikl](https://vehikl.com)**

- Visual insights with charts- **[Tighten Co.](https://tighten.co)**

- Time analytics across projects and clients- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**

- Revenue tracking- **[64 Robots](https://64robots.com)**

- Billable vs non-billable hours comparison- **[Curotec](https://www.curotec.com/services/technologies/laravel)**

- **[DevSquad](https://devsquad.com/hire-laravel-developers)**

## Tech Stack- **[Redberry](https://redberry.international/laravel-development)**

- **[Active Logic](https://activelogic.com)**

- **Framework:** Laravel 12

- **Authentication:** Laravel Breeze## Contributing

- **Database:** SQLite (configurable for MySQL/PostgreSQL)

- **Frontend:** Blade Templates + Tailwind CSSThank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

- **Charts:** Chart.js

- **PDF Generation:** DomPDF## Code of Conduct

- **Build Tool:** Vite

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Installation

## Security Vulnerabilities

### Requirements

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

- PHP 8.2 or higher

- Composer## License

- Node.js & NPM

- SQLite (or MySQL/PostgreSQL)The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


### Setup Steps

1. **Clone the repository** (if applicable)
   ```bash
   git clone <repository-url>
   cd timeshit
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   
   The application is pre-configured to use SQLite. If you want to use MySQL or PostgreSQL, update the `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=timetracking
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

8. **Start development server**
   ```bash
   php artisan serve
   ```

9. **Access the application**
   
   Open your browser and visit: `http://localhost:8000`

## Database Schema

### Tables

- **users** - User authentication and profiles
- **clients** - Client information and billing rates
- **projects** - Project details linked to clients
- **time_entries** - Individual time tracking records
- **invoices** - Invoice header information
- **invoice_items** - Line items for invoices

### Relationships

- A User has many Clients, Projects, Time Entries, and Invoices
- A Client has many Projects and Invoices
- A Project belongs to a Client and has many Time Entries
- A Time Entry belongs to a Project and User
- An Invoice belongs to a Client and has many Invoice Items
- An Invoice Item can reference a Time Entry

## Development

### Running in Development Mode

```bash
# Terminal 1: Start Laravel development server
php artisan serve

# Terminal 2: Watch and compile assets
npm run dev
```

### Code Structure

```
app/
├── Http/Controllers/
│   ├── ClientController.php
│   ├── ProjectController.php
│   ├── TimeEntryController.php
│   ├── InvoiceController.php
│   └── DashboardController.php
├── Models/
│   ├── User.php
│   ├── Client.php
│   ├── Project.php
│   ├── TimeEntry.php
│   ├── Invoice.php
│   └── InvoiceItem.php
database/migrations/
resources/views/
routes/web.php
```

## API Routes

The application uses web routes with authentication middleware:

- `GET /dashboard` - Dashboard with analytics
- Resource routes for:
  - `/clients` - Client CRUD operations
  - `/projects` - Project CRUD operations
  - `/time-entries` - Time entry CRUD operations
  - `/invoices` - Invoice CRUD operations

Special routes:
- `POST /time-entries/{id}/stop` - Stop a running timer
- `GET /invoices/{id}/pdf` - Download invoice as PDF

## Usage Guide

### Creating a Client

1. Navigate to Clients section
2. Click "Add New Client"
3. Fill in client details (name, email, company, etc.)
4. Set default hourly rate (optional)
5. Save

### Starting a Timer

1. Navigate to Time Entries
2. Click "Start Timer"
3. Select project
4. Add description
5. Timer starts automatically
6. Click "Stop" when done

### Generating an Invoice

1. Navigate to Invoices
2. Click "Create Invoice"
3. Select client
4. Choose unbilled time entries
5. Review and adjust amounts
6. Set due date and tax rate
7. Save as draft or mark as sent
8. Download PDF for sending to client

## Testing

```bash
# Run tests
php artisan test
```

## License

This project is open-sourced software licensed under the MIT license.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues, questions, or contributions, please open an issue in the repository.
