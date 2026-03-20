# Academic Management

# 04 - Academic Year

---

# Version

1.0.0

---

# Introduction

L'Année Académique (Academic Year) constitue l'entité racine de l'ensemble du domaine académique d'EduSen.

Aucune opération pédagogique ne peut exister indépendamment d'une année scolaire.

Elle représente le cadre temporel officiel dans lequel sont organisées toutes les activités d'un établissement :

- inscriptions des élèves ;
- affectations des enseignants ;
- emplois du temps ;
- évaluations ;
- présences ;
- notes ;
- bulletins ;
- statistiques.

L'Academic Year agit comme un conteneur logique garantissant que toutes les données pédagogiques appartiennent à un même cycle scolaire.

Chaque établissement (tenant) possède son propre ensemble d'années scolaires totalement isolé des autres établissements.

---

# Objectifs métier

Le module Academic Year a pour objectifs de :

- créer les années scolaires de l'établissement ;
- maintenir un historique complet des années précédentes ;
- définir l'année scolaire actuellement active ;
- empêcher les incohérences temporelles ;
- servir de référence à l'ensemble des modules académiques ;
- préparer automatiquement la création des périodes académiques ;
- faciliter l'ouverture d'une nouvelle rentrée scolaire.

---

# Position dans le domaine métier

```
Tenant

│

└── Academic Years

      │

      ├── Academic Periods

      ├── Levels

      ├── Classrooms

      ├── Subjects

      ├── Enrollments

      ├── Timetables

      ├── Evaluations

      ├── Grades

      └── Bulletins
```

L'Academic Year représente donc la racine de toute l'organisation académique.

---

# Responsabilités

Le module Academic Year est responsable de :

- créer une nouvelle année scolaire ;
- modifier ses informations ;
- définir l'année courante ;
- clôturer une année ;
- rouvrir une année si les règles métier l'autorisent ;
- archiver une année scolaire ;
- restaurer une année archivée ;
- déclencher la génération automatique des périodes académiques ;
- garantir qu'une seule année soit active.

Tout traitement concernant une année scolaire devra impérativement passer par l'AcademicYearService.

Aucun autre composant de l'application n'est autorisé à modifier directement le cycle de vie d'une année scolaire.

---

# Cycle de vie

Une année scolaire évolue selon plusieurs états.

```
Création

↓

Configuration

↓

Active

↓

Clôturée

↓

Archivée

↓

(Restaurée si nécessaire)
```

Chaque transition est contrôlée par des règles métier.

Aucun changement d'état ne doit être effectué directement depuis un Controller.

Toutes les transitions passent exclusivement par le Service métier.

---

# Etats métier

## Brouillon

Année nouvellement créée.

Les périodes académiques peuvent encore être générées ou modifiées.

Les niveaux et les classes peuvent être configurés.

Aucune inscription ne doit encore exister.

---

## Active

Une seule année scolaire peut posséder cet état.

Toutes les opérations académiques utilisent automatiquement cette année.

Les nouvelles inscriptions y sont rattachées.

Les emplois du temps utilisent cette année.

Les évaluations utilisent cette année.

Les bulletins utilisent cette année.

---

## Clôturée

Une année clôturée devient en lecture seule.

Les inscriptions sont interdites.

Les nouvelles évaluations sont interdites.

Les nouvelles présences sont interdites.

Les nouvelles affectations sont interdites.

Les données historiques restent consultables.

---

## Archivée

L'année est masquée des interfaces courantes.

Les données restent conservées grâce au SoftDelete.

Elle peut être restaurée selon les règles définies par le système.

---

# Décisions d'architecture

## Pourquoi une seule année active ?

Cette règle simplifie considérablement l'ensemble du domaine académique.

Les modules suivants dépendent toujours d'une seule année :

- Enrollment
- Attendance
- Evaluation
- Grade
- Bulletin
- Timetable

Autoriser plusieurs années actives obligerait chaque requête métier à déterminer quelle année utiliser.

Le système impose donc une seule année active par établissement.

---

## Pourquoi utiliser SoftDeletes ?

Une année scolaire constitue un élément historique.

