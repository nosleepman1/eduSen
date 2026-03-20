# Academic Management

# 19 - Bulletin

---

# Version

1.0.0

---

# Objectif

Le module **Bulletin** représente le document officiel de synthèse des résultats académiques d'un élève pour une période donnée.

Il récapitule :

- les notes obtenues par matière ;
- les moyennes par matière ;
- la moyenne générale ;
- le rang dans la classe ;
- les appréciations par matière ;
- l'appréciation générale du conseil de classe.

Le Bulletin est généré à partir des Grades et Enrollments d'une période académique.

---

# Position dans le domaine

```
AcademicYear

↓

AcademicPeriod

↓

Enrollment

↓

Grade (par matière)

↓

Bulletin

↓

BulletinLine (1 par matière)
```

---

# Philosophie

Le Bulletin répond à la question :

> Quel est le bilan académique de cet élève sur cette période ?

Il est généré automatiquement à partir des données existantes.

Il ne contient aucune information calculée différemment de ce que les Grades et Evaluations ont produit.

Un Bulletin ne peut être généré que si toutes les évaluations de la période sont clôturées.

---

# Responsabilités

Le module permet :

- générer un bulletin pour un élève sur une période ;
- générer les bulletins de toute une classe en masse ;
- ajouter une appréciation générale (conseil de classe) ;
- valider un bulletin ;
- publier un bulletin ;
- verrouiller un bulletin ;
- archiver un bulletin ;
- exporter un bulletin en PDF.

---

# Structure métier

## Bulletin

Un Bulletin possède :

