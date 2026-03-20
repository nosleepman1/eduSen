# Academic Management

# 05 - Academic Period

---

# Objectif

Le module **Academic Period** permet de découper une année scolaire en plusieurs périodes pédagogiques.

Une période académique représente une subdivision officielle de l'année scolaire utilisée pour organiser les évaluations, calculer les moyennes, générer les bulletins et suivre la progression des élèves.

Une période ne peut jamais exister sans appartenir à une année scolaire.

---

# Dépendance

Une période appartient obligatoirement à une seule AcademicYear.

```
AcademicYear

    │

    ├── AcademicPeriod
```

---

# Cas d'utilisation

Le module permet de :

- créer une période ;
- modifier une période ;
- supprimer (archiver) une période ;
- restaurer une période ;
- ouvrir une période ;
- clôturer une période ;
- réordonner les périodes ;
- générer automatiquement les périodes d'une année scolaire.

---

# Types de périodes

EduSen supporte plusieurs organisations académiques.

## Semestres

```
Semestre 1

Semestre 2
```

---

## Trimestres

```
Trimestre 1

Trimestre 2

Trimestre 3
```

---

## Quarters

```
Quarter 1

Quarter 2

Quarter 3

Quarter 4
```

---

## Personnalisé

L'établissement définit librement :

- le nom ;
- les dates ;
- l'ordre.

---

# Structure métier

Chaque période possède :

- un nom ;
- une année scolaire ;
- une date de début ;
- une date de fin ;
- un ordre ;
- un statut.

---

# Etats possibles

## Brouillon

Période créée mais non ouverte.

---

## Active

Les évaluations peuvent être créées.

Les présences sont enregistrées.

Les notes sont saisies.

Une seule période peut être active à la fois.

---

## Clôturée

Plus aucune note ne peut être ajoutée.

Les bulletins deviennent calculables.

Les évaluations sont verrouillées.

---

## Archivée

La période est masquée.

Les données restent conservées grâce au SoftDelete.

---

# Règles métier

## RP-001

Une période appartient obligatoirement à une année scolaire.

---

## RP-002

Les dates d'une période doivent être comprises dans les dates de son année scolaire.

---

## RP-003

Deux périodes d'une même année ne peuvent jamais se chevaucher.

---

## RP-004

Une seule période peut être active simultanément.

---

## RP-005

Une période clôturée devient non modifiable.

---

## RP-006

Une période possédant des évaluations ne peut jamais être supprimée physiquement.

---

## RP-007

Toutes les suppressions utilisent SoftDeletes.

---

# Génération automatique

Lors de la création de l'année scolaire, le système peut générer automatiquement les périodes.

Exemple.

Mode Trimestre

↓

Création automatique :

```
Trimestre 1

Trimestre 2

Trimestre 3
```

Les dates sont proposées automatiquement mais restent modifiables avant validation.

---

# Ouverture d'une période

L'ouverture d'une période :

- désactive automatiquement la période active précédente ;
- autorise la création des évaluations ;
- autorise la saisie des notes ;
- autorise les présences.

Cette opération est transactionnelle.

---

# Clôture d'une période

Avant la clôture, le système vérifie :

- la cohérence des évaluations ;
- les traitements académiques en cours définis par les règles métier.

Une fois clôturée :

- les notes sont verrouillées ;
- les évaluations deviennent non modifiables ;
- les bulletins peuvent être générés.

---

# Relations

AcademicPeriod possède notamment des relations avec :

- AcademicYear ;
- Evaluation ;
- Grade ;
- Bulletin.

Toutes les évaluations sont rattachées à une période.

---

# Services

Le module est piloté par :

```
AcademicPeriodService
```

Responsabilités :

- création ;
- modification ;
- activation ;
- clôture ;
- archivage ;
- restauration ;
- génération automatique.

---

# Routes API

```
GET    /academic-periods

GET    /academic-periods/{id}

POST   /academic-periods

PUT    /academic-periods/{id}

PATCH  /academic-periods/{id}/activate

PATCH  /academic-periods/{id}/close

PATCH  /academic-periods/{id}/restore

DELETE /academic-periods/{id}
```

---

# Evènements

Le module déclenche notamment :

- AcademicPeriodCreated
- AcademicPeriodUpdated
- AcademicPeriodActivated
- AcademicPeriodClosed
- AcademicPeriodArchived
- AcademicPeriodRestored

---

# Cas limites

Créer une période dont les dates dépassent l'année scolaire.

↓

Refus.

---

Créer une période qui chevauche une autre.

↓

Refus.

---

Activer une période alors qu'une autre est déjà active.

↓

L'ancienne est automatiquement désactivée dans une transaction.

---

Clôturer une période déjà clôturée.

↓

Aucune modification.

Retour d'une erreur métier explicite.

---

# Evolutions futures

Le module est conçu pour supporter :

- des calendriers académiques avancés ;
- des périodes spécifiques par cycle ;
- des périodes d'examens indépendantes ;
- des périodes de rattrapage ;
- des périodes d'activités parascolaires.

---

# Conclusion

Academic Period constitue le découpage officiel de l'année scolaire.

Toutes les évaluations, notes et bulletins s'appuient sur cette entité.

La cohérence des périodes garantit la fiabilité de l'ensemble du domaine académique.
