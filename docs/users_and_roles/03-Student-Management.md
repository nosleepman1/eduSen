# Student Management

---

# Objectif

Le module **Student Management** gère les informations administratives, personnelles et médicales des élèves inscrits au sein de l'établissement.

Il s'agit du registre unique des élèves.

---

# Position dans le domaine

```
School (Tenant)
  └── Student (Fiche élève : nom, naissance, groupe sanguin)
        │
        ├── Enrollment (Inscription annuelle dans une classe)
        │
        └── GuardianStudent (Lien pivot avec les parents)
```

---

# Philosophie et Login Élève (MVP)

**Pas de login élève dans le MVP** :
Pour la version MVP d'EduSen, les élèves n'ont pas d'identifiants de connexion à la plateforme. Ils ne se connectent pas au système. 

Toutes les informations les concernant sont gérées de manière centralisée par :
- les administrateurs et secrétaires de l'école ;
- les parents ou tuteurs (qui ont eux un accès ou des contacts directs).

---

# Limite de souscription (max_students)

Le nombre total d'élèves enregistrés et actifs dans un établissement ne peut pas dépasser la limite du plan d'abonnement.

## Règle de validation
Lors de la création d'une fiche `Student` (ou de son inscription `Enrollment` active), le système vérifie :

```
Nombre total d'élèves actifs < SubscriptionPlan.max_students
```

Si la limite est atteinte, l'ajout ou l'inscription est bloquée jusqu'à la mise à niveau de l'abonnement.

---

# Responsabilités

Le module permet :
- créer une fiche élève ;
- modifier les données personnelles (photo, adresse) ;
- enregistrer les données médicales (groupe sanguin, allergies) ;
- vérifier la limite d'abonnement `max_students` ;
- archiver la fiche d'un élève.

---

# Structure métier (Student)

Un élève possède :
- un établissement (`school_id`) ;
- un utilisateur optionnel (`user_id`) (non utilisé pour connexion dans le MVP, uniquement pour l'unicité future) ;
- un matricule élève (`student_number`) unique généré automatiquement ;
- un prénom (`firstname`) et un nom (`lastname`) ;
- une date de naissance (`birth_date`) et lieu de naissance (`birth_place`) ;
- un genre (`gender`) (Masculin / Féminin) ;
- une nationalité (`nationality`) ;
- une photo (`photo`) ;
- un groupe sanguin (`blood_group`) ;
- des informations médicales (`medical_info`) (ex: allergies, traitements) ;
- une adresse physique (`address`) ;
- un statut (`status`) (Active / Suspended / Left) ;
- une date d'inscription d'origine (`enrolled_at`).

---

# Règles métier

## RS-001
Un élève est obligatoirement rattaché à un établissement (`School`) actif.

## RS-002
Le matricule de l'élève (`student_number`) doit être unique sur l'ensemble de la plateforme. Il est généré automatiquement par le service de séquence.

## RS-003
Les informations sensibles (médicales) sont protégées et visibles uniquement par le personnel autorisé (Directeur, Secrétaire, Professeur Principal).

## RS-004
Un élève inactif (`status != Active`) ne peut pas avoir d'inscription (`Enrollment`) active pour l'année en cours.

## RS-005
Le nombre total d'élèves actifs ne peut pas dépasser `max_students` de la souscription.

---

# Services

```
StudentService
```
Responsabilités :
- création de la fiche élève ;
- validation des limites de souscription ;
- mise à jour des données de santé et de contact ;
- archivage (SoftDelete).

---

# Conclusion

La fiche `Student` centralise toutes les données individuelles d'un élève. N'ayant pas d'accès direct par login dans le MVP, sa gestion repose entièrement sur le secrétariat et l'administration de l'école.
