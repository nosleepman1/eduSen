# Guardian Management

---

# Objectif

Le module **Guardian Management** gère les parents, tuteurs ou responsables légaux des élèves d'un établissement.

Il permet d'assurer le suivi des contacts familiaux et de désigner le responsable principal pour chaque élève.

---

# Position dans le domaine

```
School (Tenant)
  └── Guardian (Parent/Tuteur)
        │
        └── GuardianStudent (Relation pivot avec l'élève)
              └── Student (Élève lié)
```

---

# Philosophie et Accès des parents

Chaque parent ou tuteur est enregistré dans la table `guardians`. Il est lié à un compte `User` (avec son e-mail et son téléphone).

Les parents peuvent avoir un accès à la plateforme pour :
- consulter les bulletins de leurs enfants ;
- suivre les absences (`AttendanceRecord`) ;
- soumettre des justificatifs d'absence.

Un parent peut être rattaché à **plusieurs élèves** (ex: fratrie inscrite dans la même école).

Inversement, un élève peut avoir **plusieurs parents** déclarés (Père, Mère, Tuteur...).

---

# Responsabilités

Le module permet :
- créer un profil de parent/tuteur ;
- lier un parent à un ou plusieurs élèves avec un lien de parenté (Père, Mère, Tuteur...) ;
- définir le contact principal pour les urgences ;
- modifier les informations de contact (téléphone, adresse) ;
- archiver un parent.

---

# Structure métier (Guardian)

Un parent possède :
- un établissement (`school_id`) ;
- un utilisateur lié (`user_id`) (pour la connexion et l'authentification) ;
- un prénom (`firstname`) et un nom (`lastname`) ;
- un téléphone principal (`phone`) et secondaire (`phone_secondary`) ;
- un e-mail (`email`) ;
- une profession (`profession`) ;
- une adresse physique (`address`) ;
- un numéro de pièce d'identité (`national_id`) (CNI, Passeport).

## Relation Pivot (GuardianStudent)
La relation de liaison possède :
- un lien de parenté (`relationship`) (Père, Mère, Tuteur, Oncle...) ;
- un drapeau de contact principal (`is_primary_contact`) (vrai/faux).

---

# Règles métier

## RG-001
Un parent doit être associé à au moins un élève actif de l'établissement lors de sa création.

## RG-002
Chaque élève doit posséder **exactement un seul** contact principal désigné (`is_primary_contact = true`) parmi ses parents liés.

## RG-003
Les coordonnées de contact (téléphone et email) doivent être uniques par parent au sein du tenant.

## RG-004
Un parent associé à plusieurs enfants peut voir les informations (bulletins, absences) de chacun de ses enfants depuis son espace utilisateur unique.

## RG-005
Toutes les suppressions utilisent SoftDeletes.

---

# Services

```
GuardianService
```
Responsabilités :
- création du profil parent ;
- liaison parent-élève (`GuardianStudent`) ;
- validation de l'unicité du contact principal par élève ;
- modification des coordonnées ;
- archivage.

---

# Conclusion

La gestion des parents garantit un canal de communication fluide entre l'école et les familles. Grâce au lien multi-enfants et à la désignation du contact d'urgence principal, l'école dispose d'un annuaire de contacts à jour pour chaque élève.
