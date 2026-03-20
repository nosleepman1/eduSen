# Academic Management

# 16 - Enrollment

---

# Version

1.0.0

---

# Objectif

Le module **Enrollment** représente l'inscription officielle d'un élève dans une classe pour une année scolaire donnée.

Il constitue le **pivot académique** qui relie un élève à une classe, à une année scolaire et à l'ensemble des activités pédagogiques qui en découlent.

Sans Enrollment actif, un élève ne peut :

- être marqué présent ou absent ;
- recevoir des évaluations ;
- obtenir des notes ;
- se voir générer un bulletin.

---

# Position dans le domaine

```
Tenant

↓

AcademicYear

↓

Level

↓

Classroom

↓

Enrollment (pivot)

├── AttendanceRecord
├── Grade
└── Bulletin
```

---

# Philosophie

L'Enrollment répond à la question :

> Quel élève est officiellement inscrit dans quelle classe pour quelle année scolaire ?

Il ne définit pas les matières ni les enseignants.

Ces informations sont portées par le Curriculum et le TeachingAssignment.

L'Enrollment est unique pour un élève dans une année scolaire donnée.

Un élève ne peut pas être inscrit dans deux classes simultanément pour la même année.

---

# Responsabilités

Le module permet :

- inscrire un élève dans une classe ;
- modifier une inscription ;
- transférer un élève vers une autre classe ;
- clôturer une inscription ;
- archiver une inscription ;
- restaurer une inscription ;
- consulter l'historique des inscriptions d'un élève.

---

# Structure métier

Un Enrollment possède :

- une année scolaire (AcademicYear) ;
- une classe (Classroom) ;
- un élève (Student / User) ;
- une date d'inscription ;
- une date de fin (optionnelle) ;
- un numéro d'inscription ;
- un statut ;
- un motif d'archivage (optionnel).

---

# Etats

## Active

L'élève est inscrit et actif dans la classe.

Les présences, évaluations et notes lui sont rattachées.

---

## Transferred

L'élève a été transféré vers une autre classe.

L'inscription d'origine est clôturée.

Une nouvelle inscription est créée dans la classe de destination.

L'historique des présences et notes reste rattaché à l'inscription d'origine.

---

## Withdrawn

L'élève a quitté l'établissement.

L'inscription est clôturée.

Les données restent consultables.

---

## Archived

L'inscription est masquée des interfaces courantes.

Les données restent conservées grâce au SoftDelete.

---

# Règles métier

## RE-001

Un Enrollment appartient obligatoirement à une AcademicYear.

---

## RE-002

Un Enrollment appartient obligatoirement à une Classroom.

---

## RE-003

Un Enrollment appartient obligatoirement à un élève (Student).

---

## RE-004

Un élève ne peut être inscrit que dans une seule classe active par année scolaire.

---

## RE-005

La Classroom ne doit pas dépasser sa capacité maximale.

---

## RE-006

La Classroom doit être Active pour accepter une inscription.

---

## RE-007

L'AcademicYear doit être Active pour accepter une nouvelle inscription.

---

## RE-008

Un Enrollment ne peut jamais être supprimé définitivement.

Toutes les suppressions utilisent SoftDeletes.

---

## RE-009

Lors d'un transfert, l'ancienne inscription est clôturée et une nouvelle est créée dans la même transaction.

---

## RE-010

Toutes les opérations critiques sont exécutées dans une transaction.

---

## RE-011

Le nombre total d'inscriptions actives (`Enrollment` à l'état `Active`) pour l'année scolaire en cours ne doit jamais dépasser la limite `max_students` définie dans le plan d'abonnement actif de l'établissement (`SubscriptionPlan`). Cette vérification est effectuée avant de valider toute nouvelle inscription.

---

# Numéro d'inscription

Chaque Enrollment reçoit un numéro d'inscription unique généré automatiquement.

Ce numéro est utilisé pour :

- identifier officiellement l'élève dans l'établissement ;
- générer les bulletins ;
- produire les attestations scolaires.

Il est généré par le `RegistrationNumberService` déjà présent dans EduSen.

---

# Transfert de classe

Lorsqu'un élève change de classe en cours d'année :

```
Enrollment actif en 6A

↓

Transfert vers 6B

↓

Transaction :

1. Enrollment 6A → statut : Transferred
2. Nouvel Enrollment en 6B → statut : Active

↓

Les AttendanceRecords et Grades passés restent
rattachés à l'Enrollment 6A.
```

---

# Relations

Enrollment appartient à :

- AcademicYear
- Classroom
- Student (User)

Enrollment possède :

- plusieurs AttendanceRecords
- plusieurs Grades
- plusieurs Bulletins

---

# Workflow

Vérification de la capacité de la classe

↓

Vérification de l'unicité de l'inscription

↓

Validation

↓

Transaction

↓

Génération du numéro d'inscription

↓

Création de l'Enrollment

↓

Audit

↓

Notification

↓

Réponse API

---

# Services

```
EnrollmentService
```

Responsabilités :

- création ;
- modification ;
- transfert ;
- clôture ;
- archivage ;
- restauration ;
- vérification de capacité ;
- génération du numéro d'inscription.

---

# Routes API

```
GET    /enrollments

GET    /enrollments/{id}

POST   /enrollments

PUT    /enrollments/{id}

PATCH  /enrollments/{id}/transfer

PATCH  /enrollments/{id}/withdraw

PATCH  /enrollments/{id}/restore

DELETE /enrollments/{id}

GET    /classrooms/{id}/enrollments

GET    /students/{id}/enrollments
```

---

# Événements

- EnrollmentCreated
- EnrollmentTransferred
- EnrollmentWithdrawn
- EnrollmentArchived
- EnrollmentRestored

---

# Notifications

Lors d'une inscription :

- notification au directeur ;
- notification au professeur principal de la classe.

---

# Cas limites

Inscrire un élève dans une classe complète.

↓

Refus.

---

Inscrire un élève déjà inscrit dans une autre classe de la même année.

↓

Refus.

---

Inscrire un élève dans une classe d'une année scolaire Clôturée.

↓

Refus.

---

Transférer un élève vers la même classe.

↓

Refus.

---

Archiver un Enrollment possédant des Grades ou des AttendanceRecords.

↓

Archivage autorisé.

Les données historiques restent conservées.

---

# Indicateurs

Le module alimente :

- effectif par classe ;
- effectif par niveau ;
- effectif par année scolaire ;
- taux de transfert ;
- taux d'abandon.

---

# Évolutions futures

Le module est prévu pour supporter :

- inscription en ligne par les parents ;
- liste d'attente par classe ;
- réinscription automatique pour la rentrée suivante ;
- dossier numérique de l'élève ;
- historique complet de la scolarité ;
- attestations scolaires générées automatiquement.

---

# Conclusion

L'Enrollment constitue le pivot académique d'EduSen.

Il est la clé qui relie chaque élève à l'ensemble du système pédagogique : présences, évaluations, notes et bulletins dépendent tous de cet enregistrement.

Sa cohérence garantit l'intégrité de toutes les données académiques d'un élève.
