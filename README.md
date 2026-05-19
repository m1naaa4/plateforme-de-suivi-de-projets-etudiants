# Student Project Tracking Platform

Web application dedicated to monitoring academic projects carried out by students in groups.

---

## Description

This platform allows users to manage and monitor student projects, groups, tasks, and deliverables in a centralized environment.

The website provides several user profiles with role-based access, including:

- Administrator
- Teacher
- Student

The main goal is to facilitate academic project supervision, group coordination, and overall project progress visualization.

---

## Features

- Secure authentication
- User and role management
- Project creation and tracking
- Student group management
- Task management
- Deliverable management
- Dashboard with indicators and charts
- Multilingual interface
- Responsive design for mobile, tablet, and desktop

---

## Tech Stack

| Technology | Usage |
|---|---|
| Laravel | Backend Framework |
| Vue.js | Frontend Framework |
| Vite | Frontend Build Tool |
| MySQL / MariaDB | Database |
| Laravel Sanctum | Authentication |

---

## Project Structure

```bash
app/
├── Http/Controllers/Api   # API Controllers
├── Models                 # Eloquent Models

database/
├── migrations             # Database Structure
├── seeders                # Test Data

resources/
├── js                     # Vue Application
├── css                    # Global Styles

routes/
├── api.php                # API Routes
├── web.php                # Web Routes
```

---

## Installation

### Requirements

- PHP 8.x
- Composer
- Node.js and npm
- MySQL or MariaDB

### Setup Instructions

```bash
# Clone the repository
git clone <repository-url>

# Navigate to the project folder
cd plateforme-pfa

# Install backend dependencies
composer install

# Install frontend dependencies
npm install

# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seeders
php artisan migrate --seed

# Build frontend assets
npm run build

# Start the development server
php artisan serve
```

---

## User Roles

### Administrator
- Manage users and roles
- Monitor all projects
- Access dashboards and statistics

### Teacher
- Supervise student projects
- Manage tasks and deliverables
- Track project progress

### Student
- Access assigned projects
- Submit deliverables
- Manage project tasks

---

## Main Modules

- Authentication System
- Project Management
- Group Management
- Task Management
- Deliverable Management
- Dashboard & Statistics
- Multilingual Support

---

## Responsive Design

The platform is fully responsive and optimized for:

- Desktop
- Tablet
- Mobile Devices

---

## License

This project was developed for educational purposes.
