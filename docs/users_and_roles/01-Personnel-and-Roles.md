# Personnel & Roles

---

# Objectif

Le module **Personnel & Roles** permet de gérer les employés de l'établissement (en dehors des élèves et des parents) et de leur attribuer des rôles d'accès pour administrer la plateforme.

Il constitue le socle de sécurité et de délégation d'EduSen.

---

# Position dans le domaine

```
School (Tenant)
  │
  ├── Personnel (Contrat, Département, Poste)
  │     │
  │     ├── User (Identifiants, Email, Statut)
  │     │
  │     └── Teacher (Profil Enseignant, optionnel)
  │
  └── RolePermission (Permissions associées à chaque rôle dans le tenant)
```

---

# Philosophie

Le personnel de l'école est enregistré dans la table `personnel`. Chaque personnel est rattaché à un utilisateur global (`User`) pour pouvoir se connecter à la plateforme.

Les droits d'accès dépendent des rôles de l'utilisateur appliqués dans l'établissement courant (`UserRole` lié à un `School`).

---

# Rôles administratifs (MVP)

Conformément à l'énumération `RoleEnum`, voici la répartition des responsabilités :

## OWNER / ADMIN (Directeur / Fondateur)
- Possède tous les droits sur l'établissement.
- Configure l'année scolaire et le système académique (Setup Wizard).
- Gère les abonnements, les factures et les paramètres de l'école.
- Peut recruter du personnel et modifier les rôles.

## SECRETARY (Secrétaire)
- Gère les inscriptions des élèves (`Enrollment`) et les fiches des parents.
- Renseigne les fiches élèves (`Student`) et parents (`Guardian`).
- Édite et imprime les attestations de scolarité.
- Peut consulter l'emploi du temps.

## SUPERVISOR (Surveillant général / Censeur)
- Responsable de la vie scolaire et des présences (`AttendanceRecord`).
- Valide ou rejette les motifs d'absence soumis par les parents.
- Supervise l'exécution réelle des cours (`LessonSession` en statut `PendingValidation`).
- Gère les emplois du temps (`Timetable` et `ScheduleEntry`).

## ACCOUNTANT (Comptable)
- Gère la facturation des élèves (hors périmètre académique MVP).
- Gère les paiements et les salaires du personnel.

---

# Droits d'inscription des élèves (Enrollment & Student)

La règle d'accès pour ajouter/inscrire un élève est strictement limitée aux rôles suivants :

- `OWNER` / `ADMIN` (Directeur)
- `SECRETARY` (Secrétaire)

Aucun autre rôle (y compris `TEACHER` ou `ACCOUNTANT`) n'a l'autorisation de créer une fiche élève ou de l'inscrire dans une classe.

---

# Règles métier

## RPR-001
Un membre du personnel appartient obligatoirement à un seul établissement (`School`).

## RPR-002
Chaque personnel doit être associé à un `User` unique pour pouvoir se connecter.

## RPR-003
Le statut d'un personnel peut être `Active` ou `Inactive`. Un personnel `Inactive` voit son compte `User` bloqué pour l'établissement.

## RPR-004
Seuls les rôles `OWNER`, `ADMIN` et `SECRETARY` possèdent la permission `enrollment:create` et `student:create`.

## RPR-005
Le nombre maximum d'enseignants enregistrés dans l'école ne peut pas dépasser la limite de l'abonnement en cours (`max_teachers`).

---

# Structure métier (Personnel)

Un enregistrement de `Personnel` possède :
- un établissement (`school_id`) ;
- un utilisateur lié (`user_id`) ;
- un numéro matricule (`registration_number`) généré automatiquement ;
- une date d'embauche (`hire_date`) ;
- un département (`department`) (ex: Administration, Pédagogie) ;
- un poste (`position`) (ex: Secrétaire, Surveillant, Enseignant) ;
- un statut (`status`) (Active / Inactive).

---

# Services

```
PersonnelService
```
Responsabilités :
- création d'un membre du personnel ;
- association à un compte utilisateur (`User`) ;
- attribution des rôles administratifs ;
- activation / désactivation du personnel.

---

# Conclusion

Le personnel constitue la force motrice de l'administration d'EduSen. Les rôles et permissions associés permettent une délégation sécurisée des tâches, garantissant que seuls les directeurs et secrétaires puissent gérer le fichier des élèves.
