
# Plateforme de suivi des projets étudiants

Application web dédiée au suivi des projets académiques réalisés par les étudiants en groupe.

## Description

Cette plateforme permet de gérer et suivre les projets étudiants, les groupes, les tâches et les livrables dans un environnement centralisé.  
Le site propose plusieurs profils utilisateurs avec des accès adaptés aux rôles, notamment :

- Administrateur
- Enseignant
- Étudiant

L’objectif est de faciliter le suivi pédagogique, la coordination des groupes et la visualisation de l’avancement global des projets.

## Fonctionnalités

- Authentification sécurisée
- Gestion des utilisateurs et des rôles
- Création et suivi des projets
- Gestion des groupes d’étudiants
- Gestion des tâches
- Gestion des livrables
- Tableau de bord avec indicateurs et graphiques
- Interface multilingue
- Design responsive pour mobile, tablette et desktop

## Stack technique

- Backend : Laravel
- Frontend : Vue.js
- Build frontend : Vite
- Base de données : MySQL / MariaDB
- Authentification : Laravel Sanctum

## Structure du projet

- `app/Http/Controllers/Api` : contrôleurs API
- `app/Models` : modèles Eloquent
- `database/migrations` : structure de la base de données
- `database/seeders` : données de test
- `resources/js` : application Vue
- `resources/css` : styles globaux
- `routes/api.php` : routes API
- `routes/web.php` : routes web

## Installation

### Prérequis

- PHP 8.x
- Composer
- Node.js et npm
- MySQL ou MariaDB

### Étapes

```bash
git clone <url-du-repo>
cd plateforme-pfa
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
