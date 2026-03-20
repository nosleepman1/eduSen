# Academic Management

# 13 - LessonSession

---

# Objectif

Le module **LessonSession** représente une séance de cours réelle générée à partir d'un ScheduleEntry.

Contrairement au ScheduleEntry qui décrit un cours planifié et récurrent, une LessonSession représente une occurrence réelle d'un cours à une date précise.

Toutes les opérations pédagogiques de l'établissement reposent sur cette entité.

---

# Position dans le domaine

AcademicYear

↓

TimeTable

↓

ScheduleEntry

↓

LessonSession

├── AttendanceRecord

├── LessonContent

├── Evaluation

└── Attachments

↓

Grade

↓

Bulletin

---

# Philosophie

Le ScheduleEntry représente un cours prévu.

La LessonSession représente le cours qui devait réellement avoir lieu.

Une LessonSession est générée automatiquement pour chaque occurrence du planning.

Exemple :

ScheduleEntry

↓

Tous les Lundis

08h00 - 10h00

↓

Le système génère automatiquement :

08 Septembre

↓

15 Septembre

↓

22 Septembre

↓

29 Septembre

Chaque date devient une LessonSession indépendante.

---

# Responsabilités

Le module permet :

- suivre l'exécution réelle d'un cours ;
- démarrer une séance ;
- terminer une séance ;
- annuler une séance ;
- reporter une séance ;
- valider administrativement une séance ;
- historiser les activités pédagogiques.

---

# Structure métier

Une LessonSession possède :

- un ScheduleEntry ;
- une date de cours ;
- une heure prévue de début ;
- une heure prévue de fin ;
- une heure réelle de début ;
- une heure réelle de fin ;
- un statut ;
- un motif d'annulation (nullable) ;
- un commentaire administratif (nullable).

---

# Cycle de vie

Création automatique

↓

Scheduled

↓

Heure de début atteinte

↓

PendingStart

↓

Le professeur démarre

↓

InProgress

↓

Le professeur termine

↓

Completed

↓

Archived

---

Si le professeur ne démarre jamais :

Scheduled

↓

PendingStart

↓

Fin théorique atteinte

↓

PendingValidation

↓

Le responsable pédagogique décide :

↓

Completed

ou

↓

Cancelled

ou

↓

Postponed

---

# Statuts

## Scheduled

La séance est planifiée.

---

## PendingStart

L'heure de début est atteinte.

Le professeur n'a pas encore démarré la séance.

---

## InProgress

Le cours est actuellement en cours.

---

## PendingValidation

Le cours n'a jamais été démarré.

Une intervention administrative est nécessaire.

---

## Completed

Le cours a été effectué.

---

## Cancelled

Le cours n'a pas eu lieu.

---

## Postponed

Le cours sera reprogrammé.

---

## Archived

Historique.

Aucune modification n'est autorisée.

---

# Contraintes métier

## RLS-001

Une LessonSession appartient obligatoirement à un ScheduleEntry.

---

## RLS-002

Une LessonSession possède une date unique.

---

## RLS-003

Une seule LessonSession peut exister pour un ScheduleEntry à une même date.

---

## RLS-004

Les heures réelles sont facultatives tant que la séance n'est pas démarrée.

---

## RLS-005

Une séance terminée ne peut plus revenir à l'état InProgress.

---

## RLS-006

Une séance archivée devient totalement immuable.

---

## RLS-007

Toutes les suppressions utilisent exclusivement SoftDeletes.

---

# Démarrage d'une séance

Le professeur clique sur :

"Démarrer le cours"

Le système :

- renseigne actual_start ;
- passe le statut à InProgress ;
- enregistre l'utilisateur ayant effectué l'action ;
- historise l'événement.

---

# Fin d'une séance

Le professeur clique sur :

"Terminer le cours"

Le système :

- renseigne actual_end ;
- passe le statut à Completed ;
- verrouille la séance.

---

# Validation administrative

Si la séance atteint son heure de fin sans avoir été démarrée :

Le système passe automatiquement le statut :

PendingValidation.

Le responsable pédagogique doit alors choisir :

✓ Cours réalisé

✗ Cours annulé

↺ Cours reporté

---

# Annulation

Si la séance est annulée :

Le motif devient obligatoire.

Exemples :

- Enseignant absent
- Élèves absents
- Grève
- Jour férié exceptionnel
- Intempéries
- Panne
- Formation
- Autre

Un commentaire complémentaire peut être ajouté.

---

# Report

Une séance reportée conserve son historique.

