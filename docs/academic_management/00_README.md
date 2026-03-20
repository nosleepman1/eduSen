# EduSen - Academic Management

> Academic Management est le domaine fonctionnel responsable de toute la gestion pédagogique d'un établissement scolaire dans EduSen.

Il fournit les fonctionnalités permettant de configurer la structure académique d'une école, gérer les inscriptions des élèves, les affectations des enseignants, les emplois du temps, les évaluations, les notes, les bulletins ainsi que les présences.

Ce module est entièrement intégré au système multi-tenant d'EduSen et respecte l'architecture Laravel MVC utilisée dans le projet.

---

# Objectifs

- Configurer rapidement une école après sa création.
- Automatiser la configuration académique initiale.
- Gérer plusieurs années scolaires.
- Gérer les périodes académiques.
- Organiser les niveaux et classes.
- Gérer les matières.
- Affecter les enseignants.
- Inscrire les élèves.
- Gérer les présences.
- Gérer les évaluations.
- Calculer les notes.
- Générer les bulletins.
- Fournir une API REST complète.

---

# Architecture

Le module suit l'architecture Laravel MVC.

- Controllers
- Models
- Services
- Form Requests
- API Resources
- Policies
- Events
- Listeners
- Jobs
- Notifications
- Repositories (si logique complexe ou réutilisée)

Toute la logique métier est centralisée dans les Services.

---

# Technologies

- Laravel
- PostgreSQL
- UUID
- Sanctum
- Redis Queue
- Blade Mail
- SoftDeletes
- API REST

---

# Organisation

```
academic-management/
│
├── 00_README.md
├── 01-Vision-et-Architecture.md
├── 02-Academic-Setup-Wizard.md
├── 03-Academic-Configuration.md
├── 04-Academic-Year.md
├── 05-Academic-Period.md
├── 06-Level.md
├── 07-Classroom.md
├── 08-Subject.md
├── 09-Curriculum.md
├── 10-TeachingAssignment.md
├── 11-Timetable.md
├── 12-ScheduleEntry.md
├── 13-LessonSession.md
├── 14-AttendanceRecord.md
├── 15-LessonContent.md
├── 16-Enrollment.md
├── 17-Evaluation.md
├── 18-Grade.md
├── 19-Bulletin.md
└── 20-SchoolCalendar.md
```

---

# Modules

Le domaine est composé de quatre grandes parties.

## Academic Setup

Configuration initiale de l'établissement.

## Academic Configuration

Gestion des paramètres académiques.

## Academic Operations

Gestion quotidienne des activités scolaires.

## Academic Reporting

Production des résultats et bulletins.

---

# Principes

- Une seule année scolaire active.
- Toutes les opérations sont rattachées à une année scolaire.
- Toutes les opérations sont rattachées à un tenant.
- Toutes les suppressions utilisent SoftDeletes.
- Les Services contiennent toute la logique métier.
- Les Controllers restent fins.
- Les traitements asynchrones utilisent Redis.
- Les Repositories s'utilisent seulement si nécessaire.

---

# Dépendances

Le module dépend de :

- IAM
- Tenant Management
- Notification System
- Audit System
- Registration Number Service

---

# Hors périmètre

Ce document ne couvre pas :

- Paiement des frais scolaires
- Facturation
- Bibliothèque
- Transport scolaire
- Communication
- Portail Parents
- Portail Élèves

Ces modules seront documentés séparément.

---

# Auteur

Projet EduSen
Academic Management Specification
Version 1.0
