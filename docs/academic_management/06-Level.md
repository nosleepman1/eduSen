# Academic Management

# 06 - Level

---

# Objectif

Le module **Level** représente les niveaux pédagogiques d'un établissement.

Un niveau regroupe plusieurs classes partageant le même programme pédagogique.

Exemples :

- CI
- CP
- CE1
- CE2
- CM1
- CM2
- 6ème
- 5ème
- 4ème
- 3ème
- Seconde
- Première
- Terminale

Le système ne doit jamais considérer une classe comme un niveau.

Le niveau constitue un modèle pédagogique tandis que les classes représentent les groupes d'élèves.

---

# Position dans le domaine

```
Academic Year

        │

        ▼

     Level (avec Specialization : comme Terminale S, Terminale L, ...)

        │

        ▼

    Classroom

        │

        ▼

Enrollment
```

---

# Responsabilités

Le module permet :

- créer un niveau ;
- modifier un niveau ;
- archiver un niveau ;
- restaurer un niveau ;
- définir son ordre pédagogique ;
- gérer sa visibilité.

---

# Exemples

```
Niveau

6ème

↓

Classes

6A
6B
6C
6D
```

---

```
Niveau

Terminale

↓


Classes (avec Specialization : Terminale S, Terminale L, ...)

TS1
TS2
TL
```

---

# Structure métier

Un niveau possède :

- un nom ;
- un code ;
- un ordre d'affichage ;
- une description (optionnelle) ;
- une année scolaire ;
- une clé étrangère vers une Spécialisation (`specialization_id`, optionnelle) ;
- un état actif/inactif.

---

# Règles métier

## RL-000

Un niveau peut être lié à une Spécialisation (optionnelle, également appelée Série) via la clé `specialization_id`.

La Spécialisation permet de segmenter un niveau en différentes filières pédagogiques (ex: Terminale S pour Scientifique, Terminale L pour Littéraire, Terminale G pour Gestion). 

Lorsqu'un niveau comporte des spécialisations :
- Les classes physiques (`Classroom`) sont associées au couple Niveau + Spécialisation.
- Le programme de cours (`Curriculum`) et les coefficients des matières sont définis de manière distincte pour chaque spécialisation.
- Les inscriptions (`Enrollment`) et affectations (`TeachingAssignment`) en découlent directement.

Cette notion est également détaillée dans la spécification du programme (`09-Curriculum`).

---

## RL-001

Un niveau appartient obligatoirement à une année scolaire.

---

## RL-002

Deux niveaux d'une même année ne peuvent avoir le même nom.

---

## RL-003

Le code doit être unique dans une année scolaire.

---

## RL-004

Un niveau peut exister sans classe.

Cette situation est normale lors de la configuration.

---

## RL-005

Un niveau ne peut être supprimé définitivement.

Toutes les suppressions utilisent SoftDeletes.

---

## RL-006

L'ordre pédagogique doit être unique.

Exemple

```
1

↓

CI

2

↓

CP

3

↓

CE1
```

---

# Relations

Un Level possède :

- plusieurs Classrooms.

Un Level peut être lié à :

- plusieurs Subjects (selon les règles de l'établissement).

---

# Workflow

Création

↓

Validation

↓

Transaction

↓

Création du niveau

↓

Audit

↓

Retour API

---

# Services

Le module est piloté par :

```
LevelService
```

Responsabilités :

- création ;
- modification ;
- archivage ;
- restauration ;
- réorganisation des niveaux.

---

# Routes API

```
GET    /levels

GET    /levels/{id}

POST   /levels

PUT    /levels/{id}

DELETE /levels/{id}

PATCH  /levels/{id}/restore

PATCH  /levels/reorder
```

---

# Cas limites

Créer deux niveaux portant le même nom.

↓

Refus.

---

Modifier un niveau utilisé par plusieurs classes.

↓

Autorisé.

Les classes restent inchangées.

---

Archiver un niveau contenant plusieurs classes.

↓

Les classes restent archivables indépendamment.

Le système avertit simplement le directeur.

---

# Evolutions futures

Le module pourra supporter :

- cycles pédagogiques ;
- niveaux nationaux ;
- import ministériel ;
- modèles par pays ;
- synchronisation avec les référentiels officiels.

---

# Conclusion

Le Level constitue le référentiel pédagogique principal de l'établissement.

Il sert de base à l'organisation des classes et de l'ensemble de la scolarité.
