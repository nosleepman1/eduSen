# Rapport d'analyse — EduSen

*Analyse effectuée le 4 août 2026 sur `main` (commit `403fb2d`).*

---

## 1. Vue d'ensemble

EduSen est un SaaS multi-tenant de gestion scolaire (contexte sénégalais : NINEA, FCFA, PayDunya). Le dépôt est un monorepo « à plat » de 4 applications, 559 fichiers versionnés.

| Application | Stack | Rôle | Taille | Maturité |
|---|---|---|---|---|
| `server/` | Laravel 13 · PHP 8.3 · PostgreSQL · Sanctum · Pest 4 | API REST v1 | 224 fichiers PHP, ~10 000 lignes | **Mûr** |
| `client/` | React · Vite · TS · TanStack Query · Radix/shadcn | Espace École (directeur/staff) | 50 fichiers, ~3 500 lignes | **Partiel** |
| `admin/` | idem `client/` | Console plateforme (super-admin) | 39 fichiers, ~2 500 lignes | **Partiel + build cassé** |
| `frontend/` | React · Vite · TS · framer-motion | ? (login/register/home seulement) | 29 fichiers, ~1 560 lignes | **Abandonné** |

Le backend est nettement en avance sur les frontends. C'est le déséquilibre structurant du projet.

---

## 2. Backend (`server/`) — état réel

### Ce qui existe

- **116 routes API** sous `/api/v1`, réparties en 5 fichiers de routes par domaine (`auth`, `platform`, `school`, `academic`, `subscription`) + webhooks.
- **34 modèles**, **40 migrations**, **25 services**, **11 repositories**, **17 enums**, **7 middlewares**, **15 factories**.
- **Domaines livrés** :
  - *IAM* : Personnel, Teacher, Student, Guardian, Invitation, rôles & permissions, historique de connexion.
  - *Academic Management Phases 1–2* : AcademicYear, AcademicPeriod, SchoolCalendar/HolidayEvent, Specialization, Level, Classroom, Subject, Curriculum.
  - *Platform* : demandes d'inscription d'école, approbation, dashboard.
  - *Billing* : plans, cycles, abonnements, factures, paiements, PayDunya, gating par feature.
  - *Transverse* : audit log (trait `Auditable`), soft deletes, UUID partout, numérotation automatique (`RegistrationNumberService`).

### Qualité du code

L'architecture est **cohérente et disciplinée** : Controller fin → FormRequest → Service → (Repository si nécessaire) → Resource. Réponses normalisées via `App\Support\ApiResponse`, exceptions API rendues en JSON dans `bootstrap/app.php`.

Points positifs concrets relevés :
- Isolation tenant appliquée **à deux niveaux** : `TenantHelper::assertOwnedByCurrentSchool()` sur les modèles route-bound, et `Rule::exists(...)->where('school_id', TenantHelper::currentSchoolId())` dans les FormRequests. Le comportement est *fail-closed* : sans contexte tenant, les requêtes ne remontent rien.
- Verrouillage transactionnel réel là où il compte (`LevelService::reorder()` avec `lockAllForYear()` contre les interleavings concurrents).
- Eager loading présent sur les `index()` qui exposent des relations (pas de N+1 évident).
- Couverture d'index correcte sur les migrations (school_id, clés étrangères, unicités métier).

### Tests

**128 tests, 310 assertions, tous verts en 9,2 s** (SQLite in-memory). Une seconde configuration `phpunit.pgsql.xml` permet de rejouer la suite sur PostgreSQL réel — bonne pratique, les deux moteurs ne se comportent pas pareil sur les enums et les contraintes.

---

## 3. Problèmes identifiés

### 🔴 Critique — Le webhook PayDunya permet d'activer un abonnement gratuitement

`server/app/Http/Controllers/Api/V1/Webhooks/PayDunyaWebhookController.php`

La route `POST /api/v1/webhooks/paydunya/subscription` est publique (normal pour un webhook) mais **aucune vérification d'authenticité n'est effectuée** :

```php
private function isValidSignature(Request $request): bool
{
    // Add PayDunya IP validation or hash validation here if needed
    return true;
}
```

Le champ `hash` du payload est lu (`$hash = $data['hash'] ?? null;`) puis **jamais utilisé**.

**Scénario d'exploitation, sans rien deviner** : un directeur d'école lance un achat légitime → une `SubscriptionInvoice` en `PENDING` est créée → `GET /api/v1/subscriptions/...` lui renvoie ses propres factures avec leurs UUID → il envoie lui-même :

```json
{ "custom_data": { "invoice_id": "<son-uuid>" }, "status": "completed" }
```

