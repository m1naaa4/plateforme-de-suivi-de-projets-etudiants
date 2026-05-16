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
Créer le fichier .env à partir de .env.example, puis configurer la base de données :

cp .env.example .env
php artisan key:generate
Lancer les migrations et, si besoin, les seeders :

php artisan migrate --seed
Compiler le frontend :

npm run dev
Lancer l’application :

php artisan serve
Comptes et rôles
Le système distingue plusieurs profils :

Administrateur : gestion globale des utilisateurs, projets et paramètres
Enseignant : suivi et organisation des projets, tâches et livrables
Étudiant : consultation et suivi de ses projets, groupes, tâches et livrables
Fonctionnement général
Les projets sont suivis via un tableau de bord visuel
Les tâches permettent de suivre l’avancement d’un projet
Les groupes centralisent les étudiants associés à un projet
Les livrables servent à suivre les documents remis
L’interface affiche l’avancement et les statuts de manière claire et moderne
Interface
L’interface a été conçue pour être :

moderne
claire
responsive
professionnelle
cohérente visuellement sur tout le site
Multilingue
Le site supporte plusieurs langues :

Français
Anglais
Espagnol
Arabe
Développement
Pour lancer le projet en mode développement :

npm run dev
php artisan serve
Build production
npm run build
Licence
Projet académique / de fin d’année.
npm install