Une nouvelle LessonSession pourra être créée pour la séance de remplacement.

Aucune donnée n'est perdue.

---

# Vérifications automatiques

À la clôture d'une séance, le système vérifie :

- présence enregistrée ;
- contenu pédagogique renseigné ;
- évaluation créée (optionnelle).

Les éléments manquants apparaissent dans le tableau de bord du directeur.

---

# Relations

LessonSession appartient à :

- ScheduleEntry

LessonSession possède :

- plusieurs AttendanceRecords ;
- plusieurs LessonContents ;
- plusieurs Evaluations ;
- plusieurs Attachments.

---

# Workflow

Génération automatique

↓

Scheduled

↓

Heure de début

↓

PendingStart

↓

Le professeur démarre

↓

InProgress

↓

Le professeur termine

↓

Completed

↓

Archivage

---

Ou

↓

PendingValidation

↓

Décision administrative

↓

Completed

↓

Cancelled

↓

Postponed

---

# Services

LessonSessionService

Responsabilités :

- génération des séances ;
- démarrage ;
- clôture ;
- validation administrative ;
- annulation ;
- report ;
- archivage.

---

# Génération automatique

Les LessonSessions peuvent être générées :

- lors de la création d'un ScheduleEntry ;
- automatiquement via une commande Artisan planifiée ;
- par un Job exécuté dans une Queue Redis ;
- à la demande par un administrateur.

Le système ne doit jamais générer de séances pendant :

- les vacances scolaires ;
- les jours fériés configurés ;
- les périodes de fermeture de l'établissement ;
- les dates postérieures à la date de fin de cours personnalisée de la classe (`Classroom.end_date_override`, ex: fin juin pour les classes intermédiaires, fin juillet pour les classes d'examen).

Ces jours sont extraits dynamiquement du calendrier de l'année scolaire en cours (`SchoolCalendar`).

## Propagation des changements de calendrier (Cascading)

Lorsqu'un agent de l'administration modifie le calendrier (par exemple, en déplaçant ou en confirmant les dates exactes d'une fête variable comme la Tabaski ou la Korité qui étaient préalablement définies sous forme d'intervalles estimatifs) :
- Les séances réelles de cours futures qui tombent sur le nouvel intervalle de la fête sont automatiquement annulées (statut `CANCELLED` avec le motif "Ajustement Fête Religieuse Variable").
- Si une date précédemment fermée redevient ouverte (libérée par le déplacement de l'intervalle) : le système notifie l'administration et liste les cours potentiels qui peuvent être replanifiés sur ce créneau.

---

# Routes API

GET /lesson-sessions

GET /lesson-sessions/{id}

GET /lesson-sessions/today

POST /lesson-sessions/{id}/start

POST /lesson-sessions/{id}/finish

POST /lesson-sessions/{id}/cancel

POST /lesson-sessions/{id}/postpone

POST /lesson-sessions/{id}/validate

PATCH /lesson-sessions/{id}/restore

---

# Cas limites

Le professeur démarre avec 20 minutes de retard.

↓

La séance démarre normalement.

Le retard est conservé dans l'historique.

---

Le professeur oublie de terminer.

↓

Le directeur peut clôturer administrativement la séance.

---

Le professeur oublie complètement de démarrer.

↓

La séance passe automatiquement en PendingValidation.

---

Le cours est annulé.

↓

Le motif est obligatoire.

---

Le cours est reporté.

↓

Une nouvelle LessonSession est créée.

L'ancienne est conservée avec son statut Postponed.

---

# Indicateurs

Le module permettra d'alimenter les tableaux de bord :

- nombre de cours réalisés ;
- nombre de cours annulés ;
- taux d'exécution des enseignements ;
- retards des enseignants ;
- cours en attente de validation ;
- taux de complétion des contenus pédagogiques.

---

# Évolutions futures

Le module est conçu pour accueillir :

- géolocalisation du professeur au démarrage ;
- signature numérique de la séance ;
- scan QR pour valider la présence de l'enseignant ;
- démarrage automatique via badge NFC ;
- visioconférence intégrée pour les cours hybrides ;
- intelligence artificielle pour détecter les anomalies pédagogiques.

---

# Conclusion

LessonSession constitue le cœur opérationnel d'EduSen.

Il représente chaque cours réellement planifié et exécuté au sein de l'établissement.

Toutes les fonctionnalités pédagogiques — présence, contenu des cours, devoirs, évaluations, notes et indicateurs de performance — reposent sur cette entité, garantissant une traçabilité complète et une vision fidèle de l'activité pédagogique de l'école.
