# Academic Management

# 08 - Subject

---

# Objectif

Le module **Subject** représente le référentiel des matières enseignées dans un établissement.

Une matière constitue une discipline académique indépendante de toute classe, de tout niveau ou de toute année scolaire.

Exemples :

- Mathématiques
- Français
- Anglais
- Physique
- Chimie
- SVT
- Histoire-Géographie
- Philosophie
- Informatique

Une matière n'est créée qu'une seule fois.

Son utilisation dans un niveau ou une série est définie par le module **Curriculum**.

---

# Position dans le domaine

```
Subject

        │

        ▼

Curriculum

        │

        ▼

TeachingAssignment

        │

        ▼

Evaluation

        │

        ▼

Grade
```

---

# Responsabilités

Le module permet :

- créer une matière ;
- modifier une matière ;
- archiver une matière ;
- restaurer une matière ;
- gérer son code ;
- gérer sa visibilité.

---

# Structure métier

Une matière possède :

- un nom ;
- un code ;
- une description (optionnelle) ;
- un statut actif/inactif.

Exemple :

```
Nom

Mathématiques

Code

MATH

Description

Discipline scientifique fondamentale.
```

---

# Règles métier

## RS-001

Une matière est créée une seule fois dans un établissement.

---

## RS-002

Deux matières ne peuvent avoir le même code dans un même tenant.

---

## RS-003

Le nom d'une matière doit être unique dans le tenant.

---

## RS-004

Une matière peut exister sans être encore utilisée dans un programme pédagogique.

---

## RS-005

Une matière ne peut jamais être supprimée définitivement.

Toutes les suppressions utilisent SoftDeletes.

---

# Relations

Une matière peut appartenir à plusieurs programmes pédagogiques.

```
Subject

↓

Curriculum
```

Une matière peut être enseignée dans plusieurs niveaux.

Une matière peut être enseignée dans plusieurs séries.

Une matière peut être enseignée par plusieurs enseignants.

---

# Workflow

Création

↓

Validation

↓

Transaction

↓

Création

↓

Audit

↓

Réponse API

---

# Services

Le module est piloté par :

```
SubjectService
```

Responsabilités :

- création ;
- modification ;
- archivage ;
- restauration.

---

# Routes API

```
GET     /subjects

GET     /subjects/{id}

POST    /subjects

PUT     /subjects/{id}

DELETE  /subjects/{id}

PATCH   /subjects/{id}/restore
```

---

# Cas limites

Créer deux matières portant le même code.

↓

Refus.

---

Créer deux matières portant le même nom.

↓

Refus.

---

Archiver une matière déjà utilisée dans plusieurs programmes.

↓

Autorisé.

Les données historiques restent conservées.

---

# Evolutions futures

Le module est prévu pour supporter :

- catégories de matières ;
- couleurs d'affichage ;
- icônes ;
- matières optionnelles ;
- matières nationales ;
- matières locales.

---

# Conclusion

Subject constitue le référentiel unique des disciplines enseignées.

Il ne contient aucune règle pédagogique.

Toutes les règles d'enseignement sont définies dans le module Curriculum.
