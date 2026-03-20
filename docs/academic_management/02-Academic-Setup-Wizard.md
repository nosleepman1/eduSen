# Academic Setup Wizard

## Introduction

Le **Academic Setup Wizard** est le premier module exécuté lors de la première connexion du directeur d'un établissement.

Son objectif est de permettre à une école de devenir opérationnelle en quelques minutes sans avoir à créer manuellement toutes les configurations académiques.

À l'issue du Wizard, l'établissement possède une structure académique complète et peut commencer les inscriptions des élèves.

---

# Objectifs

Le Setup Wizard doit permettre de :

- Configurer l'année scolaire initiale.
- Configurer le système académique.
- Générer automatiquement les périodes académiques.
- Préconfigurer les niveaux.
- Préconfigurer les classes.
- Préconfigurer les matières.
- Générer automatiquement toutes les données de base.
- Marquer l'établissement comme configuré.

---

# Conditions d'exécution

Le Wizard ne peut être exécuté que si :

- le directeur est authentifié ;
- l'établissement existe ;
- l'établissement n'a jamais été configuré.

Si la configuration est déjà terminée, toute tentative d'accès redirige automatiquement vers le Dashboard.

---

# Workflow général

```
Connexion

↓

Vérification de la configuration

↓

Configuration inexistante

↓

Setup Wizard

↓

Configuration terminée

↓

Dashboard
```

---

# Étapes

## Étape 1 — Création de la première année scolaire

Le directeur renseigne :

- Nom de l'année scolaire
- Date de début (par défaut : le 1er octobre de l'année en cours)
- Date de fin (par défaut : le 31 juillet de l'année suivante)

*Note importante* : Bien que la date de fin officielle de l'année scolaire de l'établissement soit le 31 juillet, le système permet de définir des dates d'arrêt anticipé des cours en juin pour certaines classes (notamment les classes sans examen national), tandis que les classes d'examen poursuivent leurs activités jusqu'en juillet.

Exemple

```
2026 - 2027

Début : 01/10/2026

Fin : 31/07/2027
```

Une seule année scolaire est créée.

Elle devient automatiquement l'année active.

---

## Étape 2 — Choix du système académique

Le directeur choisit le découpage de l'année.

Options proposées :

- Semestres
- Trimestres
- Quarters
- Personnalisé

Ce choix détermine automatiquement le nombre de périodes.

---

## Étape 3 — Génération des périodes

Le système génère automatiquement :

### Exemple Semestres

```
Semestre 1

Semestre 2
```

### Exemple Trimestres

```
Trimestre 1

Trimestre 2

Trimestre 3
```

### Exemple Quarters

```
Quarter 1

Quarter 2

Quarter 3

Quarter 4
```

En mode personnalisé, le directeur crée lui-même les périodes.

Toutes les périodes restent modifiables avant validation finale.

---

## Étape 4 — Préconfiguration des niveaux

Selon le pays sélectionné lors de la création de l'école ou selon le modèle choisi, EduSen propose automatiquement une liste de niveaux.

Le directeur peut :

- conserver un niveau ;
- retirer un niveau ;
- ajouter un niveau personnalisé.

---

## Étape 5 — Préconfiguration des classes

Pour chaque niveau sélectionné, EduSen propose automatiquement des classes.

Exemple

```
6ème

☑ A

☑ B

☑ C
```

Le directeur peut :

- ajouter une classe ;
- supprimer une proposition ;
- modifier le nom d'une classe.

Toutes les classes seront créées automatiquement.

---

## Étape 6 — Préconfiguration des matières

EduSen affiche une liste de matières recommandées.

Exemple

```
Mathématiques

Français

SVT

Physique

EPS

Anglais

Histoire & Géographie

Informatique

Education Civique

Musique

Economie Familiale et Sociale

etc...


```

Le directeur peut :

- sélectionner les matières utiles ;
- supprimer une matière ;
- ajouter une matière personnalisée ;
- modifier son coefficient par défaut.

---

## Étape 7 — Résumé

Le Wizard affiche un résumé complet.

Exemple

```
Année scolaire

↓

Périodes

↓

Niveaux

↓

Classes

↓

Matières
```

Le directeur valide définitivement.

---

## Étape 8 — Génération automatique

Le système crée automatiquement :

- AcademicYear
- AcademicPeriods
- SchoolCalendar (avec les jours fériés par défaut de l'établissement)
- Levels
- Classrooms
- Subjects

Toutes les opérations sont exécutées dans une transaction.

---

## Étape 9 — Finalisation

L'établissement est marqué comme :

```
is_academic_configured = true
```

Le directeur est redirigé vers le Dashboard.

---

# Paramètres

Le Wizard n'est jamais rejoué automatiquement.

Toute modification ultérieure s'effectue depuis :

```
Paramètres

↓

Configuration Académique
```

---

# Routes

Le module expose des routes dédiées.

Exemple

```
GET

/setup/status
```

Retourne l'état de configuration.

---

```
POST

/setup/start
```

Initialise le Wizard.

---

```
POST

/setup/complete
```

Valide définitivement la configuration.

---

# Sécurité

Seul le directeur peut lancer le Setup Wizard.

Toutes les routes sont protégées.

Toutes les données sont créées dans le tenant courant.

---

# Suppression

Aucune donnée n'est supprimée.

Toutes les entités utilisent SoftDeletes.

---

# Résultat

À la fin du Wizard, l'établissement est immédiatement prêt pour :

- gérer son personnel ;
- inscrire des élèves ;
- affecter des enseignants ;
- créer les emplois du temps ;
- lancer les évaluations.