- une période académique (AcademicPeriod) ;
- un enrollment (Enrollment) ;
- une moyenne générale calculée (moyenne individuelle de l'élève) ;
- un rang dans la classe ;
- un rang sur l'effectif total ;
- un effectif de classe ;
- une appréciation du conseil de classe (optionnelle) ;
- une mention (optionnelle) ;
- un statut ;
- une date de génération ;
- une date de publication.

*Note d'Architecture* : Les statistiques globales (comme la moyenne de la classe, la plus haute et la plus basse moyenne) ne sont pas stockées dans la table `bulletins` pour éviter toute duplication inutile. Elles sont calculées dynamiquement ou lues depuis la table de cache `classroom_period_stats` lors de l'affichage ou de l'export.

---

## BulletinLine

Un BulletinLine représente la ligne d'une matière dans le bulletin.

Un BulletinLine possède :

- un Bulletin ;
- un Curriculum (matière + coefficient) ;
- la moyenne de l'élève dans cette matière sur la période ;
- le coefficient de la matière ;
- les points obtenus (moyenne × coefficient) ;
- l'appréciation de l'enseignant (optionnelle) ;
- un statut.

*Note d'Architecture* : De même, la moyenne de classe, la note maximale et la note minimale de la matière ne sont pas stockées dans `bulletin_lines`. Elles sont lues depuis la table globale de statistiques `classroom_period_stats` de la classe, évitant de dupliquer ces mêmes valeurs pour tous les élèves.

---

# Etats du Bulletin

## Draft

Le bulletin a été généré.

Les données ne sont pas encore validées.

Les appréciations peuvent être ajoutées.

---

## Validated

Le directeur ou le responsable pédagogique a validé le bulletin.

Les données sont verrouillées.

---

## Published

Le bulletin est publié.

Il est visible par les parties autorisées.

---

## Locked

Le bulletin est définitivement verrouillé.

Aucune modification n'est possible.

---

## Archived

Le bulletin est archivé.

Les données restent conservées grâce au SoftDelete.

---

# Règles métier

## RB-001

Un Bulletin appartient obligatoirement à une AcademicPeriod.

---

## RB-002

Un Bulletin appartient obligatoirement à un Enrollment.

---

## RB-003

Un seul Bulletin peut exister par couple AcademicPeriod + Enrollment.

---

## RB-004

Un Bulletin ne peut être généré que si toutes les Evaluations de la période sont dans l'état Closed ou Locked.

---

## RB-005

Un Bulletin ne peut être généré que si tous les Grades de l'élève sur la période ont un statut Graded, Absent ou Dispensed.

Aucun Grade Pending ne doit exister.

---

## RB-006

La moyenne générale est calculée à partir des BulletinLines et des coefficients du Curriculum.

---

## RB-007

Le rang est calculé en comparant les moyennes générales de tous les élèves de la même Classroom sur la même AcademicPeriod.

---

## RB-008

Un Bulletin Locked ne peut jamais être modifié.

---

## RB-009

Toutes les suppressions utilisent SoftDeletes.

---

## RB-010

La génération en masse d'une classe entière est exécutée dans un Job Redis (traitement asynchrone).

---

# Calcul du Bulletin

## Étape 1 — Récupération des Grades

Pour chaque matière du Curriculum de la classe :

Récupération de tous les Grades de l'élève sur la période.

---

## Étape 2 — Calcul de la moyenne par matière

```
Σ (grade_i.valeur × evaluation_i.coefficient)
────────────────────────────────────────────── = Moyenne matière
Σ evaluation_i.coefficient
```

---

## Étape 3 — Calcul des points par matière

```
Moyenne matière × Coefficient Curriculum = Points matière
```

---

## Étape 4 — Calcul de la moyenne générale

```
Σ Points matière
──────────────── = Moyenne générale
Σ Coefficients
```

---

## Étape 5 — Calcul du rang

```
Rang = Position de l'élève dans la liste des moyennes générales de la classe

(Classement décroissant)
```

---

## Étape 6 — Détermination de la mention

Selon les seuils définis par l'établissement :

```
Moyenne ≥ 16 → Très Bien

Moyenne ≥ 14 → Bien

Moyenne ≥ 12 → Assez Bien

Moyenne ≥ 10 → Passable

Moyenne < 10 → Insuffisant
```

---

# Génération en masse

Le directeur peut générer les bulletins de toute une classe en une seule action.

```
Classe 6A

↓

Générer les bulletins de la période

↓

Job Redis (asynchrone)

↓

Génération de 45 Bulletins

↓

Notification de fin de traitement
```

---

# Export PDF

Chaque Bulletin peut être exporté en PDF.

Le PDF contient :

- l'en-tête de l'établissement ;
- les informations de l'élève ;
- le tableau des matières avec notes et appréciations ;
- la moyenne générale et le rang ;
- l'appréciation du conseil de classe ;
- la signature du directeur.

---

# Appréciation du conseil de classe

Après la génération des bulletins :

Le directeur ou le responsable pédagogique peut saisir une appréciation générale.

Exemples :

```
"Bon trimestre. Continuez vos efforts."

"Des progrès sensibles. Peut mieux faire en mathématiques."

"Résultats insuffisants. Un entretien avec les parents est recommandé."
```

---

# Relations

Bulletin appartient à :

- AcademicPeriod
- Enrollment

Bulletin possède :

- plusieurs BulletinLines (1 par matière du Curriculum)

---

# Workflow

Toutes les Evaluations de la période → Closed / Locked

↓

Tous les Grades → Graded / Absent / Dispensed

↓

Déclenchement de la génération (manuelle ou automatique)

↓

Calcul des moyennes par matière

↓

Calcul de la moyenne générale

↓

Calcul du rang

↓

Création du Bulletin (Draft)

↓

Ajout des appréciations

↓

Validation par le directeur

↓

Publication

↓

Export PDF

---

# Services

```
BulletinService
```

Responsabilités :

- génération d'un bulletin individuel ;
- génération en masse d'une classe ;
- calcul des moyennes ;
- calcul du rang ;
- ajout des appréciations ;
- validation ;
- publication ;
- export PDF ;
- archivage.

---

# Routes API

```
GET    /bulletins

GET    /bulletins/{id}

POST   /bulletins/generate

POST   /bulletins/generate-class

PATCH  /bulletins/{id}/validate

PATCH  /bulletins/{id}/publish

PATCH  /bulletins/{id}/lock

PATCH  /bulletins/{id}/restore

DELETE /bulletins/{id}

GET    /enrollments/{id}/bulletins

GET    /academic-periods/{id}/bulletins

GET    /bulletins/{id}/export-pdf

GET    /classrooms/{id}/bulletin-summary
```

---

# Événements

- BulletinGenerated
- BulletinValidated
- BulletinPublished
- BulletinLocked
- BulletinExported
- BulletinArchived

---

# Notifications

À la publication du bulletin :

- notification au directeur ;
- notification au personnel administratif autorisé.

Les notifications aux parents et élèves sont prévues pour les évolutions futures.

---

# Cas limites

Générer un bulletin alors que des Grades sont encore Pending.

↓

Refus.

---

Générer un bulletin alors que des Evaluations sont encore Open.

↓

Refus.

---

Modifier un Bulletin Locked.

↓

Refus.

---

Créer deux Bulletins pour le même élève sur la même période.

↓

Refus.

---

Générer le bulletin d'un élève transféré en cours de période.

↓

Le bulletin inclut uniquement les Grades rattachés à l'Enrollment actif sur la période concernée.

---

# Indicateurs

Le module alimente :

- tableau de bord des résultats par classe ;
- classement des élèves ;
- taux de réussite par période ;
- évolution des résultats sur l'année ;
- statistiques par matière et par niveau ;
- comparaison inter-classes.

---

# Évolutions futures

Le module est prévu pour supporter :

- portail parents pour consulter les bulletins en ligne ;
- portail élèves pour accéder aux résultats ;
- notifications SMS / email aux parents à la publication ;
- bulletins annuels (synthèse de toutes les périodes) ;
- bulletins de compétences ;
- conseils de classe numériques ;
- archivage légal longue durée ;
- conformité avec les référentiels nationaux.

---

# Conclusion

Le Bulletin constitue la synthèse officielle du parcours académique d'un élève sur une période.

Il est le document le plus visible et le plus important produit par EduSen.

Sa fiabilité repose sur la cohérence de l'ensemble de la chaîne : Curriculum → Evaluation → Grade → Bulletin.

Sa génération marque la clôture officielle d'une période académique.
