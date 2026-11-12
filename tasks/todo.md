# Tâches : biweb/laravel-openapi v1

Voir [`plan.md`](plan.md) pour les décisions et [`../SPEC.md`](../SPEC.md) pour le contrat.
Vérification commune à chaque tâche : `composer test`, `composer analyse`, `vendor/bin/pint --test`.

---

## Phase 1 : fondations et risques

### T1 : squelette du document + validateurs de test

**Description :** `LaravelOpenapi::generate(?string $document)` renvoie `openapi`, `info`, `servers` et `paths` (vide) à partir de la nouvelle config multi-documents. La version `openapi` est contrôlée (`3.0.x` / `3.1.x`, sinon `InvalidOpenapiDefinition`). On ajoute les deux validateurs de dev, les méta-schémas officiels et le helper Pest `toBeValidOpenapi()`.

**Critères d'acceptation :**
- [x] Un document sans aucune route est valide en 3.0.3 et en 3.1.0, et `paths` vaut `{}` en JSON.
- [x] Un document volontairement invalide (sans `info`) est rejeté par chacun des deux validateurs.
- [x] Une version `2.0` ou `3.2.0` lève une erreur, et un document inconnu aussi.

**Vérification :** `vendor/bin/pest tests/Feature/DocumentTest.php`

**Dépendances :** aucune

**Fichiers :** `composer.json`, `config/openapi.php`, `src/LaravelOpenapi.php`, `src/Exceptions/InvalidOpenapiDefinition.php`, `tests/Pest.php`, `tests/Fixtures/schemas/oas-3.0.json`, `tests/Fixtures/schemas/oas-3.1.json`, `tests/Feature/DocumentTest.php`

**Taille :** M (les méta-schémas sont des fichiers copiés, pas du code)

---

### T2 : `#[Operation]` / `#[Parameter]` sur contrôleur classique

**Description :** les attributs `Operation` et `Parameter` sont créés. Les routes du préfixe configuré dont l'action porte `#[Operation]` deviennent des `paths` : summary, description, tags, deprecated, operationId, security, puis paramètres explicites et paramètres de chemin `{x}` ajoutés automatiquement. Les routes closure, les routes hors préfixe et la méthode `HEAD` sont exclues.

**Critères d'acceptation :**
- [x] Une route sans `#[Operation]` est absente, même dans le préfixe ; une route annotée hors préfixe aussi.
- [x] `{id}` donne un paramètre path required de type string, qu'un `#[Parameter(name: 'id', type: 'integer')]` remplace.
- [x] Un `operationId` explicite sur une route à plusieurs méthodes lève une erreur.

**Vérification :** `vendor/bin/pest tests/Feature/OperationTest.php`, puis `toBeValidOpenapi()` en 3.0 et en 3.1

**Dépendances :** T1

**Fichiers :** `src/Attributes/Operation.php`, `src/Attributes/Parameter.php`, `src/LaravelOpenapi.php`, `tests/Fixtures/Http/UserController.php`, `tests/Feature/OperationTest.php`

**Taille :** M

---

### T3 : classes invocables (ADR)

**Description :** pour une action `Class@__invoke`, les attributs d'opération sont lus sur la classe ou sur `__invoke`. Les deux à la fois lèvent une erreur. Sur un contrôleur classique, les attributs de classe sont ignorés.

**Critères d'acceptation :**
- [x] `Route::get('/users/{id}', ShowUserAction::class)` (attributs sur la classe) et une autre Action annotée sur `__invoke` produisent chacune l'opération attendue, identique à celle d'un contrôleur classique annoté de la même façon.
- [x] Une Action annotée sur la classe et sur `__invoke` lève `InvalidOpenapiDefinition`, avec un message qui nomme la classe.
- [x] Un `#[Operation]` posé sur la classe d'un contrôleur non invocable n'a aucun effet.

**Vérification :** `vendor/bin/pest tests/Feature/InvokableActionTest.php`

**Dépendances :** T2

**Fichiers :** `src/LaravelOpenapi.php`, `tests/Fixtures/Http/Actions/ShowUserAction.php`, `tests/Fixtures/Http/Actions/DeleteUserAction.php`, `tests/Fixtures/Http/Actions/AmbiguousAction.php`, `tests/Feature/InvokableActionTest.php`

**Taille :** M

---

### Checkpoint A
- [ ] `composer test` et `composer analyse` passent
- [ ] Revue humaine des signatures `Operation` et `Parameter`

