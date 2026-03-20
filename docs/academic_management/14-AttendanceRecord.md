# Academic Management

# 14 - AttendanceRecord

---

# Objectif

Le module **AttendanceRecord** représente la présence ou l'absence d'un élève lors d'une séance de cours réelle (LessonSession).

Il constitue la trace officielle de la participation de chaque élève à chaque cours dispensé au sein de l'établissement.

Toutes les statistiques de présence, les alertes d'absentéisme et les justificatifs reposent sur cette entité.

---

# Position dans le domaine

```
AcademicYear

↓

TimeTable

↓

ScheduleEntry

↓

LessonSession

↓

AttendanceRecord

├── Enrollment (élève concerné)
├── Justification (optionnelle)
└── Attachment (pièce jointe, optionnelle)
```

---

# Philosophie

Un AttendanceRecord est lié à une **LessonSession** et à un **Enrollment**.

Il répond à la question :

> Cet élève était-il présent lors de ce cours précis ?

Un AttendanceRecord est unique par couple :

```
LessonSession + Enrollment
```

Il est généralement créé automatiquement pour tous les élèves inscrits dans la classe lorsque la séance commence.

L'enseignant effectue ensuite l'appel en marquant les absents.

---

# Responsabilités

Le module permet :

- créer les enregistrements de présence ;
- marquer un élève comme présent ;
- marquer un élève comme absent ;
- marquer un élève en retard ;
- marquer un élève comme dispensé ;
- justifier une absence ;
- joindre un document justificatif ;
- modifier un enregistrement tant que la séance n'est pas archivée ;
- consulter le taux de présence d'un élève.

---

# Structure métier

Un AttendanceRecord possède :

- une séance de cours (LessonSession) ;
- un enrollment (l'inscription de l'élève dans la classe) ;
- un statut de présence ;
- une heure d'arrivée réelle (optionnelle, si retard) ;
- un motif d'absence (optionnel) ;
- une justification (optionnelle) ;
- un statut de justification ;
- un commentaire (optionnel).

---

# Statuts de présence

## Present

L'élève était présent lors de la séance.

---

## Absent

L'élève était absent lors de la séance.

Aucune justification n'a encore été fournie.

---

## Late

L'élève est arrivé en retard.

L'heure d'arrivée réelle est enregistrée.

---

## Excused

L'élève était absent mais sa présence est justifiée.

Un document peut être joint.

---

## Dispensed

L'élève était officiellement dispensé de ce cours.

Exemple :

- Dispense médicale d'EPS.
- Dispense pour activité officielle.

---

# Statuts de justification

## Pending

La justification a été soumise.

Elle est en attente de validation par un responsable.

---

## Approved

La justification a été acceptée.

L'absence est officiellement excusée.

---

## Rejected

La justification a été refusée.

L'absence reste comptabilisée comme non justifiée.

---

# Règles métier

## RAR-001

Un AttendanceRecord appartient obligatoirement à une LessonSession.

---

## RAR-002

Un AttendanceRecord appartient obligatoirement à un Enrollment.

---

## RAR-003

Il ne peut exister qu'un seul AttendanceRecord par couple LessonSession + Enrollment.

---

## RAR-004

Un AttendanceRecord ne peut être créé que si la LessonSession est dans l'un des états suivants :

- InProgress
- Completed
- PendingValidation

---

## RAR-005

Un AttendanceRecord ne peut plus être modifié lorsque la LessonSession est archivée.

---

## RAR-006

L'heure d'arrivée réelle n'est renseignée que si le statut est Late.

---

## RAR-007

Si le statut est Absent, le motif d'absence est recommandé mais reste facultatif à la saisie.

---

## RAR-008

Si le statut est Excused, la justification est obligatoire.

---

## RAR-009

Toutes les suppressions utilisent SoftDeletes.

---

## RAR-010

L'Enrollment doit appartenir à la même Classroom que la LessonSession.

---

# Génération automatique

Lors du démarrage d'une LessonSession :

Le système peut générer automatiquement un AttendanceRecord pour chaque élève inscrit dans la classe.