Le contrôleur crée un `SubscriptionPayment` en `SUCCESS`, passe la facture en `PAID`, et appelle `SubscriptionService::subscribe()`. **Abonnement payant activé sans paiement.**

Aggravants :
- **Aucune idempotence** : rejouer le même webhook crée des paiements en double et ré-souscrit.
- **Aucune transaction DB** : un échec entre l'étape 2 (facture PAID) et l'étape 3 (souscription) laisse une facture payée sans abonnement.
- **Aucun test** ne couvre ce contrôleur (`grep -rl paydunya tests/` → vide), alors que c'est le seul point d'entrée non authentifié de l'API.

À corriger avant toute mise en production. La signature PayDunya se vérifie par un hash SHA-512 de la master key ; y ajouter un contrôle d'idempotence sur `receipt_id` et envelopper le tout dans `DB::transaction()`.

### 🟠 Le build de `admin/` est cassé

`npm run build` = `tsc -b && vite build`. `tsc -b` sort en **code 2** :

```
src/components/dashboard/SubscriptionsTable.tsx(2,62): TS6133 'TableCell' déclaré mais jamais utilisé
src/pages/billing/InvoicesList.tsx(1,29|46|58): TS6133 'CardDescription' / 'CardHeader' / 'CardTitle'
```

`admin/` **n'est pas déployable en l'état**. Ce sont 4 imports morts, correction en une minute — mais l'absence de CI fait que personne ne l'a vu. `client/` compile proprement (exit 0).

### 🟠 Les Policies Laravel sont du code mort

`app/Policies/` contient `SchoolPolicy`, `SchoolRegistrationRequestPolicy`, `UserPolicy`. Elles ne sont **jamais invoquées** : zéro appel à `authorize()` dans les 25 contrôleurs, zéro `Gate::`, zéro `->can()`. L'autorisation passe intégralement par les middlewares `permission:` / `role:` sur les routes.

Ce n'est pas un trou de sécurité (les middlewares font le travail), mais c'est trompeur : un développeur qui lit `UserPolicy` croira que la règle s'applique. Soit les brancher, soit les supprimer.

### 🟠 `client/` : 7 liens de navigation morts

`client/src/components/layout/sidebar/SidebarMenu.tsx` déclare 9 entrées ; `client/src/App.tsx` n'en route que 2 (`/`, `/subscription`). Les 7 autres — `/staff`, `/students`, `/classes`, `/subjects`, `/enrollments`, `/payments`, `/reports` — tombent sur le catch-all `<Route path="*" element={<Navigate to="/" replace />} />`.

Effet utilisateur : **le clic ne fait rien de visible**, on reste sur le dashboard sans message. Pire qu'une page « bientôt disponible ».

### 🟡 `admin/` : `/billing` et `/invoices` affichent des données inventées