---

## Phase 2 : schémas

### T4 : `#[Schema]` / `#[Property]` : scalaires, nullable, required

**Description :** `SchemaFactory` transforme une classe `#[Schema]` en schéma `object` : il déduit `int`/`float`/`string`/`bool`, le rend nullable (`nullable: true` en 3.0, `["T","null"]` en 3.1) et calcule `required` (non nullable et sans valeur par défaut, sauf `required` explicite). Les surcharges `#[Property]` (type, format, description, example) sont appliquées. Des `#[Property(name:, type:)]` répétés sur la classe (API Resources, modèles) remplacent la lecture des propriétés PHP. Le schéma est enregistré dans `components.schemas`.

**Critères d'acceptation :**
- [x] La table de déduction de la spec est couverte en 3.0 et en 3.1 (tests Unit, avec dataset).
- [x] Les règles `required` sont vérifiées : nullable, valeur par défaut, `required: true/false` explicite.
- [x] Une classe sans `#[Schema]` passée à la factory lève une erreur qui nomme la classe.
- [x] Une `JsonResource` décrite par des `#[Property]` de classe ne donne que les champs déclarés, et un `name` ou un `type` manquant lève une erreur qui nomme la classe.

**Vérification :** `vendor/bin/pest tests/Unit/SchemaFactoryTest.php`

**Dépendances :** T1 (indépendante de T3)

**Fichiers :** `src/Attributes/Schema.php`, `src/Attributes/Property.php`, `src/SchemaFactory.php`, `tests/Fixtures/Data/*.php`, `tests/Unit/SchemaFactoryTest.php`

**Taille :** M

---

### T5 : `$ref` imbriqués, tableaux, enums, collisions

**Description :** une propriété typée par une classe `#[Schema]` produit un `$ref`, et la classe référencée est enregistrée à son tour (une seule fois, même en cas de cycle). Un `array` exige `#[Property(items: …)]` (class-string ou type scalaire). Un `BackedEnum` donne le type de son backing et ses valeurs en `enum`. Deux classes qui donnent le même nom de composant lèvent une erreur.

**Critères d'acceptation :**
- [ ] Une référence circulaire (`User` → `Team` → `User`) se termine, avec deux composants et des `$ref`.
- [ ] Un `array` sans `items` lève une erreur qui nomme la classe et la propriété.
- [ ] Une collision `A\User` / `B\User` lève une erreur qui nomme les deux classes, et `#[Schema(name:)]` la résout.

**Vérification :** `vendor/bin/pest tests/Unit/SchemaFactoryTest.php`

**Dépendances :** T4

**Fichiers :** `src/SchemaFactory.php`, `tests/Fixtures/Data/*.php`, `tests/Unit/SchemaFactoryTest.php`

**Taille :** S

---

### T6 : `#[RequestBody]` / `#[Response]`

**Description :** les attributs `RequestBody` et `Response` (répétable, avec `isList`) sont branchés sur l'opération. Ils référencent les schémas via `SchemaFactory`. On crée le jeu de fixtures complet et les fichiers attendus `expected/document-3.0.json` et `expected/document-3.1.json`.

**Critères d'acceptation :**
- [ ] `#[Response(200, schema: UserData::class, isList: true)]` donne `content.application/json.schema = {type: array, items: {$ref}}`.
- [ ] `#[Response(204, 'Supprimé')]` est émis sans `content`.
- [ ] Une réponse `JsonResource` est enveloppée selon `$wrap` (`{data: $ref}`, ou `data` en tableau avec `isList`), et ne l'est pas si `$wrap` vaut `null`.
- [ ] Les fixtures complètes sont valides et égales aux fichiers `expected/`, en 3.0 et en 3.1.

**Vérification :** `vendor/bin/pest tests/Feature/FullDocumentTest.php`

**Dépendances :** T3, T5

**Fichiers :** `src/Attributes/RequestBody.php`, `src/Attributes/Response.php`, `src/LaravelOpenapi.php`, `tests/Fixtures/expected/*.json`, `tests/Feature/FullDocumentTest.php`

**Taille :** M

---

### Checkpoint B
- [ ] Les fixtures complètes génèrent des documents 3.0 et 3.1 valides et égaux à `expected/*.json`
- [ ] Revue humaine des fichiers `expected/`

---

## Phase 3 : multi-documents et sorties

### T7 : multi-documents + sécurité

