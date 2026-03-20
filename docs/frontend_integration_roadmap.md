# Roadmap d'intégration Frontend — Admin & Client

> **État au 4 août 2026.** L'étape 0 ci-dessous est faite : le commit `4437b3d`
> a livré le flux d'authentification complet de `client/` (`SelectSchoolPage`,
> `SetPasswordPage`, `MustChangePasswordScreen`, gating dans
> `DashboardLayout`), `axios.ts` lit bien `VITE_API_URL`, et `auth.types.ts`
> couvre les trois variantes `super_admin` / `single_school` / `multi_school`.
> Elle est conservée pour mémoire du raisonnement. Tout ce qui suit l'étape 0
> — système de design, séquençage des domaines, pattern d'écran — reste à
> faire. Voir [rapport_analyse.md](rapport_analyse.md) pour l'état d'ensemble.

## Périmètre

Ce document couvre uniquement `client/` et `admin/`. La landing page (Next.js, déjà déployée, CORS déjà géré) est **hors périmètre** — on ne la touche pas, on n'en parle pas ici. Idem pour `frontend/` (rôle flou, pas encore inclus) : on s'occupe de `client/` et `admin/` d'abord, on verra la suite après.

## Contexte

Le backend (`server/`) a maintenant un module Academic Management complet (Phase 1 + Phase 2 : AcademicYear, AcademicPeriod, SchoolCalendar/HolidayEvent, Specialization, Level, Classroom, Subject, Curriculum) et un module IAM complet (Personnel, Student, Teacher, Guardian, Invitation), tous testés (128 tests, SQLite + PostgreSQL) et documentés dans `docs/academic_management/` et `docs/users_and_roles/`.

| App | Stack | Persona | État réel |
|---|---|---|---|
| `admin/` | React + Vite + TS + TanStack Query + Radix/shadcn | Opérateur plateforme (super-admin) | 2 domaines réels sur 2 attendus (demandes d'inscription, abonnements plateforme). Intégration correcte. |
| `client/` | React + Vite + TS + TanStack Query + Radix/shadcn | Directeur/staff d'un établissement ("Espace École") | 3 domaines réels (dashboard, setup wizard, abonnement) sur ~12 attendus. Sidebar affiche 7 liens morts. |

**Ni `admin/` ni `client/` n'ont de UI pour le module Academic Management ou pour la majorité du module IAM** (Student/Teacher/Guardian/Personnel/Invitation) — c'est tout ce travail qui reste à faire.

La demande d'inscription d'école (formulaire externe déjà géré) arrive côté `admin/` via `GET /api/v1/platform/registration-requests` — cet écran est déjà câblé et fonctionnel (`SchoolRequestsTable`), rien à faire ici non plus.

---

## Étape 0 — Débloquer les fondations (avant toute nouvelle UI)

Ce sont des bugs actifs ou des trous de configuration sur `client/`/`admin/` eux-mêmes, pas du confort. Rien de solide ne peut se construire par-dessus tant que ce n'est pas réglé.

### 0.1 — Corriger le flux d'authentification de `client/`
`client/src/hooks/useAuth.ts` et `services/auth.service.ts` ne gèrent pas :
- le champ `type` (`super_admin` | `single_school` | `multi_school`) renvoyé par `AuthService::login()` côté serveur ;
- l'étape `POST /api/v1/auth/select-school` pour un directeur multi-écoles ;
- le flag `mustChangePassword` (personnel invité, première connexion).

**Résultat concret aujourd'hui** : un directeur multi-écoles ou un membre du personnel invité ne peut pas se connecter correctement. C'est un bug bloquant, à corriger avant tout le reste.

### 0.2 — Config d'environnement pour `client/`
`client/src/api/axios.ts` a `baseURL` codée en dur (`http://localhost:8000/api/v1`), sans variable d'env. Impossible de déployer `client/` sur un autre environnement sans modifier le code. Introduire `VITE_API_URL` (le pattern existe déjà dans `admin/`, à copier).

### 0.3 — Corriger la dérive des types TypeScript
Les types `client/src/types/subscription.types.ts` et `auth.types.ts` ont dérivé du contrat réel (des commentaires du type `// Changed from base_price` le prouvent) :
- `SubscriptionStatusEnum` côté client n'a que 4 valeurs (`ACTIVE`, `EXPIRED`, `CANCELLED`, `SUSPENDED`) sur les 7 réelles (manque `TRIAL`, `TRIAL_EXPIRED`, `GRACE_PERIOD`) — un abonnement en période d'essai ou de grâce s'affiche mal.
- `SubscriptionPlan` manque `currency`, `type`, `duration`.
- `User` a un champ `role` qui n'existe pas sur le modèle serveur (le rôle vient d'une table pivot par école, renvoyé séparément dans la réponse de login) et un champ `phone` alors que la colonne réelle est `phone_number`.

