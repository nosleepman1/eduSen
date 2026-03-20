# Academic Management

# 09 - Curriculum

---

# Objectif

Le module Curriculum représente le programme pédagogique officiel d'un établissement pour une année scolaire donnée.

Il définit :

- les matières enseignées ;
- les niveaux concernés ;
- les éventuelles spécialisations ;
- les coefficients ;
- les volumes horaires hebdomadaires ;
- les matières obligatoires ;
- les matières optionnelles.

Le Curriculum constitue la source officielle utilisée par :

- les emplois du temps ;
- les affectations d'enseignants ;
- les évaluations ;
- les calculs de moyennes ;
- les bulletins.

---

# Position dans le domaine

AcademicYear

↓

Level

↓

Curriculum

↓

Subject

↓

TeachingAssignment

↓

Evaluation

↓

Grade

↓

Bulletin

---

# Philosophie

Une matière existe une seule fois.

Exemple :

Mathématiques

↓

Subject

Le Curriculum détermine ensuite :

- où elle est enseignée ;
- avec quel coefficient ;
- combien d'heures par semaine ;
- dans quel niveau ;
- dans quelle spécialisation.

---

# Exemples

## Collège

6ème

↓

Mathématiques

Coefficient : 4

6h/semaine

---

6ème

↓

Français

Coefficient : 4

5h/semaine

---

## Lycée

Terminale

↓

S

↓

Mathématiques

Coefficient : 7

8h/semaine

---

Terminale

↓

L

↓

Philosophie

Coefficient : 7

6h/semaine

---

# Structure métier

Un Curriculum possède :

- une année scolaire ;
- un niveau ;
- une spécialisation éventuelle (`specialization_id`, ForeignKey) ;
- une matière ;
- un coefficient ;
- un volume horaire hebdomadaire ;
- un ordre d'affichage ;
- un statut.

---

# Règles métier

## RCU-001

Une ligne de curriculum appartient obligatoirement à une année scolaire.

---

## RCU-002

Une ligne de curriculum appartient obligatoirement à un niveau.

---

## RCU-003

Une ligne de curriculum appartient obligatoirement à une matière.

---

## RCU-004

La spécialisation (`specialization_id`) est facultative.

Elle est renseignée uniquement lorsque le niveau comporte des séries (ex: Terminale S1, Terminale L2, Terminale G).

---

## RCU-005

Une matière ne peut apparaître qu'une seule fois pour le quadruplet :

- AcademicYear
- Level
- Specialization (via specialization_id, nullable)
- Subject

---

## RCU-006

Le coefficient doit être strictement positif.

---

## RCU-007

Le volume horaire hebdomadaire doit être strictement positif.

---

## RCU-008

Toutes les suppressions utilisent SoftDeletes.

---

# Coefficient

Le coefficient intervient dans :

- calcul des moyennes ;
- calcul des rangs ;
- génération des bulletins.

Exemple :

Maths

Coef 5

↓

Note 15

↓

75 points

---

Français

Coef 3

↓

Note 15

↓

45 points

---

# Volume horaire hebdomadaire

Le volume horaire représente le nombre d'heures à enseigner chaque semaine.

Exemple :

Mathématiques

↓

6h/semaine

Français

↓

5h/semaine

Anglais

↓

3h/semaine

---

Ce volume servira notamment :

- à construire les emplois du temps ;
- à vérifier la charge des enseignants ;
- à produire les statistiques pédagogiques.

---

# Matières obligatoires

Une matière obligatoire :

- doit être enseignée ;
- doit apparaître dans les bulletins ;
- participe aux moyennes.

Exemple :

Mathématiques

Français

Histoire

---

# Matières optionnelles

Une matière optionnelle peut :

- être choisie ;
- être activée selon les besoins de l'établissement.

Exemple :

Espagnol

Allemand

Arts

---

# Relations

Curriculum appartient à :

- AcademicYear
- Level
- Specialization (nullable)
- Subject

---

Curriculum est utilisé par :

- TeachingAssignment
- Timetable
- Evaluation
- Grade
- Bulletin

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

CurriculumService

Responsabilités :

- création ;
- modification ;
- archivage ;
- restauration ;
- duplication vers une nouvelle année scolaire.

---

# Duplication annuelle

Lors de la préparation d'une nouvelle rentrée scolaire :

Le directeur peut copier le curriculum précédent.

Exemple :

2026-2027

↓

Copier

↓

2027-2028

---

Puis effectuer les ajustements nécessaires.

Cette fonctionnalité évite une ressaisie complète.

---

# Routes API

GET /curriculums

GET /curriculums/{id}

POST /curriculums

PUT /curriculums/{id}

DELETE /curriculums/{id}

PATCH /curriculums/{id}/restore

POST /curriculums/duplicate

---

# Cas limites

Ajout d'une matière déjà présente dans le même programme.

↓

Refus.

---

Coefficient nul.

↓

Refus.

---

Volume horaire nul.

↓

Refus.

---

Suppression d'un curriculum déjà utilisé.

↓

Archivage uniquement.

---

# Evolutions futures

Le module pourra supporter :

- groupes de matières ;
- matières à choix ;
- spécialités avancées ;
- options facultatives ;
- programmes ministériels ;
- import automatique de référentiels nationaux.

---

# Conclusion

Le Curriculum constitue la définition officielle du programme pédagogique de l'établissement.

Toutes les affectations d'enseignants, évaluations, notes et bulletins reposent sur cette entité.

Il représente le cœur pédagogique d'EduSen.
