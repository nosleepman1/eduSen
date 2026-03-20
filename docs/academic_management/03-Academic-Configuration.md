# Academic Configuration

## Introduction

Le module **Academic Configuration** permet au directeur de modifier la configuration académique de son établissement après l'exécution du Setup Wizard.

Toutes les modifications sont centralisées dans un seul espace accessible depuis les paramètres.

---

# Objectifs

Permettre au directeur de :

- créer une nouvelle année scolaire ;
- modifier une année scolaire ;
- ouvrir une année scolaire ;
- clôturer une année scolaire ;
- gérer les périodes académiques ;
- gérer les niveaux ;
- gérer les classes ;
- gérer les matières.

---

# Accès

```
Paramètres

↓

Configuration Académique
```

---

# Organisation

La page est organisée en plusieurs sections.

## Années scolaires

Liste des années.

Actions :

- créer ;
- modifier ;
- archiver ;
- restaurer ;
- définir comme année active ;
- clôturer.

---

## Périodes académiques

Liste des périodes.

Actions :

- créer ;
- modifier ;
- archiver ;
- restaurer.

---

## Calendrier scolaire

Configuration du calendrier de l'établissement et des congés.

Actions :

- consulter le calendrier de l'année active ;
- ajouter un congé / fête (fixe, variable ou vacances scolaires) ;
- modifier un congé / fête (décalage ou renommage) ;
- définir ou ajuster l'intervalle d'une fête variable estimée ;
- supprimer un congé.

---

## Niveaux

Liste des niveaux.

Actions :

- créer ;
- modifier ;
- archiver ;
- restaurer.

---

## Classes

Liste des classes.

Actions :

- créer ;
- modifier ;
- archiver ;
- restaurer.

---

## Matières

Liste des matières.

Actions :

- créer ;
- modifier ;
- modifier le coefficient (attention : les matieres ont des coefficients different selon le niveau);
- archiver ;
- restaurer.

---

# Règles métier

Une seule année scolaire peut être active.

Une période appartient toujours à une année scolaire.

Une classe appartient toujours à un niveau.

Une matière peut être utilisée dans plusieurs affectations.

---

# Archivage

Toutes les suppressions utilisent SoftDeletes.

Le système ne supprime jamais définitivement :

- une matière ;
- une classe ;
- un niveau ;
- une période ;
- une année scolaire.

---

# Vérifications

Avant tout archivage, le système vérifie les dépendances.

Exemple

Une matière utilisée dans une évaluation.

↓

Impossible de la supprimer définitivement.

↓

Archivage uniquement.

---

# Tableau de bord de configuration

Le module affiche également un état général.

Exemple

```
✓ Année scolaire active

✓ 3 périodes

✓ 12 niveaux

✓ 28 classes

✓ 18 matières
```

Des alertes peuvent être affichées.

Exemple

```
⚠ Aucun enseignant affecté.

⚠ Aucune classe créée pour le niveau 2nde.

⚠ Année scolaire bientôt terminée.
```

---

# Historique

Toutes les modifications sont enregistrées par le système d'audit déjà présent dans EduSen.

---

# Routes

Le module possède ses propres routes API.

```
/configuration/*
```

Celles-ci sont organisées dans :

```
routes/api/v1/configuration.php
```

---

# Services

Toute la logique métier est centralisée dans :

```
AcademicConfigurationService
```

Ce service orchestre les différents services spécialisés :

- AcademicYearService
- AcademicPeriodService
- SchoolCalendarService
- LevelService
- ClassroomService
- SubjectService

---

# Événements

Les principales actions déclenchent des événements.

Exemples :

- AcademicYearCreated
- AcademicYearActivated
- AcademicYearClosed
- AcademicPeriodCreated
- SchoolCalendarCreated
- HolidayEventCreated
- HolidayEventUpdated
- LevelCreated
- ClassroomCreated
- SubjectCreated

---

# Notifications

Les notifications concernent uniquement le personnel autorisé lorsque cela est nécessaire.

Les élèves et les parents ne sont pas concernés.

---

# Objectif final

Le module doit permettre au directeur de conserver une configuration académique évolutive sans devoir relancer le Setup Wizard.

Toutes les opérations doivent être sécurisées, historisées et compatibles avec le multi-tenant.
