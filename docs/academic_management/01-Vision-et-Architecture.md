# Vision & Architecture

## Vision

Le domaine Academic Management représente le cœur métier d'EduSen.

Il est responsable de toute l'organisation pédagogique de l'établissement depuis sa configuration jusqu'à la production des bulletins.

Toutes les fonctionnalités académiques reposent sur ce domaine.

---

# Architecture générale

Le module respecte l'architecture Laravel MVC.

```
HTTP Request

↓

Route

↓

Controller

↓

Form Request

↓

Service

↓

Model

↓

API Resource

↓

HTTP Response
```

Les traitements asynchrones sont délégués aux Events, Listeners et Jobs.

---

# Philosophie

Le système privilégie :

- simplicité
- évolutivité
- maintenabilité
- automatisation
- séparation des responsabilités

---

# Structure fonctionnelle

```
Academic Management
│
├── Setup Wizard
├── Configuration
├── Academic Years
├── Academic Periods
├── Levels
├── Classrooms
├── Subjects
├── Teaching Assignments
├── Timetables
├── Enrollments
├── Attendance
├── Evaluations
├── Grades
└── Bulletins
```

---

# Principes métier

- une école possède plusieurs années scolaires
- une seule année scolaire peut être active
- une année scolaire contient plusieurs périodes
- une période appartient à une seule année scolaire
- un niveau possède plusieurs classes
- une classe appartient à une seule année scolaire
- une matière peut être enseignée dans plusieurs classes
- un enseignant peut enseigner plusieurs matières
- un élève est inscrit via Enrollment
- Enrollment constitue le pivot académique
- toutes les données sont isolées par tenant

---

# Gestion des suppressions

Le projet n'effectue jamais de suppression physique.

Toutes les entités métier utilisent :

- SoftDeletes

Une suppression correspond à un archivage.

---

# Notifications

Les notifications concernent uniquement :

- Directeur
- Personnel administratif
- Enseignants

Les élèves et les parents ne possèdent pas encore d'espace utilisateur.

---

# Sécurité

Toutes les routes sont protégées.

Toutes les actions sont soumises aux autorisations définies dans le système IAM.

Toutes les requêtes sont filtrées par tenant.

---

# API

Toutes les fonctionnalités sont exposées via une API REST versionnée.

```
routes/

api.php

↓

api/v1/

academic.php
configuration.php
enrollment.php
attendance.php
evaluation.php
bulletin.php
```

---

# Conventions

- UUID pour toutes les entités principales.
- SoftDeletes partout.
- Transactions pour les opérations critiques.
- Services pour la logique métier.
- Form Requests pour les validations.
- API Resources pour les réponses.
- Events pour les traitements métiers.
- Listeners pour les traitements asynchrones.
- Redis pour les Jobs.

---

# Objectif

Construire un domaine académique robuste, extensible et capable de répondre aux besoins des établissements scolaires tout en restant simple à maintenir.
