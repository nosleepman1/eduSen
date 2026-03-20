# Teacher Management

---

# Objectif

Le module **Teacher Management** gère les profils des enseignants de l'établissement.

Il décrit le profil académique d'un enseignant, ses qualifications et le lie à la planification des cours.

---

# Position dans le domaine

```
Personnel (département pédagogique)
  └── Teacher (diplôme, spécialité, expérience)
        └── TeachingAssignment (matière × classe × année)
```

---

# Philosophie

Dans EduSen, un enseignant n'est pas un profil isolé. C'est avant tout un membre du personnel (`Personnel`) ayant le poste d'enseignant et à qui l'on associe une fiche de spécialisation professionnelle (`Teacher`).

Cette structure permet de conserver les informations contractuelles (dans `Personnel`) et les informations pédagogiques (dans `Teacher`) séparément.

---

# Limite de souscription (max_teachers)

Conformément à la règle de plateforme, le nombre d'enseignants créés dans un établissement est limité par le plan d'abonnement actif.

## Règle de validation
Lors de la création d'un profil `Teacher` (ou de l'association du rôle `TEACHER` à un personnel), le système vérifie :

```
Nombre total de profils Teacher actifs < SubscriptionPlan.max_teachers
```

Si le seuil est atteint, la création de l'enseignant est bloquée et l'établissement est invité à mettre à niveau sa souscription.

---

# Responsabilités

Le module permet :
- créer un profil d'enseignant lié à un personnel existant ;
- modifier la spécialité et les informations professionnelles d'un enseignant ;
- valider la limite d'abonnement `max_teachers` ;
- consulter les enseignants de l'établissement ;
- archiver un profil d'enseignant.

---

# Structure métier (Teacher)

Un `Teacher` possède :
- une liaison vers le personnel (`personnel_id`) ;
- une spécialité principale (`speciality`) (ex: Mathématiques, Français) ;
- le diplôme le plus élevé (`diploma`) ;
- un numéro de licence d'enseignement (`license_number`) (optionnel) ;
- le nombre d'années d'expérience (`years_experience`).

---

# Règles métier

## RT-001
Un profil `Teacher` est obligatoirement rattaché à un membre du personnel (`Personnel`) actif de l'établissement.

## RT-002
Un enseignant doit posséder au moins une spécialité renseignée.

## RT-003
Le nombre maximum d'enseignants actifs dans l'établissement ne doit jamais dépasser `max_teachers` de la souscription active de l'école.

## RT-004
La suppression d'un enseignant s'effectue par SoftDelete sur le profil de personnel associé.

---

# Services

```
TeacherService
```
Responsabilités :
- création du profil d'enseignant ;
- validation de la limite d'abonnement ;
- modification des compétences et spécialités ;
- archivage et restauration.

---

# Conclusion

Le profil `Teacher` enrichit le dossier du personnel pour l'adapter aux besoins de planification académique. Sa gestion est étroitement contrôlée par les limites d'abonnement de la plateforme.
