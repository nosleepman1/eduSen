# Academic Management

# 17 - Evaluation

---

# Version

1.0.0

---

# Objectif

Le module **Evaluation** représente une évaluation académique officielle réalisée pour une classe, une matière et une période donnée.

Pour le MVP d'EduSen, deux types d'évaluation sont gérés :

- **Devoir** : évaluation continue réalisée en cours de période.
- **Examen** : évaluation terminale réalisée en fin de période.

Une Evaluation constitue le cadre officiel dans lequel les notes (Grades) des élèves sont saisies.

---

# Position dans le domaine

```
AcademicYear

↓

AcademicPeriod

↓

Curriculum

↓

TeachingAssignment

↓

Evaluation

↓

Grade (1 par élève inscrit)

↓

Bulletin
```

---

# Philosophie

Une Evaluation répond à la question :

> Quelle épreuve a été organisée pour quelle matière, dans quelle classe, pendant quelle période ?

Elle peut être liée à une LessonSession (évaluation effectuée en cours) ou être indépendante (examen terminal).

L'Evaluation définit le cadre (barème, date, type).

Le Grade enregistre le résultat individuel de chaque élève.

---

# Types d'évaluation (MVP)

## Devoir

Évaluation continue réalisée pendant la période active.

Peut être associée à une LessonSession spécifique.

Plusieurs devoirs peuvent exister par matière et par période.

---

## Examen

Évaluation terminale réalisée généralement en fin de période.

Un seul examen par matière et par période est recommandé.

L'examen contribue fortement à la moyenne finale selon le coefficient défini.

---

# Responsabilités

Le module permet :

- créer une évaluation (Devoir ou Examen) ;
- modifier une évaluation ;
- ouvrir la saisie des notes ;
- clôturer la saisie des notes ;
- verrouiller définitivement une évaluation ;
- archiver une évaluation ;
- restaurer une évaluation.

---

# Structure métier

Une Evaluation possède :

- une année scolaire (AcademicYear) ;
- une période académique (AcademicPeriod) ;
- une affectation pédagogique (TeachingAssignment) ;
- un type (Devoir / Examen) ;
- un titre ;
- une description (optionnelle) ;
- une date de l'évaluation ;
- une note maximale (barème) ;
- un coefficient de l'évaluation (optionnel — complète le coefficient du Curriculum) ;
- une LessonSession liée (optionnelle) ;
- un statut.

---

# Etats

## Draft

L'évaluation est créée mais la saisie des notes n'est pas encore ouverte.

---

## Open

La saisie des notes est ouverte.

Les Grades peuvent être créés ou modifiés.

---

## Closed

La saisie des notes est clôturée.

Les Grades ne sont plus modifiables.

Les moyennes peuvent être calculées.

---

## Locked

L'évaluation est définitivement verrouillée.

Aucune modification n'est possible.

Les données servent au calcul final du bulletin.

---

## Archived

L'évaluation est archivée.

Les données restent conservées grâce au SoftDelete.

---

# Règles métier

## REV-001

Une Evaluation appartient obligatoirement à une AcademicYear.

---

## REV-002

Une Evaluation appartient obligatoirement à une AcademicPeriod.

---

## REV-003

Une Evaluation appartient obligatoirement à un TeachingAssignment.

---

## REV-004

La date de l'évaluation doit être comprise dans les dates de la AcademicPeriod.

---

## REV-005

La AcademicPeriod doit être Active pour permettre la création d'une évaluation.

---

## REV-006

La note maximale (barème) doit être strictement positive.

---

## REV-007

Un Examen ne peut être créé que sur une période Active.

---

## REV-008

Une Evaluation Locked ne peut jamais être modifiée.

---

## REV-009

Une Evaluation ne peut pas être supprimée définitivement si elle possède des Grades.

Archivage uniquement.

---

## REV-010

Toutes les suppressions utilisent SoftDeletes.

---

## REV-011

La LessonSession liée (optionnelle) doit appartenir au même TeachingAssignment.

---

# Barème et coefficient

## Barème

Définit la note maximale de l'évaluation.

Exemple :

```
Barème : 20
```