**Recommandation structurelle** : au lieu de types maintenus à la main des deux côtés (source de dérive garantie), envisager `laravel-typescript-transformer` ou un simple script qui génère les interfaces TS depuis les `Resource`/`FormRequest` Laravel. Pas bloquant pour démarrer, mais à faire avant que la dérive ne s'aggrave avec les nouveaux domaines.

---

## Système de design — fondation avant les 12 domaines

Vous voulez du "très très joli" partout. Construire ~12 domaines × 2 apps sans fondation commune garantit un résultat incohérent (chaque écran réinvente sa mise en page) et un gros refactor plus tard. `admin/` et `client/` utilisent déjà la même stack (Radix + shadcn/ui + Tailwind) — c'est l'occasion de factoriser plutôt que dupliquer.

### Recommandation : package UI partagé
Créer `packages/ui` (ou équivalent workspace npm/pnpm) avec les primitives communes aux deux apps :
- **`DataTable`** générique (tri, pagination, recherche, état vide, état de chargement squelette) — chaque domaine (Student, Teacher, Level, Classroom...) ne fait que passer ses colonnes et son hook react-query.
- **`FormSheet`/`FormDialog`** générique pour create/edit, avec gestion d'erreurs de validation 422 mappées automatiquement sur les champs du formulaire (react-hook-form + zod, déjà en place).
- **`StatusBadge`** paramétrable par enum (couleur cohérente pour DRAFT/ACTIVE/CLOSED/ARCHIVED, TRIAL/ACTIVE/EXPIRED, etc.).
- **`ConfirmDialog`** pour les actions destructives (archiver, supprimer, suspendre) — un seul pattern, pas un par écran.
- **`EmptyState`** et **`PageHeader`** (titre, fil d'Ariane, action principale à droite) cohérents partout.
- Tokens de design (couleurs, espacements, typographie) centralisés dans un thème Tailwind partagé plutôt que dupliqués dans chaque `tailwind.config`.

Si un monorepo tooling (Turborepo/Nx) est too much pour l'instant, une alternative légère : un simple package local publié en `file:` ou un dossier partagé importé via alias Vite — l'objectif est juste d'éviter la duplication, pas d'adopter un outil lourd.

### Convention de navigation
`client/` a une sidebar avec des liens morts (`/staff`, `/students`, `/classes`, `/subjects`, `/enrollments`, `/payments`, `/reports`) — ces liens dessinent déjà l'arborescence cible. Les faire correspondre à mesure qu'on avance, plutôt que de redessiner la nav à chaque domaine ajouté. `admin/` n'a lui aucune sidebar/nav du tout — à construire (voir Phase D).

---

## Séquençage des domaines

L'ordre suit trois règles strictes : (1) corriger l'existant avant d'ajouter, (2) construire dans l'ordre des dépendances métier (un Personnel doit exister avant qu'on puisse lui assigner un rôle de Teacher ; une AcademicYear doit exister avant qu'on puisse créer un Level dedans), (3) ne pas construire d'UI pour des endpoints qui n'existent pas encore côté serveur (TeachingAssignment/Timetable/Enrollment = Phase 3+ backend, pas encore fait).

### Phase A — IAM dans `client/` (premier domaine réel, valide le pattern)
Ordre : **Personnel → Teacher → Student → Guardian** (Personnel d'abord car Teacher en dépend ; Guardian en dernier car il se lie à un Student existant via `linkGuardian`).

Pour chaque entité : écran liste (`DataTable` + recherche + pagination + filtre par statut), formulaire création/édition (`FormSheet`), action archiver/restaurer (`ConfirmDialog`), et pour Personnel spécifiquement l'écran de gestion des rôles (`updateRoles`, déjà exposé côté API).

Pourquoi en premier : le backend IAM est le plus mûr (revue de code faite en Phase 1), les liens de sidebar existent déjà (`/staff`, `/students`), et ça valide le pattern `DataTable`/`FormSheet`/permissions avant de l'appliquer à 8 autres domaines.

### Phase B — Academic Management, socle temporel
**AcademicYear → AcademicPeriod → Specialization → SchoolCalendar/HolidayEvent.**
Un écran "Année scolaire active" en évidence sur le dashboard (`client/`) — c'est la donnée root dont dépend tout le reste (Level, Classroom, Curriculum en dépendent tous via `academic_year_id`).

### Phase C — Academic Management, structure pédagogique
**Subject (indépendant, peut se faire en parallèle de B) → Level → Classroom → Curriculum.**
Classroom est l'écran le plus riche (cycle de vie DRAFT/ACTIVE/CLOSED/ARCHIVED, assignation professeur principal, nom dynamique affiché) — prévoir plus de temps dessus. Curriculum en dernier car son écran le plus complexe (duplication vers une nouvelle année) dépend de Level et Subject existants.

### Phase D — `admin/`, compléter le périmètre plateforme existant
Pas de nouveau domaine métier ici — corriger l'existant : brancher `/billing` et `/invoices` sur de vraies données (actuellement 100% statique, et il n'existe même pas de concept "invoice" côté backend — soit créer l'endpoint, soit repenser l'écran autour de `SubscriptionPayment` qui existe déjà), ajouter la sidebar/nav qui manque totalement aujourd'hui.

### Hors scope pour l'instant
TeachingAssignment, Timetable, ScheduleEntry, Enrollment, LessonSession, Evaluation, Grade, Bulletin — aucun endpoint backend n'existe encore pour ces domaines (Phase 3 à 7 du `implementation_plan.md`, jamais commencées). Ne pas construire d'UI en avance de phase.

---

## Pattern d'écran standard (à répliquer pour chaque entité)

Pour éviter que chaque domaine soit designé différemment :

1. **Liste** : `PageHeader` (titre + bouton "Nouveau X" à droite) → `DataTable` avec recherche, filtre par statut si applicable, pagination serveur (toutes les API sont déjà paginées).
2. **Création/édition** : `FormSheet` (panneau latéral, pas une nouvelle page — garde le contexte de la liste visible), validation zod miroir des règles Laravel (ex. `after:start_date`, `min:1`), erreurs 422 mappées champ par champ.
3. **Actions d'état** (activer/clôturer/archiver/restaurer/ouvrir/fermer) : boutons contextuels selon le statut actuel + `ConfirmDialog` pour les actions irréversibles ou à fort impact.
4. **Détail** (si l'entité a des relations riches — ex. Classroom avec son professeur principal, Curriculum avec ses lignes) : soit un panneau latéral, soit une page dédiée si le contenu est dense.
5. **Permissions** : chaque bouton d'action vérifie le rôle/permission de l'utilisateur connecté (le backend expose déjà `permission:xxx.manage` par route — le frontend doit lire les permissions de l'utilisateur au login et masquer/désactiver les actions non autorisées plutôt que de laisser l'API renvoyer 403).

---

## Vérification à chaque étape

Ne pas se contenter de `tsc`/build qui passe. Pour chaque écran livré :
1. Lancer `client dev` (ou `admin dev`) + le serveur Laravel en local, cliquer réellement le parcours (créer, éditer, archiver, restaurer, chercher, paginer).
2. Tester au moins un cas de refus métier visible dans l'UI (ex. créer un Level avec un nom déjà pris dans l'année → le message d'erreur du backend doit s'afficher lisiblement, pas une erreur générique).
3. Vérifier le rendu avec un utilisateur qui n'a pas la permission `.manage` (ex. Teacher) — les actions doivent être masquées, pas juste échouer silencieusement.
4. Si `phpunit.pgsql.xml` est utilisé côté backend pour ce sprint, s'assurer qu'aucune régression backend n'accompagne les ajustements de contrat frontend.

---

## Résumé — prochaine action concrète

1. Corriger l'auth de `client/` (0.1) — bloquant, à faire en tout premier.
2. Config d'environnement + types (0.2, 0.3) — petit, rapide, évite de construire sur du sable.
3. Poser `packages/ui` avec `DataTable`/`FormSheet`/`StatusBadge`/`ConfirmDialog` — fondation design.
4. Construire Personnel (Phase A) comme premier domaine complet, de bout en bout, pour valider le pattern avant de l'industrialiser sur les 11 domaines suivants.