`admin/src/pages/billing/InvoicesList.tsx` contient un tableau codé en dur (« Lycée d'Excellence », « 150 000 FCFA »…). Rien ne vient de l'API. Il n'existe d'ailleurs pas d'endpoint « invoices » côté plateforme — `SubscriptionInvoice` existe en base mais n'est exposé que par école.

Écran de démo passé en production silencieusement. À brancher sur de vraies données ou à retirer de la navigation.

### 🟡 `frontend/` : troisième application fantôme

29 fichiers, trois pages (login, register, home), un `Dockerfile` — la seule des quatre apps à en avoir un. Pas de `node_modules`, stack divergente (framer-motion, `@fontsource-variable/geist`, contexte React au lieu de Zustand), aucune référence depuis les autres apps. La roadmap elle-même la qualifie de « rôle flou ».

C'est ~1 560 lignes de code que tout le monde doit lire et personne ne maintient. Décision à prendre : réactiver ou supprimer.

### 🟡 Duplication de la suite de tests académiques

Quatre tests existent en double, à deux emplacements :

| Test | `tests/Feature/` | `tests/Feature/Api/V1/Academic/` |
|---|---|---|
| AcademicYearTest | 63 lignes | 146 lignes |
| AcademicPeriodTest | 87 | 165 |
| SchoolCalendarTest | 81 | 86 |
| SpecializationTest | 70 | 46 |

Les versions racine testent le modèle/la migration, les versions imbriquées testent l'API — mais les noms identiques rendent `--filter=AcademicYearTest` ambigu et laissent croire à un oubli de nettoyage.

### 🟡 Pas de CI, pas d'orchestration locale

Aucun `.github/`, aucun `docker-compose.yml` à la racine. Conséquences observables : le build cassé de `admin/` a été mergé, et démarrer le projet suppose de lancer manuellement PostgreSQL + Redis + `artisan serve` + deux `vite`. Une CI minimale (`pint --test`, `artisan test`, `tsc -b` sur les trois apps) aurait bloqué le problème n°2.

### 🟡 Divers

- **`phpunit.pgsql.xml`** versionne des identifiants en clair (`DB_PASSWORD=root`). Test uniquement, mais habitude à ne pas prendre.
- **`baseURL` incohérente** : `admin/` pointe sur `http://localhost:8000` et préfixe `/api/v1` dans chaque service ; `client/` pointe sur `http://localhost:8000/api/v1`. Deux conventions pour la même API.
- **`TenantHelper`** stocke le tenant dans le conteneur applicatif (`app()->instance('current_school_id')`) sans jamais le vider. Sans danger en PHP-FPM (processus par requête), mais devient une fuite inter-requêtes sous Laravel Octane et inter-jobs dans un worker de queue. À garder en tête avant tout passage en runtime persistant.
- **Types TypeScript maintenus à la main** des deux côtés du contrat. Ils sont actuellement alignés (`client/src/types/auth.types.ts` reflète bien les trois variantes `super_admin` / `single_school` / `multi_school`), mais rien ne garantit qu'ils le restent.
- **Queue** : la documentation annonce Redis, `.env.example` déclare `QUEUE_CONNECTION=database`.
- **`SchoolDashboardController.php:32`** : `$classesCount = 0; // TODO` — le modèle `Classroom` existe pourtant depuis la Phase 2.
- **Branches** : 6 branches de feature déjà mergées traînent en local et sur `origin`.

---

## 4. Documentation

`docs/` (non versionné — `git status` le donne en `??`) contient 9 287 lignes de spécification : 21 documents pour Academic Management, 5 pour Users & Roles, plus une roadmap d'intégration frontend. La qualité est élevée et la spécification va bien au-delà de ce qui est codé (Timetable, Enrollment, Evaluation, Grade, Bulletin sont spécifiés en détail, non implémentés).

Deux réserves :

1. **Ce répertoire n'est pas commité.** 9 287 lignes de spécification qui n'existent que sur une machine.
2. **`docs/frontend_integration_roadmap.md` est partiellement périmé.** Ses points bloquants 0.1 (flux d'auth multi-écoles), 0.2 (`VITE_API_URL`) et 0.3 (dérive des types) ont été résolus par le commit `4437b3d` : `SelectSchoolPage.tsx`, `SetPasswordPage.tsx`, `MustChangePasswordScreen.tsx` et le gating dans `DashboardLayout.tsx` sont en place, `axios.ts` lit bien `import.meta.env.VITE_API_URL`, et `auth.types.ts` couvre les trois variantes de login. La roadmap reste juste sur tout le reste (séquençage des domaines, package UI partagé).

---

## 5. Écart backend / frontend

C'est le constat principal du rapport.

| | Backend | `client/` | `admin/` |
|---|---|---|---|
| Domaines exposés | 116 endpoints, ~13 entités | 3 écrans réels | 2 domaines réels + 2 factices |
| Academic Management | 8 entités complètes | **aucune UI** | **aucune UI** |
| IAM (Student/Teacher/Guardian/Personnel/Invitation) | 5 entités complètes | **aucune UI** | — |

**La quasi-totalité de la valeur produite côté serveur est aujourd'hui inaccessible aux utilisateurs.** Level, Classroom, Subject, Curriculum, Student, Teacher, Guardian, Personnel, Invitation : testés, documentés, sans une seule interface.

---

## 6. Recommandations, par ordre

1. **Sécuriser le webhook PayDunya** — signature, idempotence, transaction, tests. Bloquant production.
2. **Réparer le build de `admin/`** — 4 imports morts.
3. **Mettre en place une CI minimale** — `pint --test`, `artisan test`, `tsc -b ×3`. C'est ce qui empêche le point 2 de se reproduire.
4. **Commiter `docs/`**.
5. **Trancher sur `frontend/`** — réactiver ou supprimer.
6. **Poser la fondation UI partagée** (`DataTable`, `FormSheet`, `StatusBadge`, `ConfirmDialog`) avant de construire les ~12 domaines manquants. Construire 12 domaines × 2 apps sans socle garantit l'incohérence et un refactor.
7. **Rattraper l'écart backend/frontend** en suivant le séquençage de la roadmap (Personnel → Teacher → Student → Guardian, puis AcademicYear → … → Curriculum), en masquant ou en désactivant les liens de sidebar tant que l'écran n'existe pas.
8. **Nettoyage** : supprimer ou brancher les Policies, dédupliquer les 4 tests, unifier la convention `baseURL`, purger les 6 branches mergées, traiter le TODO du dashboard.

---

## Annexe — commandes de vérification

```bash
cd server && php artisan test --compact
```

```bash
cd admin && npx tsc -b
```

```bash
cd server && php artisan route:list --except-vendor --path=api
```