Elle peut être nécessaire pour :

- consulter d'anciens bulletins ;
- retrouver une ancienne inscription ;
- effectuer un audit ;
- produire des statistiques.

Sa suppression physique entraînerait une perte irréversible de données.

Toutes les suppressions sont donc des archivages.

---

## Pourquoi Academic Year est la racine ?

Toutes les données pédagogiques appartiennent à une année scolaire.

Cette relation permet :

- l'isolation des données ;
- la génération des statistiques ;
- la duplication d'une année ;
- la clôture complète d'un cycle scolaire.

---

# Règles métier

Les règles suivantes sont obligatoires.

## RM-001

Un établissement doit posséder au minimum une année scolaire.

---

## RM-002

Une seule année scolaire peut être active.

---

## RM-003

Deux années scolaires ne peuvent jamais avoir des dates qui se chevauchent.

---

## RM-004

Une année scolaire doit posséder une date de début et une date de fin.

---

## RM-005

La date de début doit être strictement antérieure à la date de fin.

---

## RM-006

Une année scolaire clôturée devient non modifiable.

---

## RM-007

Une année scolaire utilisée par d'autres modules ne peut jamais être supprimée définitivement.

---

## RM-008

Toutes les opérations critiques doivent être exécutées dans une transaction.

---

## RM-009

Toutes les opérations doivent être historisées par le système d'audit déjà présent dans EduSen.

---

## RM-010

Toutes les requêtes doivent être filtrées par le tenant courant.

---

## RM-011

L'année scolaire de l'établissement commence par défaut le 1er octobre de l'année courante et se termine le 31 juillet de l'année suivante.

---

## RM-012

Le système doit supporter des dates de fin de cours différenciées par classe (`Classroom`). Par exemple, les classes hors examen national (comme les classes intermédiaires) peuvent s'arrêter de manière anticipée en juin (ex: 30 juin), tandis que les classes d'examen national (ex: 3ème, Terminale) se poursuivent jusqu'au 31 juillet. Les processus de génération de séances de cours (`LessonSession`) et de calcul de bulletins doivent respecter ces dates de fin par classe.

---

# Contraintes techniques

Le modèle AcademicYear devra utiliser :

- UUID
- SoftDeletes
- HasFactory

Toutes les dates seront stockées au format DATE.

Les timestamps utiliseront UTC.

---

# Partie 2 — Cas d'utilisation

Le module expose les cas d'utilisation suivants.

| Code      | Cas d'utilisation            |
| --------- | ---------------------------- |
| UC-AY-001 | Créer une année scolaire     |
| UC-AY-002 | Modifier une année scolaire  |
| UC-AY-003 | Définir l'année active       |
| UC-AY-004 | Clôturer une année scolaire  |
| UC-AY-005 | Réouvrir une année scolaire  |
| UC-AY-006 | Archiver une année scolaire  |
| UC-AY-007 | Restaurer une année scolaire |

Les chapitres suivants décrivent chacun de ces cas d'utilisation de manière détaillée.

---

# UC-AY-001 — Créer une année scolaire

## Objectif

Permettre au directeur de créer une nouvelle année scolaire qui servira de conteneur principal à toutes les données académiques.

## Acteurs

- Directeur
- Personnel autorisé possédant la permission correspondante

## Préconditions

- utilisateur authentifié ;
- tenant actif ;
- permissions validées ;
- établissement existant.

## Déclencheur

Le directeur clique sur :

```
Configuration académique

↓

Années scolaires

↓

Nouvelle année scolaire
```

## Résultat attendu

Une nouvelle année scolaire est créée.

Elle est enregistrée dans le tenant courant.

Les périodes académiques peuvent ensuite être générées automatiquement ou manuellement selon la configuration choisie.

---

# Partie 3 — Architecture technique & Implémentation Laravel

---

# Architecture MVC

Le module Academic Year respecte strictement l'architecture Laravel MVC adoptée par EduSen.

La logique métier ne doit jamais être implémentée dans les Controllers.

Les Controllers sont uniquement responsables de :

- recevoir les requêtes HTTP ;
- déléguer le traitement au Service approprié ;
- retourner une API Resource.

