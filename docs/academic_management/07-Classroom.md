# Academic Management

# 07 - Classroom

---

# Objectif

Le module **Classroom** représente les classes pédagogiques d'un établissement scolaire.

Une classe est un groupe d'élèves appartenant à un niveau donné pendant une année scolaire donnée.

Exemples :

- 6ème A
- 6ème B
- 5ème A
- Seconde S1
- Terminale L

La classe constitue l'unité centrale de l'organisation pédagogique quotidienne.

C'est elle qui reçoit :

- les élèves ;
- les enseignants ;
- les emplois du temps ;
- les évaluations ;
- les présences ;
- les bulletins.

---

# Position dans le domaine

```
AcademicYear

        │

        ▼

      Level

        │

        ▼

    Classroom

      ├── Enrollment

      ├── TeacherAssignment

      ├── Timetable

      ├── Attendance

      ├── Evaluation

      └── Bulletin
```

---

# Responsabilités

Le module permet :

- créer une classe ;
- modifier une classe ;
- ouvrir une classe ;
- fermer une classe ;
- archiver une classe ;
- restaurer une classe ;
- affecter un professeur principal ;
- définir la capacité maximale ;
- consulter les effectifs.

---

# Structure métier

Une classe possède :

- un nom (ex: "A", "B", "1") ;
- un code ;
- un niveau (Level) ;
- une spécialisation (`specialization_id`, optionnelle, ForeignKey vers la table des spécialisations) ;
- une année scolaire ;
- une capacité maximale ;
- un professeur principal (optionnel) ;
- une salle principale (optionnelle) ;
- un statut ;
- une description (optionnelle) ;
- une date de fin de cours personnalisée (optionnelle, par défaut celle de l'année scolaire (31 juillet), mais peut être fixée au 30 juin pour les classes hors examen).

Le nom complet affiché de la classe est construit dynamiquement en concaténant : Nom du Niveau + Code de Spécialisation (si présent) + Nom de la classe (ex: "Terminale L2 A", "Terminale S1 A", "6ème A").

---

# Exemples

```
Nom

6A

↓

Niveau

6ème

↓

Année

2026-2027

↓

Capacité

45 élèves

↓

Professeur principal

M. Diop
```

---

# Etats

Une classe peut être :

## Brouillon

Créée mais pas encore utilisée.

---

## Active

Les inscriptions sont autorisées.

Les emplois du temps sont créés.

Les présences sont enregistrées.

Les évaluations sont possibles.

---

## Fermée

Aucune nouvelle inscription.

Les données restent consultables.

---

## Archivée

SoftDelete.

Visible uniquement dans les archives.

---

# Règles métier

## RC-001

Une classe appartient obligatoirement à un seul niveau.

---

## RC-002

Une classe appartient obligatoirement à une seule année scolaire.

---

## RC-003

Deux classes peuvent porter le même nom uniquement si elles appartiennent à des années scolaires différentes.

Exemple

```
6A

↓

2026-2027

✔

6A

↓

2027-2028

✔
```

---

## RC-004

La combinaison :

```
AcademicYear

+

Level

+

Specialization (si applicable)

+

Nom de classe (ex: A, B)
```

doit être unique au sein de l'établissement.

---

## RC-005

Une classe ne peut jamais dépasser sa capacité maximale.

Cette règle est contrôlée lors des inscriptions.

---

## RC-006

Une classe peut être créée sans professeur principal.

L'affectation peut être réalisée ultérieurement.

---

## RC-007

Une classe peut être créée sans salle.

La salle pourra être renseignée plus tard.

---

## RC-008

Une classe utilisée par des inscriptions ne peut jamais être supprimée définitivement.

SoftDelete uniquement.

---

## RC-009

La date de fin de cours personnalisée d'une classe doit être comprise entre la date de début et la date de fin officielle de son année scolaire. Si elle n'est pas spécifiée, la date de fin de l'année scolaire (par défaut le 31 juillet) est appliquée. Les classes hors examens terminaux sont encouragées à être configurées avec une fin au 30 juin.

---

# Relations

Une Classroom possède :

- plusieurs Enrollments ;
- plusieurs TeacherAssignments ;
- plusieurs Timetables ;
- plusieurs Attendances ;
- plusieurs Evaluations ;
- plusieurs Bulletins;
- plusieurs CurriculumSubjects ;

Elle appartient à :

- un AcademicYear ;
- un Level.

---

# Cycle de vie

```
Création

↓

Configuration

↓

Ouverture

↓

Inscriptions

↓

Vie scolaire

↓

Fermeture

↓

Archivage
```

---

# Création

Lors de la création, le directeur choisit :

- le niveau ;
- le nom ;
- la capacité ;
- éventuellement le professeur principal.

Le système génère automatiquement le code interne de la classe si nécessaire.

---

# Professeur principal

Chaque classe peut posséder un professeur principal.

Ses responsabilités pourront inclure ultérieurement :

- suivi des élèves ;
- validation des absences ;
- consultation des bulletins ;
- communication avec les parents.

Une classe ne possède qu'un seul professeur principal actif à un instant donné.

---

# Capacité maximale

La capacité maximale représente le nombre d'élèves pouvant être inscrits simultanément.

Lors d'une inscription, le système vérifie :

```
Effectif actuel

<

Capacité maximale
```

Si la capacité est atteinte :

↓

L'inscription est refusée.

---

# Services

Le module est piloté par :

```
ClassroomService
```

Responsabilités :

- création ;
- modification ;
- ouverture ;
- fermeture ;
- archivage ;
- restauration ;
- affectation du professeur principal ;
- gestion des capacités.

---

# Routes API

```
GET     /classrooms

GET     /classrooms/{id}

POST    /classrooms

PUT     /classrooms/{id}

PATCH   /classrooms/{id}/open

PATCH   /classrooms/{id}/close

PATCH   /classrooms/{id}/principal-teacher

PATCH   /classrooms/{id}/restore

DELETE  /classrooms/{id}
```

---

# Cas limites

Créer deux classes "6A" dans la même année scolaire.

↓

Refus.

---

Créer une classe avec une capacité négative.

↓

Refus.

---

Inscrire un élève dans une classe complète.

↓

Refus.

---

Supprimer une classe contenant des élèves.

↓

Archivage uniquement.

---

Fermer une classe contenant encore des élèves.

↓

Autorisé.

Les données restent consultables.

Les nouvelles inscriptions sont interdites.

---

# Décisions d'architecture

## Pourquoi Classroom appartient-elle directement à AcademicYear ?

Parce qu'une classe représente une organisation annuelle.

Chaque rentrée scolaire produit une nouvelle instance de la classe.

Cette approche permet :

- un historique complet ;
- des statistiques par année ;
- une duplication simplifiée lors de la préparation de la rentrée ;
- une isolation parfaite des données.

---

## Pourquoi conserver les classes même après la fin de l'année ?

Les classes sont référencées par :

- les bulletins ;
- les évaluations ;
- les présences ;
- les inscriptions ;
- les statistiques.

Une suppression physique casserait ces relations.

Le système utilise donc exclusivement SoftDeletes.

---

# Evolutions futures

Le module est conçu pour accueillir :

- plusieurs professeurs principaux (titulaire / adjoint) ;
- des salles dédiées ;
- des bâtiments ;
- des divisions pédagogiques ;
- des classes virtuelles ;
- des regroupements temporaires ;
- des indicateurs de performance par classe ;
- des tableaux de bord de suivi.

---

# Conclusion

La Classroom constitue l'unité pédagogique opérationnelle d'EduSen.

Elle centralise les élèves, les enseignants et les activités pédagogiques d'une année scolaire donnée.

Toutes les fonctionnalités liées à la vie scolaire s'appuient directement sur cette entité, ce qui en fait l'un des piliers du domaine Academic Management.