Toutes les notes saisies dans les Grades doivent être comprises entre 0 et le barème.

---

## Coefficient de l'évaluation

Complète le coefficient de la matière défini dans le Curriculum.

Exemple :

```
Matière : Mathématiques

Coefficient Curriculum : 4

Évaluation : Devoir 1

Coefficient Devoir : 1

Évaluation : Examen

Coefficient Examen : 2

Calcul :

Moyenne = (Note Devoir × 1 + Note Examen × 2) / (1 + 2)

↓

Moyenne pondérée × Coefficient Curriculum = Points matière
```

---

# Calcul de la moyenne de l'élève

Pour une matière donnée sur une période :

```
Σ (note_i × coeff_eval_i) / Σ coeff_eval_i = Moyenne matière
```

Cette moyenne sera utilisée par le module Grade et Bulletin.

---

# Saisie des notes

Lors de l'ouverture d'une Evaluation :

Le système génère automatiquement un Grade vide pour chaque élève Enrolled dans la classe.

L'enseignant saisit ensuite les notes.

```
Evaluation ouverte

↓

Génération automatique de Grades (note = null)

↓

Saisie par l'enseignant

↓

Clôture

↓

Verrouillage
```

---

# Relations

Evaluation appartient à :

- AcademicYear
- AcademicPeriod
- TeachingAssignment
- LessonSession (nullable)

Evaluation possède :

- plusieurs Grades (1 par élève inscrit)

---

# Workflow

Création de l'évaluation (Draft)

↓

Ouverture de la saisie (Open)

↓

Génération automatique des Grades

↓

Saisie des notes par l'enseignant

↓

Clôture de la saisie (Closed)

↓

Verrouillage définitif (Locked)

↓

Calcul des moyennes

↓

Génération du Bulletin

---

# Services

```
EvaluationService
```

Responsabilités :

- création ;
- modification ;
- ouverture de la saisie ;
- clôture ;
- verrouillage ;
- archivage ;
- restauration ;
- génération automatique des Grades ;
- calcul des moyennes.

---

# Routes API

```
GET    /evaluations

GET    /evaluations/{id}

POST   /evaluations

PUT    /evaluations/{id}

PATCH  /evaluations/{id}/open

PATCH  /evaluations/{id}/close

PATCH  /evaluations/{id}/lock

PATCH  /evaluations/{id}/restore

DELETE /evaluations/{id}

GET    /teaching-assignments/{id}/evaluations

GET    /academic-periods/{id}/evaluations
```

---

# Événements

- EvaluationCreated
- EvaluationOpened
- EvaluationClosed
- EvaluationLocked
- EvaluationArchived

---

# Notifications

À la clôture d'une évaluation :

- notification au directeur ;
- notification au personnel administratif autorisé.

---

# Cas limites

Créer une évaluation sur une période Clôturée.

↓

Refus.

---

Modifier une évaluation Locked.

↓

Refus.

---

Saisir une note supérieure au barème.

↓

Refus (géré dans le module Grade).

---

Archiver une évaluation possédant des Grades.

↓

Archivage autorisé.

Les Grades sont également archivés en cascade.

---

Créer deux Examens pour la même matière sur la même période.

↓

Avertissement.

Non bloquant mais signalé au directeur.

---

# Indicateurs

Le module alimente :

- taux de réussite par évaluation ;
- moyenne de classe ;
- note minimale / maximale ;
- écart-type ;
- comparaison entre devoirs et examen.

---

# Évolutions futures

Le module est conçu pour accueillir :

- évaluations orales ;
- évaluations pratiques ;
- QCM numérique ;
- correction automatique ;
- rattrapage d'examen ;
- évaluations par compétences ;
- notation par lettres (A, B, C…) ;
- anonymisation des copies.

---

# Conclusion

L'Evaluation constitue le cadre officiel de mesure des apprentissages des élèves.

Pour le MVP, elle couvre les deux types fondamentaux : **Devoir** et **Examen**.

Tous les résultats individuels (Grades) et les documents de synthèse (Bulletins) dépendent de la cohérence et de l'intégrité de ce module.