Statut par défaut : **Absent**

L'enseignant modifie ensuite les élèves présents.

Cette approche simplifie l'appel en minimisant les actions de saisie.

---

# Appel

Le processus standard est :

Démarrage de la séance

↓

Génération automatique des AttendanceRecords (Absent par défaut)

↓

L'enseignant coche les présents

↓

Le système met à jour les statuts

↓

Validation de l'appel

---

# Justification d'absence

Un parent, un élève ou un personnel administratif peut soumettre une justification.

Le responsable pédagogique valide ou rejette.

Exemple :

```
Élève : Moussa Diallo

Séance : Mathématiques — Lundi 08/09/2026

Statut : Absent

Justification : Certificat médical

↓

Statut mis à jour : Excused
```

---

# Retard

Un retard est enregistré avec l'heure d'arrivée réelle.

Exemple :

```
Heure prévue de début : 08h00

Heure d'arrivée réelle : 08h25

Durée du retard : 25 minutes
```

Les retards peuvent alimenter les tableaux de bord et déclencher des alertes.

---

# Relations

AttendanceRecord appartient à :

- LessonSession
- Enrollment

AttendanceRecord peut posséder :

- plusieurs Attachments (documents justificatifs)

---

# Workflow

Démarrage de la LessonSession

↓

Génération automatique des AttendanceRecords

↓

Appel par l'enseignant

↓

Mise à jour des statuts

↓

Validation

↓

Clôture de la LessonSession

↓

Verrouillage des AttendanceRecords

---

# Services

```
AttendanceRecordService
```

Responsabilités :

- génération automatique des enregistrements ;
- mise à jour du statut de présence ;
- enregistrement des retards ;
- gestion des justifications ;
- calcul du taux de présence ;
- archivage.

---

# Routes API

```
GET    /attendance-records

GET    /attendance-records/{id}

POST   /attendance-records

PUT    /attendance-records/{id}

PATCH  /attendance-records/{id}/justify

PATCH  /attendance-records/{id}/approve-justification

PATCH  /attendance-records/{id}/reject-justification

DELETE /attendance-records/{id}

PATCH  /attendance-records/{id}/restore

GET    /lesson-sessions/{id}/attendance

POST   /lesson-sessions/{id}/attendance/bulk

GET    /enrollments/{id}/attendance-summary
```

---

# Événements

Le module déclenche notamment :

- AttendanceRecordCreated
- AttendanceRecordUpdated
- AbsenceJustificationSubmitted
- AbsenceJustificationApproved
- AbsenceJustificationRejected

---

# Indicateurs

Le module alimente :

- taux de présence par élève ;
- taux de présence par classe ;
- taux de présence par matière ;
- nombre d'absences non justifiées ;
- alertes d'absentéisme (seuil configurable) ;
- statistiques par période académique.

---

# Cas limites

Créer deux AttendanceRecords pour le même élève dans la même séance.

↓

Refus.

---

Modifier un AttendanceRecord d'une LessonSession archivée.

↓

Refus.

---

Soumettre une justification pour un élève Present.

↓

Refus.

---

Enregistrer un retard sans heure d'arrivée réelle.

↓

Refus.

---

Tenter de créer un AttendanceRecord pour un élève non inscrit dans la classe.

↓

Refus.

---

# Évolutions futures

Le module est conçu pour accueillir :

- notifications automatiques aux parents en cas d'absence ;
- portail parents pour soumettre les justifications en ligne ;
- portail élèves pour consulter leur historique de présence ;
- scan QR code pour l'appel numérique ;
- reconnaissance biométrique ;
- intégration avec les systèmes de badges NFC ;
- alertes automatiques au-delà d'un seuil d'absentéisme configurable ;
- rapports d'absentéisme pour les autorités scolaires.

---

# Conclusion

AttendanceRecord constitue la trace officielle de la participation des élèves à chaque cours.

Il repose entièrement sur la LessonSession, garantissant une cohérence parfaite entre le planning pédagogique et le suivi des présences.

Toutes les statistiques d'absentéisme, les alertes et les bulletins de présence s'appuient sur cette entité.
