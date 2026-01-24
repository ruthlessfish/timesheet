# Laravel Time Tracking Application

This is a time-tracking application built with Laravel for freelance web developers.

## Features
- User authentication and authorization
- Client management
- Project management per client
- Time entry tracking with start/stop functionality
- Invoice generation with PDF export
- Reports and charts for time analytics
- Dashboard with visual insights

## Project Structure
- Models: User, Client, Project, TimeEntry, Invoice
- Controllers: Resource controllers for CRUD operations
- Views: Blade templates with Tailwind CSS
- Database: MySQL/PostgreSQL compatible

## Development Guidelines
- Follow Laravel best practices
- Use Eloquent ORM for database operations
- Apply proper validation on all forms
- Implement authorization policies where needed
- Keep controllers thin, use service classes for complex logic
- Write migrations for all database changes
