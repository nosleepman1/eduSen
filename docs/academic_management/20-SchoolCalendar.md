# Academic Management

# 20 - School Calendar

---

# Version

1.0.0

---

# Introduction

Le module **School Calendar** (Calendrier Scolaire) gère l'ensemble des périodes d'inactivité et des congés d'un établissement scolaire dans EduSen.

Il permet d'enregistrer les jours fériés, les fêtes religieuses et les vacances scolaires pour une année académique donnée. Ce calendrier sert de référence temporelle incontournable pour la planification automatique et l'exécution réelle des cours (`LessonSession`).

---

# Position dans le domaine

```
AcademicYear
     │
     ▼
SchoolCalendar
     │
     ├── HolidayEvent (Fixed / Variable Holiday / School Break)
     │
     ▼ (Filtre lors de la génération)
LessonSession & ScheduleEntry
```

---

# Responsabilités

Le module permet :
- Créer le calendrier scolaire associé à l'année académique.
- Configurer les jours fériés fixes et les vacances scolaires.
- Prévoir et configurer les fêtes variables (Korité, Tabaski, Maouloud, etc.) sous forme d'intervalles estimatifs.
- Permettre aux agents de l'administration de modifier à tout moment le calendrier de fêtes généré.
- Propager automatiquement les modifications du calendrier sur les séances de cours futures.

---

# Structure métier

## SchoolCalendar
- `id` : `UUID`
- `school_id` : `UUID` (Clé tenant)
- `academic_year_id` : `UUID` (Lien année)
- `name` : `VARCHAR` (ex: "Calendrier Officiel 2026-2027")

## HolidayEvent
- `id` : `UUID`
- `school_calendar_id` : `UUID`
- `name` : `VARCHAR` (ex: "Fête de la Tabaski", "Vacances du 1er Trimestre")
- `type` : `VARCHAR` (Enum: `FIXED_HOLIDAY`, `VARIABLE_HOLIDAY`, `SCHOOL_BREAK`)
- `start_date` : `DATE`
- `end_date` : `DATE`
- `is_estimated` : `BOOLEAN` (Vrai si la date est estimative et susceptible de changer, Faux si elle est officiellement confirmée)

---

# Règles métier

## RPH-001
Un calendrier scolaire appartient obligatoirement à une seule année académique active.

## RPH-002
Deux congés (`HolidayEvent`) ou vacances scolaires ne peuvent pas se chevaucher ou avoir des dates incohérentes (`start_date` > `end_date`).

## RPH-003
Les agents de l'administration possédant la permission `calendar.manage` peuvent librement ajouter, modifier ou supprimer des événements et des fêtes du calendrier.

## RPH-004
Toute modification d'une date de fête ou d'une période de vacances scolaires doit déclencher un recalcul asynchrone des cours (`LessonSession`) à venir :
- Si un nouveau jour férié ou de vacances est introduit : les séances de cours existantes tombant sur ce jour passent automatiquement à l'état `CANCELLED` (motif: "Calendrier Scolaire mis à jour").
- Si une fête est déplacée (changement d'intervalle) : les anciennes séances annulées pour cette date doivent être signalées à l'administration pour réactivation, et les séances tombant sur le nouvel intervalle sont annulées.

## RPH-005 (Gestion des fêtes variables)
Les fêtes dépendant du calendrier lunaire (Korité, Tabaski, etc.) sont enregistrées initialement avec le drapeau `is_estimated = true`. Le système autorise la définition d'un intervalle élargi de précaution (ex: 3 jours d'inactivité prévus). À l'approche de l'événement et lors de la confirmation officielle, l'administrateur modifie l'événement pour renseigner l'intervalle exact et passe `is_estimated` à `false`.

---

# Services

```
SchoolCalendarService
```
Responsabilités :
- Initialiser le calendrier lors du Setup Wizard ou lors de la création d'une année.
- Gérer le CRUD des `HolidayEvent`.
- Valider la cohérence des dates.
- Lancer le Job asynchrone `UpdateLessonSessionsOnCalendarChangeJob` lors de toute modification.

---

# Routes API

```
GET    /api/v1/academic/school-calendars
GET    /api/v1/academic/school-calendars/{id}
POST   /api/v1/academic/school-calendars/{id}/holidays
PUT    /api/v1/academic/school-calendars/holidays/{holidayId}
DELETE /api/v1/academic/school-calendars/holidays/{holidayId}
```

---

# Conclusion

Le module `SchoolCalendar` garantit que le système pédagogique d'EduSen est synchronisé avec le calendrier national et religieux, évitant ainsi la planification de cours fictifs et offrant aux administrateurs une flexibilité totale pour gérer les imprévus calendaires.
