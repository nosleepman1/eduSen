# Academic Management

# 15 - LessonContent

---

# Objectif

Le module **LessonContent** représente le contenu pédagogique renseigné par l'enseignant lors ou après une séance de cours (LessonSession).

Il constitue la trace officielle de ce qui a réellement été enseigné durant chaque cours.

Il permet au directeur, aux élèves et aux parties prenantes de suivre l'avancement du programme pédagogique.

---

# Position dans le domaine

```
LessonSession

↓

LessonContent

↓

Evaluation (peut référencer le contenu enseigné)
```

---

# Philosophie

Un LessonContent répond à la question :

> Qu'est-ce qui a été enseigné lors de ce cours ?

Il permet de documenter :

- le chapitre traité ;
- la progression dans le programme ;
- les objectifs pédagogiques atteints.

Un LessonContent est lié à une seule LessonSession.

Une LessonSession peut posséder plusieurs entrées de contenu.

---

# Responsabilités

Le module permet :

- renseigner le contenu enseigné ;
- modifier le contenu ;
- indiquer la progression dans le programme ;
- archiver le contenu ;
- restaurer un contenu archivé.

---

# Structure métier

Un LessonContent possède :

- une séance de cours (LessonSession) ;
- un titre / chapitre ;
- une description détaillée (optionnelle) ;
- un numéro de progression dans le programme (optionnel) ;
- un statut.

Exemple :

```
Séance : Mathématiques — Lundi 08/09/2026

Contenu : Chapitre 1 — Fonctions linéaires

Description : Introduction aux fonctions f(x) = ax + b.
Exercices d'application pages 12 à 15.

Progression : 3 / 36 semaines
```

---

# Règles métier

## RLC-001

Un LessonContent appartient obligatoirement à une LessonSession.

---

## RLC-002

Un LessonContent ne peut être créé que si la LessonSession est dans l'état InProgress ou Completed.

---

## RLC-003

Un LessonContent ne peut plus être modifié si la LessonSession est Archivée.

---

## RLC-004

Toutes les suppressions utilisent SoftDeletes.

---

# Relations

LessonContent appartient à :

- LessonSession

---

# Workflow

Démarrage de la séance (InProgress)

↓

L'enseignant renseigne le contenu

↓

Validation

↓

Enregistrement

↓

Clôture de la séance

↓

Verrouillage du contenu

---

# Services

```
LessonContentService
```

Responsabilités :

- création ;
- modification ;
- archivage ;
- restauration ;
- récupération de la progression du programme.

---

# Routes API

```
GET    /lesson-contents

GET    /lesson-contents/{id}

POST   /lesson-contents

PUT    /lesson-contents/{id}

DELETE /lesson-contents/{id}

PATCH  /lesson-contents/{id}/restore

GET    /lesson-sessions/{id}/contents
```

---

# Événements

- LessonContentCreated
- LessonContentUpdated
- LessonContentArchived

---

# Cas limites

Créer un contenu pour une LessonSession Archived.

↓

Refus.

---

Modifier un contenu après archivage de la séance.

↓

Refus.

---

# Évolutions futures

Le module est conçu pour accueillir :

- pièces jointes pédagogiques (PDF, images…) ;
- liens vers des ressources externes ;
- alignement automatique avec les référentiels nationaux ;
- indicateur de complétion du programme ;
- alertes de retard sur la progression.

---

# Conclusion

LessonContent constitue la mémoire pédagogique de chaque séance.

Il permet au directeur de s'assurer que le programme est respecté et aux élèves de suivre la progression de leurs cours.
