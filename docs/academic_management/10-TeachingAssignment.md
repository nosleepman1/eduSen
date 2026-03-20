# Academic Management

# 10 - Teaching Assignment

---

# Objectif

Le module **Teaching Assignment** permet d'affecter un enseignant à une matière du programme pédagogique (Curriculum) pour une classe donnée durant une année scolaire.

Il constitue le lien officiel entre :

- un enseignant ;
- une classe ;
- une matière ;
- une année scolaire.

Aucun cours, aucune évaluation, aucune présence, aucune note ou bulletin ne peut exister sans une affectation valide.

---

# Position dans le domaine

AcademicYear

↓

Curriculum

↓

TeachingAssignment

↓

Teacher

↓

Classroom

↓

Timetable

↓

Attendance

↓

Evaluation

↓

Grade

↓

Bulletin

---

# Philosophie

Une affectation répond à la question :

> Quel enseignant enseigne quelle matière dans quelle classe pendant quelle année scolaire ?

L'affectation ne définit pas les horaires.

Les horaires sont gérés par le module Timetable.

---

# Exemple

Mathématiques

↓

6ème

↓

Classe 6A

↓

Professeur M. Ndiaye

↓

Année 2026-2027

Une nouvelle ligne TeachingAssignment est créée.

---

# Responsabilités

Le module permet :

- affecter un enseignant ;
- modifier une affectation ;
- réaffecter un enseignant ;
- terminer une affectation ;
- archiver une affectation ;
- restaurer une affectation ;
- consulter la charge pédagogique d'un enseignant.

---

# Structure métier

Une affectation possède :

- une année scolaire ;
- une classe ;
- un curriculum ;
- un enseignant ;
- un rôle ;
- une date de début ;
- une date de fin (optionnelle) ;
- un statut.

---

# Types de rôle

Une affectation peut être :

## Titulaire

Responsable principal de l'enseignement.

---

## Remplaçant

Intervient temporairement.

---

## Co-enseignant

Partage l'enseignement avec un autre professeur.

---

# Règles métier

## RTA-001

Une affectation appartient obligatoirement à une année scolaire.

---

## RTA-002

Une affectation appartient obligatoirement à une classe.

---

## RTA-003

Une affectation appartient obligatoirement à un Curriculum.

---

## RTA-004

Une affectation appartient obligatoirement à un enseignant.

---

## RTA-005

Le Curriculum doit être compatible avec la classe.

Exemple :

Curriculum

↓

Terminale S

↓

Impossible de l'affecter à une classe de 6ème.

---

## RTA-006

Un enseignant peut enseigner plusieurs matières.

---

## RTA-007

Un enseignant peut enseigner dans plusieurs classes.

---

## RTA-008

Une classe possède plusieurs affectations.

Une par matière.

---

## RTA-009

Une matière ne peut avoir qu'un seul titulaire actif dans une même classe.

Les co-enseignants restent autorisés.

---

## RTA-010

Toutes les suppressions utilisent SoftDeletes.

---

## RTA-011

Le nombre total d'enseignants distincts actifs et affectés dans l'établissement (`Teacher` via `TeachingAssignment`) ne doit jamais dépasser la limite `max_teachers` définie dans le plan d'abonnement actif de l'établissement (`SubscriptionPlan`).

---

# Relations

TeachingAssignment appartient à :

- AcademicYear
- Classroom
- Curriculum
- Teacher

TeachingAssignment possède :

- plusieurs Timetables
- plusieurs Attendances
- plusieurs Evaluations

Toutes les notes et bulletins découlent indirectement de cette affectation.

---

# Compatibilité automatique

Lors d'une affectation :

Le système vérifie automatiquement :

AcademicYear

=

Classroom.AcademicYear

=

Curriculum.AcademicYear

Si ce n'est pas le cas :

↓

Refus.

---

Le système vérifie également :

Level de la classe

=

Level du Curriculum

Sinon :

↓

Refus.

---

Si une spécialisation existe :

Classe

↓

Terminale S

Curriculum

↓

Terminale L

↓

Refus.

---

# Charge horaire

La charge horaire hebdomadaire d'un enseignant est calculée automatiquement.

Elle correspond à :

Σ weekly_hours

de tous les Curriculums actifs auxquels il est affecté.

Aucune donnée n'est stockée.

Le calcul est effectué dynamiquement.

---

# Changement d'enseignant

Si un enseignant quitte l'établissement :

L'affectation est clôturée.

Une nouvelle affectation est créée.

L'historique reste conservé.

Les évaluations précédentes restent liées à l'ancien enseignant.

---

# Workflow

Sélection du professeur

↓

Sélection de la classe

↓

Sélection du Curriculum

↓

Validation métier

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

TeachingAssignmentService

Responsabilités :

- création ;
- modification ;
- changement d'enseignant ;
- clôture ;
- restauration ;
- calcul de charge horaire ;
- vérification de compatibilité.

---

# Routes API

GET /teaching-assignments

GET /teaching-assignments/{id}

POST /teaching-assignments

PUT /teaching-assignments/{id}

PATCH /teaching-assignments/{id}/close

PATCH /teaching-assignments/{id}/restore

DELETE /teaching-assignments/{id}

GET /teachers/{id}/workload

---

# Cas limites

Affecter deux titulaires pour la même matière dans la même classe.

↓

Refus.

---

Affecter un enseignant à un Curriculum d'un autre niveau.

↓

Refus.

---

Affecter un Curriculum de Terminale S à une classe Terminale L.

↓

Refus.

---

Supprimer une affectation utilisée par des évaluations.

↓

Archivage uniquement.

---

Changer d'enseignant en milieu d'année.

↓

Nouvelle affectation.

L'ancienne est clôturée.

Toutes les évaluations restent rattachées à l'ancien enseignant.

---

# Evolutions futures

Le module pourra supporter :

- répartition des heures entre co-enseignants ;
- enseignement partagé entre établissements ;
- gestion des vacataires ;
- historique complet des affectations ;
- import automatique des affectations ;
- optimisation de la charge pédagogique.

---

# Conclusion

TeachingAssignment constitue le pivot opérationnel de l'activité pédagogique.

Il relie les enseignants aux classes à travers le programme officiel de l'établissement.

Tous les modules pédagogiques d'EduSen (emploi du temps, évaluations, notes, présences et bulletins) s'appuient sur cette entité, garantissant une traçabilité complète et une cohérence métier sur l'ensemble du système.
