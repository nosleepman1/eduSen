# Academic Management

# 18 - Grade

---

# Version

1.0.0

---

# Objectif

Le module **Grade** représente la note individuelle d'un élève pour une évaluation donnée.

Il constitue l'unité atomique du résultat académique.

Chaque note est rattachée à un élève (via son Enrollment), à une Evaluation et à une période académique.

---

# Position dans le domaine

```
Evaluation

↓

Grade (1 par élève × 1 par évaluation)

↓

Calcul des moyennes

↓

Bulletin
```

---

# Philosophie

Un Grade répond à la question :

> Quelle note cet élève a-t-il obtenu à cette évaluation ?

Un Grade est unique pour un couple :

```
Evaluation + Enrollment
```

Il est généralement créé automatiquement (avec note nulle) lors de l'ouverture d'une Evaluation.

L'enseignant saisit ensuite la note.

---

# Responsabilités

Le module permet :

- créer un Grade (automatiquement ou manuellement) ;
- saisir la note ;
- modifier la note tant que l'Evaluation est Open ;
- marquer un élève comme absent à l'évaluation ;
- dispenser un élève de l'évaluation ;
- consulter les notes par élève, par classe ou par matière ;
- calculer les moyennes.

---

# Structure métier

Un Grade possède :

- une évaluation (Evaluation) ;
- un enrollment (Enrollment) ;
- une valeur de note (nullable tant que non saisie) ;
- un statut de présence à l'évaluation ;
- un commentaire de l'enseignant (optionnel) ;
- un statut.

---

# Statuts de présence à l'évaluation

## Graded

La note a été saisie.

---

## Absent

L'élève était absent lors de l'évaluation.

La note reste nulle ou à zéro selon les règles de l'établissement.

---

## Dispensed

L'élève était officiellement dispensé.

La note n'est pas prise en compte dans la moyenne.

---

## Pending

Le Grade a été créé automatiquement mais la note n'a pas encore été saisie.

---

# Règles métier

## RG-001

Un Grade appartient obligatoirement à une Evaluation.

---

## RG-002

Un Grade appartient obligatoirement à un Enrollment.

---

## RG-003

Un seul Grade peut exister pour un couple Evaluation + Enrollment.

---

## RG-004

La note doit être comprise entre 0 et le barème de l'Evaluation.

---

## RG-005

Un Grade ne peut plus être modifié si l'Evaluation est dans l'état Locked.

---

## RG-006

Un Grade ne peut plus être modifié si l'Evaluation est Archivée.

---

## RG-007

L'Enrollment doit appartenir à la même Classroom que le TeachingAssignment de l'Evaluation.

---

## RG-008

Toutes les suppressions utilisent SoftDeletes.

---

## RG-009

Un Grade Dispensed n'est pas pris en compte dans le calcul des moyennes.

---

## RG-010

Un Grade Absent est pris en compte avec la valeur 0 sauf règle contraire définie par l'établissement.

---

# Calcul des moyennes

## Moyenne par matière sur une période

```
Pour un élève E, une matière M, une période P :

Σ (grade_i.valeur × evaluation_i.coefficient)
────────────────────────────────────────────── = Moyenne matière
Σ evaluation_i.coefficient

(Seuls les Grades dont le statut est Graded ou Absent sont inclus)
```

---

## Moyenne générale sur une période

```
Σ (moyenne_matiere_j × curriculum_j.coefficient)
──────────────────────────────────────────────── = Moyenne générale
Σ curriculum_j.coefficient
```

---

## Rang

Le rang est calculé en comparant la moyenne générale de l'élève avec celles de tous les élèves de la même classe sur la même période.

Le rang est stocké dans le Bulletin, pas dans le Grade.

---

# Génération automatique

Lors de l'ouverture d'une Evaluation :

```
EvaluationOpened

↓

Pour chaque Enrollment actif dans la Classroom :

Grade créé avec statut Pending et note = null
```

L'enseignant n'a plus qu'à saisir les notes.

---

# Relations

Grade appartient à :

- Evaluation
- Enrollment

Grade est utilisé par :

- Bulletin (pour les calculs de synthèse)

---

# Workflow

Ouverture de l'Evaluation

↓

Génération automatique des Grades (Pending)

↓

Saisie de la note par l'enseignant

↓

Statut → Graded

↓

Clôture de l'Evaluation

↓

Verrouillage des Grades

↓

Calcul des moyennes

---

# Services

```
GradeService
```

Responsabilités :

- génération automatique des Grades ;
- saisie de la note ;
- modification ;
- calcul des moyennes par matière ;
- calcul des moyennes générales ;
- calcul du rang ;
- archivage.

---

# Routes API

```
GET    /grades

GET    /grades/{id}

POST   /grades

PUT    /grades/{id}

DELETE /grades/{id}

PATCH  /grades/{id}/restore

GET    /evaluations/{id}/grades

GET    /enrollments/{id}/grades

POST   /evaluations/{id}/grades/bulk

GET    /enrollments/{id}/averages
```

---

# Événements

- GradeCreated
- GradeUpdated
- GradeArchived
- ClassAverageCalculated

---

# Cas limites

Saisir une note supérieure au barème.

↓

Refus.

---

Saisir une note négative.

↓

Refus.

---

Modifier un Grade verrouillé.

↓

Refus.

---

Créer deux Grades pour le même élève sur la même évaluation.

↓

Refus.

---

Calculer la moyenne d'un élève avec des évaluations Pending (non saisies).

↓

Avertissement.

La moyenne est partielle.

Le bulletin ne peut pas être généré en présence de notes Pending.

---

# Indicateurs

Le module alimente :

- moyenne par matière ;
- moyenne générale ;
- rang dans la classe ;
- note la plus haute / la plus basse ;
- taux de réussite ;
- évolution sur plusieurs périodes.

---

# Évolutions futures

Le module est prévu pour supporter :

- appréciations qualitatives ;
- notation par compétences ;
- grilles d'évaluation multiples ;
- repêchage automatique ;
- pondération dynamique selon le type d'évaluation ;
- export des notes en Excel / PDF.

---

# Conclusion

Le Grade constitue l'unité fondamentale du résultat scolaire.

Il permet de mesurer la progression individuelle de chaque élève à travers les évaluations, de calculer les moyennes et de produire des bulletins complets et fiables.

Sa cohérence est critique pour l'ensemble du processus d'évaluation d'EduSen.
