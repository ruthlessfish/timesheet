# Timeshit
**Time Tracking Application for Freelance Web Developers**
*pronounced like "time sheet"

A comprehensive time-tracking application built with Laravel 12, designe to manage clients, projects, time entries, and invoices.

- Create and manage client profiles
- Store contact information and billing details
- Set default hourly rates per client## About Laravel
- Track client activity status

### Project Management

- Organize work by client and project
- Set project-specific hourly rates and budgets
- Track project status (active, on hold, completed, cancelled)
- Monitor project timelines with start and end dates
- View total hours and earnings per project

### Time Tracking

- Start/stop timer for time entries
- Manual time entry with description
- Track billable and non-billable hours
- Automatic duration calculation
- Override hourly rates per time entry
- Associate time entries with specific projects

### Invoice Generation
- Create invoices from time entries
- Automatic invoice numbering
- Support for tax calculations
- Multiple invoice statuses (draft, sent, paid, overdue, cancelled)
- Export invoices to PDF
- Track invoice items and totals

## Tech Stack
- **Framework:** Laravel 12
- **Authentication:** Laravel Breeze## Contributing
- **Database:** SQLite (configurable for MySQL/PostgreSQL)
- **Frontend:** Blade Templates + Tailwind CSS
- **Charts:** Chart.js
- **PDF Generation:** DomPDF
- **Build Tool:** Vite

## Security Vulnerabilities
If you discover a security vulnerability within Laravel, please send an e-mail to Shane Pearson via [bubbafoley@gmail.com](mailto:bubbafoley@gmail.com). All security vulnerabilities will be promptly addressed.

### Requirements
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- SQLite (or MySQL/PostgreSQL)

## Installation
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