Ils ne doivent jamais :

- créer une transaction ;
- envoyer un e-mail ;
- exécuter une logique métier ;
- manipuler directement plusieurs modèles.

---

# Structure des fichiers

```
app/

├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           └── Academic/
│   │               └── AcademicYearController.php
│   │
│   ├── Requests/
│   │       └── AcademicYear/
│   │
│   └── Resources/
│           └── AcademicYearResource.php
│
├── Models/
│       └── AcademicYear.php
│
├── Services/
│       └── AcademicYearService.php
│
├── Events/
│
├── Listeners/
│
├── Jobs/
│
└── Policies/
        └── AcademicYearPolicy.php
```

---

# Responsabilités

## Controller

Responsable de :

- recevoir les requêtes ;
- appeler le Service ;
- retourner une Resource.

---

## Form Requests

Responsables de :

- validation syntaxique ;
- validation des formats ;
- messages personnalisés.

Aucune règle métier complexe ne doit être placée ici.

---

## Service

Le Service constitue le cœur du module.

Il est le seul composant autorisé à :

- créer une année scolaire ;
- modifier une année scolaire ;
- définir l'année active ;
- clôturer une année ;
- rouvrir une année ;
- archiver ;
- restaurer.

Toutes les opérations critiques utilisent une transaction.

---

## Model

Le modèle représente uniquement les données.

Aucune logique métier complexe ne doit être présente.

Les Accessors, Mutators et Scopes restent autorisés.

---

## API Resource

Toutes les réponses HTTP doivent utiliser une Resource.

Aucun Model ne doit être retourné directement.

---

# Routes

Toutes les routes du domaine Academic sont déclarées dans :

```
routes/api/v1/academic.php
```

Puis chargées depuis :

```
routes/api.php
```

Exemple :

```
api/v1/academic-years
```

---

# Endpoints

## GET

Liste paginée.

---

## GET /{id}

Afficher une année.

---

## POST

Créer.

---

## PUT

Modifier.

---

## PATCH /activate

Définir comme année active.

---

## PATCH /close

Clôturer.

---

## PATCH /reopen

Réouvrir.

---

## DELETE

Archivage (SoftDelete).

---

## PATCH /restore

Restauration.

---

# Middleware

Toutes les routes utilisent :

- auth:sanctum
- tenant
- permission

Le filtrage par tenant est obligatoire.

---

# Policies

Les autorisations sont centralisées dans :

AcademicYearPolicy

Actions :

- view
- create
- update
- activate
- close
- reopen
- archive
- restore

Le Controller ne doit jamais effectuer de vérification métier.

---

# Transactions

Les méthodes suivantes ouvrent obligatoirement une transaction.

- create()
- update()
- activate()
- close()
- reopen()
- archive()
- restore()

En cas d'exception :

Rollback immédiat.

---

# Evènements

Chaque opération importante déclenche un Event.

Exemple :

AcademicYearCreated

AcademicYearUpdated

AcademicYearActivated

AcademicYearClosed

AcademicYearReopened

AcademicYearArchived

AcademicYearRestored

---

# Listeners

Les traitements secondaires sont délégués.

Exemple.

AcademicYearCreated

↓

GenerateAcademicPeriods

↓

WriteAuditLog

↓

RefreshAcademicConfiguration

---

AcademicYearActivated

↓

DeactivatePreviousAcademicYear

↓

WriteAuditLog

---

AcademicYearClosed

↓

WriteAuditLog

↓

RefreshDashboardStatistics

---

# Notifications

Aucune notification n'est envoyée aux élèves.

Les notifications concernent uniquement :

- Directeur
- Personnel administratif concerné

Toutes les notifications sont asynchrones.

---

# Gestion des erreurs

Le Service doit lever des exceptions métier explicites.

Exemples :

AcademicYearAlreadyActiveException

AcademicYearAlreadyClosedException

AcademicYearOverlapException

AcademicYearHasDependenciesException

AcademicYearCannotBeDeletedException

---

# API Response

Toutes les réponses suivent le même format.

Succès

```
{
    "success": true,
    "message": "...",
    "data": {}
}
```

