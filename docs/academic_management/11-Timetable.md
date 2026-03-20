# Academic Management

# 11 - TimeTable

---

# Objectif

Le module **TimeTable** représente un emploi du temps appartenant à une classe physique (`Classroom`) pour une année scolaire donnée.

Il constitue un conteneur regroupant l'ensemble des créneaux horaires (ScheduleEntry) utilisés par cette classe.

Le TimeTable ne contient aucun horaire directement.

Tous les créneaux sont définis dans les ScheduleEntries.

---

# Position dans le domaine

AcademicYear

↓

TimeTable

↓

ScheduleEntry

↓

LessonSession

↓

AttendanceRecord

↓

Evaluation

---

# Philosophie

Le TimeTable est une configuration.

Il permet d'organiser plusieurs plannings indépendants.

Exemples :

- Emploi du temps général
- Emploi du temps des examens
- Emploi du temps des cours de rattrapage
- Emploi du temps du Ramadan
- Emploi du temps provisoire

Chaque TimeTable possède plusieurs ScheduleEntries.

---

# Responsabilités

Le module permet :

- créer un emploi du temps ;
- modifier son nom ;
- l'activer ;
- le désactiver ;
- le dupliquer ;
- l'archiver ;
- le restaurer.

Il ne gère jamais les horaires.

---

# Structure métier

Un TimeTable possède :

- une classe (`classroom_id`, ForeignKey) ;
- une année scolaire (`academic_year_id`, ForeignKey) ;
- un nom ;
- une description ;
- un statut.

---

# Statuts

Draft

Le planning est en préparation.

---

Active

Le planning est utilisé par l'établissement.

---

Archived

Le planning est conservé uniquement pour l'historique.

---

# Règles métier

## RTT-001

Un TimeTable appartient obligatoirement à une classe physique (`Classroom`).

---

## RTT-002

Un TimeTable appartient obligatoirement à une année scolaire (`AcademicYear`).

---

## RTT-003

Une classe peut posséder plusieurs emplois du temps (ex: principal, Ramadan, provisoire).

---

## RTT-004

Un seul TimeTable peut être à l'état `Active` à un instant donné pour une même classe physique.

---

## RTT-005

Toutes les suppressions utilisent SoftDeletes.

---

# Duplication

Le directeur peut dupliquer un emploi du temps.

Exemple

Emploi du temps 2026-2027

↓

Dupliquer

↓

Emploi du temps 2027-2028

Tous les ScheduleEntries sont copiés.

Le directeur effectue ensuite les ajustements nécessaires.

---

# Relations

TimeTable

hasMany

ScheduleEntry

---

AcademicYear

hasMany

TimeTable

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

TimeTableService

Responsabilités :

- création ;
- mise à jour ;
- activation ;
- désactivation ;
- duplication ;
- archivage ;
- restauration.

---

# Routes API

GET /timetables

GET /timetables/{id}

POST /timetables

PUT /timetables/{id}

DELETE /timetables/{id}

PATCH /timetables/{id}/restore

PATCH /timetables/{id}/activate

POST /timetables/{id}/duplicate

---

# Cas limites

Activation d'un nouvel emploi du temps.

↓

L'ancien devient automatiquement inactif.

---

Suppression d'un emploi du temps contenant des ScheduleEntries.

↓

SoftDelete uniquement.

---

Duplication.

↓

Toutes les ScheduleEntries sont copiées.

Aucune LessonSession n'est copiée.

---

# Evolutions futures

Le module est conçu pour supporter :

- plusieurs types de planning ;
- gestion des vacances scolaires ;
- plannings saisonniers ;
- plannings exceptionnels ;
- import/export Excel.

---

# Conclusion

TimeTable représente le conteneur principal des plannings pédagogiques.

Les créneaux sont définis dans les ScheduleEntries qui lui sont rattachés.

Le module garantit une séparation claire entre la configuration d'un planning et les séances réellement exécutées.
