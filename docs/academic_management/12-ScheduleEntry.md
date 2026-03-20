# Academic Management

# 12 - ScheduleEntry

---

# Objectif

Le module **ScheduleEntry** représente un créneau récurrent appartenant à un emploi du temps.

Une ScheduleEntry décrit précisément :

- quel enseignant intervient ;
- quelle matière est enseignée ;
- dans quelle classe ;
- quel jour ;
- à quelle heure ;
- pendant quelle durée.

Il constitue l'unité fondamentale de planification des enseignements.

Les séances de cours (LessonSession) seront générées automatiquement à partir des ScheduleEntries.

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

LessonContent

↓

Evaluation

---

# Philosophie

Une ScheduleEntry représente un cours prévu.

Elle ne représente pas un cours réellement effectué.

Exemple :

Tous les lundis

08h00

↓

10h00

↓

Mathématiques

↓

6ème A

↓

Professeur Ndiaye

Cette définition sera utilisée pour générer toutes les séances de l'année scolaire.

---

# Responsabilités

Le module permet :

- créer un créneau ;
- modifier un créneau ;
- déplacer un créneau ;
- changer d'horaire ;
- changer de salle ;
- désactiver un créneau ;
- archiver un créneau ;
- restaurer un créneau.

---

# Structure métier

Une ScheduleEntry possède :

- un emploi du temps ;
- une affectation pédagogique (TeachingAssignment) ;
- un jour de la semaine ;
- une heure de début ;
- une heure de fin ;
- une salle (optionnelle) ;
- une fréquence ;
- un statut.

---

# Fréquence

Par défaut

Weekly

Le créneau est répété chaque semaine.

Le système est conçu pour accueillir ultérieurement :

- EveryTwoWeeks
- Monthly
- Custom

---

# Durée

La durée n'est jamais enregistrée.

Elle est calculée automatiquement.

Exemple

08h00

↓

10h00

↓

2 heures

---

# Contraintes métier

## RSE-001

Une ScheduleEntry appartient obligatoirement à un TimeTable.

---

## RSE-002

Une ScheduleEntry appartient obligatoirement à un TeachingAssignment.

---

## RSE-003

L'heure de fin doit être supérieure à l'heure de début.

---

## RSE-004

Deux créneaux d'une même classe ne peuvent jamais se chevaucher.

---

## RSE-005

Un enseignant ne peut jamais être présent dans deux classes au même moment.

---

## RSE-006

Une salle ne peut jamais être réservée deux fois simultanément.

---

## RSE-007

Le jour de la semaine est obligatoire.

---

## RSE-008

Toutes les suppressions utilisent SoftDeletes.

---

# Vérifications automatiques

Avant chaque création :

Le système vérifie :

↓

Conflit enseignant

↓

Conflit classe

↓

Conflit salle

↓

Compatibilité avec l'année scolaire

↓

Compatibilité avec le TeachingAssignment

↓

Validation

---

# Compatibilité pédagogique

Le système vérifie automatiquement :

TeachingAssignment

↓

Curriculum

↓

Classroom

↓

AcademicYear

Toutes ces informations doivent être cohérentes.

Aucune incohérence n'est autorisée.

---

# Contrôle du volume horaire

Chaque Curriculum possède :

weekly_hours

Exemple

Mathématiques

6 heures

---

Le système calcule automatiquement :

Lundi

2h

Mercredi

2h

Vendredi

2h

=

6h

---

Si le planning ne respecte pas le volume prévu :

↓

Notification

↓

Avertissement

La création reste autorisée.

---

# Génération des LessonSessions

Une fois la ScheduleEntry validée :

Le système pourra générer automatiquement les séances de cours.

Exemple

ScheduleEntry

↓

Chaque lundi

↓

08h00

↓

Pendant 36 semaines

↓

36 LessonSessions

Chaque LessonSession correspond à une date réelle.

---

# Modification

Lorsqu'un créneau est modifié :

Le système ne modifie jamais les LessonSessions déjà réalisées.

Seules les séances futures sont impactées.

Cette règle garantit l'intégrité de l'historique.

---

# Relations

ScheduleEntry appartient à :

- TimeTable
- TeachingAssignment

ScheduleEntry possède :

- plusieurs LessonSessions

---

# Workflow

Sélection de l'emploi du temps

↓

Sélection du TeachingAssignment

↓

Choix du jour

↓

Choix de l'heure de début

↓

Choix de l'heure de fin

↓

Validation des conflits

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

ScheduleEntryService

Responsabilités :

- création ;
- modification ;
- suppression logique ;
- restauration ;
- détection des conflits ;
- calcul du volume horaire ;
- génération des LessonSessions ;
- régénération des séances futures.

---

# Routes API

GET /schedule-entries

GET /schedule-entries/{id}

POST /schedule-entries

PUT /schedule-entries/{id}

DELETE /schedule-entries/{id}

PATCH /schedule-entries/{id}/restore

GET /timetables/{id}/schedule-entries

POST /schedule-entries/{id}/generate-sessions

POST /schedule-entries/{id}/regenerate

---

# Cas limites

Deux professeurs dans une même salle au même moment.

↓

Refus.

---

Deux cours pour une même classe.

↓

Refus.

---

Créneau hors année scolaire.

↓

Refus.

---

Suppression d'un créneau possédant déjà des LessonSessions.

↓

Le créneau est archivé.

Les LessonSessions passées restent conservées.

Les séances futures peuvent être annulées automatiquement selon les règles métier.

---

Modification de l'heure.

↓

Les LessonSessions futures sont recalculées.

Les anciennes séances restent inchangées.

---

# Intégration avec le Calendrier Scolaire

Lors de la génération automatique ou de l'activation d'un emploi du temps (`Timetable`), les séances réelles (`LessonSession`) créées à partir des `ScheduleEntries`  systématiqueexcluentment les dates définies comme congés, jours fériés (fixes ou variables) et vacances scolaires dans le calendrier (`SchoolCalendar`) de l'établissement.

---

# Evolutions futures

Le module est prévu pour supporter :

- semaines paires / impaires ;
- jours fériés ;
- vacances scolaires ;
- cours exceptionnels ;
- remplacement automatique ;
- IA de génération de planning ;
- optimisation automatique des emplois du temps.

---

# Conclusion

ScheduleEntry représente l'unité de planification pédagogique.

Chaque créneau définit un enseignement récurrent pour une classe.

Les LessonSessions générées à partir de ces créneaux constitueront les véritables séances de cours sur lesquelles reposeront les présences, les contenus pédagogiques, les devoirs, les évaluations et les notes.