Erreur

```
{
    "success": false,
    "message": "...",
    "errors": {}
}
```

---

# Performance

Les index suivants sont recommandés.

tenant_id

status

start_date

end_date

deleted_at

---

# SoftDelete

Le module utilise exclusivement SoftDeletes.

Aucun appel à forceDelete() n'est autorisé.

Une année archivée peut être restaurée si les règles métier le permettent.

---

# Sécurité

Toutes les requêtes sont filtrées par :

tenant_id

Aucune année scolaire d'un autre établissement ne doit être accessible.

---

# Partie 4 — Implémentation métier détaillée

---

# UC-AY-001

Créer une année scolaire

## Workflow

```
Requête HTTP

↓

Authentification

↓

Middleware Tenant

↓

Policy

↓

FormRequest

↓

AcademicYearService

↓

Transaction

↓

Création

↓

Event

↓

Listeners

↓

Commit

↓

API Resource

↓

Réponse
```

---

# Validations métier

Avant la création :

Le système vérifie :

- absence de chevauchement de dates ;
- nom unique dans le tenant ;
- cohérence des dates.

Si une validation échoue :

Rollback.

Retour HTTP 422.

---

# Génération automatique

Selon les paramètres :

Le système peut créer automatiquement :

- les périodes ;
- les paramètres académiques.

Cette génération est transactionnelle.

---

# Activation

Une nouvelle année peut être activée immédiatement.

Le Service :

désactive automatiquement l'année précédente.

Cette opération est atomique.

---

# Clôture

Avant la clôture :

Le système vérifie notamment :

- présence d'une année suivante si nécessaire ;
- cohérence des périodes ;
- absence de traitement bloquant défini par les règles métier.

Une année clôturée devient en lecture seule.

---

# Réouverture

Une réouverture reste exceptionnelle.

Le système vérifie :

- permissions ;
- cohérence des données ;
- impact sur les autres modules.

---

# Archivage

Une année est archivée.

Jamais supprimée.

Le Service refuse l'archivage si cela compromet l'intégrité fonctionnelle.

---

# Restauration

Une année restaurée retrouve son historique.

Elle n'est jamais automatiquement réactivée.

Le directeur décide ensuite de son activation.

---

# Intégration avec les autres modules

Academic Year est référencé par :

AcademicPeriod

↓

SchoolCalendar

↓

Level

↓

Classroom

↓

Enrollment

↓

TeachingAssignment

↓

Attendance

↓

Evaluation

↓

Grade

↓

Bulletin

Aucun de ces modules ne doit exister sans année scolaire.

---

# Tests Unit

Le module doit couvrir :

- création ;
- modification ;
- activation ;
- clôture ;
- réouverture ;
- archivage ;
- restauration.

Tous les cas limites doivent être testés.

---

# Tests Feature

Scénarios :

Créer une année.

Créer une année avec chevauchement.

Créer deux années actives.

Clôturer une année.

Réouvrir une année.

Archiver.

Restaurer.

Tester les permissions.

Tester l'isolation des tenants.

---

# Evolutions futures

Le module est conçu pour supporter ultérieurement :

- duplication d'une année scolaire ;
- archivage automatique ;
- clôture automatique à une date donnée ;
- statistiques annuelles ;
- export complet d'une année (surtout cette partie, permettre a la co
  loture de l annee, le directeur pourra exporter toutes les donnees de l annee dans un fichier excel ou un autre avec une organisation exceptionnelle);
- import d'une structure académique ;
- assistant de préparation d'une nouvelle rentrée scolaire.

---

# Conclusion

Academic Year constitue la racine fonctionnelle de l'ensemble du domaine académique.

Toutes les évolutions futures devront préserver les principes définis dans cette spécification :

- une seule année active ;
- isolation stricte par tenant ;
- aucune suppression physique ;
- logique métier centralisée dans les Services ;
- architecture Laravel MVC ;
- événements pour les traitements secondaires ;
- transactions pour toutes les opérations critiques.

Toute évolution du module devra respecter ces principes afin de garantir la cohérence de l'ensemble du système EduSen.