**Description :** `Operation::documents` restreint une opération à certains documents, et chaque document filtre sur ses propres préfixes. Les `security_schemes` de la config vont dans `components.securitySchemes`, et le `security` global du document est émis à la racine.

**Critères d'acceptation :**
- [ ] Deux documents (`public` sur `api`, `internal` sur `internal`) ne contiennent chacun que leurs routes.
- [ ] Une opération avec `documents: ['internal']` est absente de `public`, même si elle est dans son préfixe.
- [ ] Les `securitySchemes` et le `security` global sont présents, et le document reste valide.

**Vérification :** `vendor/bin/pest tests/Feature/MultiDocumentTest.php`

**Dépendances :** T6

**Fichiers :** `src/LaravelOpenapi.php`, `tests/Feature/MultiDocumentTest.php`, `tests/Fixtures/Http/InternalController.php`

**Taille :** S

---

### T8 : commande `openapi:generate`

**Description :** la commande `openapi:generate {document?} {--all} {--output=} {--format=}` remplace le placeholder. Le format vient de `--format`, sinon de l'extension. Le YAML passe par `symfony/yaml` (avec `DUMP_OBJECT_AS_MAP`). Les répertoires manquants sont créés. Les erreurs `InvalidOpenapiDefinition` donnent un message et le code `FAILURE`.

**Critères d'acceptation :**
- [ ] Le fichier JSON écrit est égal à `generate()`, et le YAML relu par `Yaml::parse` est égal au JSON.
- [ ] `--all` écrit un fichier par document, au chemin `output` de chacun.
- [ ] Un document inconnu ou une annotation invalide donne un code de sortie non nul et un message qui nomme la cause.

**Vérification :** `vendor/bin/pest tests/Feature/GenerateCommandTest.php`, et `composer update --prefer-lowest` reste vert

**Dépendances :** T7

**Fichiers :** `composer.json`, `src/Commands/GenerateCommand.php` (et suppression de `LaravelOpenapiCommand.php`), `src/LaravelOpenapiServiceProvider.php`, `tests/Feature/GenerateCommandTest.php`

**Taille :** M

---

### T9 : route qui sert le fichier

**Description :** `routes/openapi.php` enregistre `GET {route.uri}` pour chaque document qui a `route.enabled`, avec son middleware et le nom du document en `defaults`. `OpenapiController` (invocable) sert le fichier `output` avec le `Content-Type` correspondant à son extension, ou répond 404 si le fichier n'existe pas. Le fichier de routes est chargé avec `hasRoute('openapi')`.

**Critères d'acceptation :**
- [ ] Route désactivée → 404 ; route activée sans fichier → 404.
- [ ] Route activée avec un fichier `.json` → 200 avec `application/json` ; fichier `.yaml` → 200 avec `application/yaml`.
- [ ] Le middleware configuré est appliqué, et `route:cache` fonctionne (pas de closure).

**Vérification :** `vendor/bin/pest tests/Feature/RouteTest.php`

**Dépendances :** T8

**Fichiers :** `routes/openapi.php`, `src/Http/OpenapiController.php`, `src/LaravelOpenapiServiceProvider.php`, `tests/Feature/RouteTest.php`

**Taille :** M

---

### T10 : README + CLAUDE.md

**Description :** le README boilerplate est remplacé par la doc d'usage : installation, config, attributs (contrôleur classique et Action ADR), commande, route, et rappel de relancer `openapi:generate` au déploiement. La section « Project state » de `CLAUDE.md` est mise à jour (elle dit encore que les tests et phpstan sont absents et qu'il n'y a pas de dépôt git). Une entrée est ajoutée dans `CHANGELOG.md`.

**Critères d'acceptation :**
- [ ] Les exemples du README correspondent aux fixtures testées (mêmes signatures).
- [ ] `CLAUDE.md` décrit l'architecture réelle (attributs, `SchemaFactory`, commande, route).
- [ ] Il ne reste plus aucun placeholder du skeleton (`My command`, `This is my package`).

**Vérification :** relecture humaine ; `grep -r "This is my package\|My command"` ne renvoie rien

**Dépendances :** T9

**Fichiers :** `README.md`, `CLAUDE.md`, `CHANGELOG.md`, `composer.json` (description)

**Taille :** S

---

### Checkpoint final
- [ ] Les 7 critères de succès de `SPEC.md` sont remplis
- [ ] `composer test`, `composer analyse` et `vendor/bin/pint --test` passent
- [ ] Revue humaine avant merge dans `develop`
