# Users & Roles Management

Ce domaine gère les acteurs de l'établissement d'EduSen, leurs rôles, leurs permissions et leurs relations. 

Il interagit directement avec le domaine **Academic Management** (l'inscription dépend des élèves, et la planification des enseignants).

---

# Organisation des fichiers

```
users_and_roles/
│
├── 00_README.md
├── 01-Personnel-and-Roles.md
├── 02-Teacher-Management.md
├── 03-Student-Management.md
└── 04-Guardian-Management.md
```

---

# Description des modules

## 01 — Personnel & Roles

Gestion du personnel administratif et de service de l'établissement. Il définit le système IAM de rôles (Directeur, Secrétaire, Comptable, Surveillant) qui permet de déléguer les actions (comme inscrire des élèves ou modifier la configuration).

## 02 — Teacher Management

Gestion des profils d'enseignants. Dans EduSen, un enseignant est obligatoirement un membre du personnel (`Personnel`) ayant le rôle `TEACHER` et une fiche d'informations professionnelles complémentaires (`Teacher`).

## 03 — Student Management

Gestion des fiches d'élèves. L'élève n'a pas d'accès de connexion propre au système pour le MVP. Ses informations sont gérées par le personnel administratif de l'établissement.

## 04 — Guardian Management

Gestion des parents et tuteurs. Il permet d'associer un ou plusieurs parents à un élève, de définir le contact principal pour les urgences et de centraliser les communications futures.
