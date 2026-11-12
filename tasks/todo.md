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

## Phase 2 : modèle objet et schémas

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

### T5 : modèle objet OpenAPI + sérialisation 3.0/3.1

**Description :** on porte le builder fluent et immuable de l'ancien paquet
(`SlayerMonkey\LaravelOpenAPI\OpenAPI\{Objects,Enums}`) vers `src/OpenApi/{Objects,Enums}`, avec les corrections de
`plan.md` : nullables explicites, propriétés initialisées, `int|float|null`, helper `with()`, pas de `__get`.
`toArray(string $version)` est récursif. `Schema` centralise les écarts 3.0/3.1 (`nullable`, `exclusiveMinimum`/
`exclusiveMaximum`). Les maps vides sortent en `{}`. Un champ requis manquant, ou un champ réservé à la 3.1 utilisé en
3.0, lève `InvalidOpenapiDefinition` avec le chemin de l'objet. `ref()` accepte un pointeur `#/…` ou un `class-string`, qui est stocké pour une résolution ultérieure
(T8).

Le travail est découpé en deux lots, un commit par lot :

- **T5a (cœur)** : `BaseObject`, `OpenApi`, `Info`, `Server`, `Tag`, `PathItem`, `Operation`, `Parameter`,
  `RequestBody`, `MediaType`, `Response`, `Schema`, `Components`, `SecurityRequirement`, plus les enums.
- **T5b (secondaires)** : `Contact`, `License`, `ServerVariable`, `ExternalDocs`, `Encoding`, `Header`, `Link`,
  `Example`, `AllOf`/`AnyOf`/`OneOf`/`Not`, `Discriminator`, `Xml`, `SecurityScheme`, `OAuthFlow`.

**Critères d'acceptation :**

- [x] Chaque objet a un test : construction, immutabilité (le setter ne modifie pas l'original), `toArray` en 3.0 et en
  3.1.
- [x] `Schema::string()->nullable()` donne `{type: string, nullable: true}` en 3.0 et `{type: [string, null]}` en 3.1.
- [x] Un document complet construit à la main avec le builder passe `toBeValidOpenapi()` en 3.0 et en 3.1.
- [x] Aucune signature implicitement nullable (vérifiée par PHPStan, avec les règles de dépréciation).

**Vérification :** `vendor/bin/pest tests/Unit/OpenApi`

**Dépendances :** T1

**Fichiers :** `src/OpenApi/Objects/*.php`, `src/OpenApi/Enums/*.php`, `src/OpenApi/Contracts/SchemaContract.php`,
`tests/Unit/OpenApi/*Test.php`

**Taille :** L (en deux lots M)

---

### T6 : migration de `generate()` et `SchemaFactory` vers les objets

**Description :** `LaravelOpenapi::generate()` construit un arbre `OpenApi` et renvoie `->toArray($version)`.
`SchemaFactory` renvoie des `Objects\Schema` au lieu de tableaux. C'est une **migration à sortie identique**.

**Critères d'acceptation :**

- [x] `DocumentTest`, `OperationTest`, `InvokableActionTest` et `SchemaFactoryTest` passent sans changer les tableaux
  attendus. Seuls les appels changent (`->toArray($version)`).
- [x] Il ne reste aucune construction de tableau OpenAPI à la main dans `src/`, hors de `toArray()`.

**Vérification :** `composer test`

**Dépendances :** T4, T5

**Fichiers :** `src/LaravelOpenapi.php`, `src/SchemaFactory.php`, `tests/Unit/SchemaFactoryTest.php`

**Taille :** M

---

### T7 : paramètres optionnels → duplication des paths

**Description :** le remplacement actuel `{id?}` → `{id}` est remplacé par un `PathExpander`. Une URI avec *n* segments
optionnels donne *n* + 1 paths. Chaque variante ne porte que ses paramètres de chemin, et l'`operationId` explicite
reçoit le suffixe `By{Param}`.

**Critères d'acceptation :**

- [x] `/posts/{post}/comments/{comment?}` donne `/posts/{post}/comments` (paramètre `post`) et
  `/posts/{post}/comments/{comment}` (paramètres `post` et `comment`).
- [x] `operationId: 'listComments'` donne `listComments` et `listCommentsByComment`.
- [x] Le document reste valide (pas de paramètre de chemin absent de l'URI, ni d'`operationId` dupliqué).

**Vérification :** `vendor/bin/pest tests/Feature/OptionalParameterTest.php`

**Dépendances :** T6

**Fichiers :** `src/PathExpander.php`, `src/LaravelOpenapi.php`, `tests/Fixtures/Http/CommentController.php`,
`tests/Feature/OptionalParameterTest.php`

**Taille :** S

---

### T8 : `$ref` imbriqués, tableaux, enums, collisions (registre `Components`)

**Description :** un registre `Components` (une instance par génération) résout les `class-string` en composants. Une
propriété typée par une classe `#[Schema]` produit un `$ref`, et la classe référencée est enregistrée une seule fois,
même en cas de cycle. Les `ref(Foo::class)` stockés dans l'arbre sont résolus en parcourant l'arbre jusqu'au point fixe.
Un `array` exige `#[Property(items: …)]`. Un `BackedEnum` donne son type de backing et ses valeurs en `enum`. Deux
classes qui donnent le même nom de composant lèvent une erreur.

**Critères d'acceptation :**
- [x] Une référence circulaire (`User` → `Team` → `User`) se termine, avec deux composants et des `$ref`.
- [x] Un `array` sans `items` lève une erreur qui nomme la classe et la propriété.
- [x] Une collision `A\User` / `B\User` lève une erreur qui nomme les deux classes, et `#[Schema(name:)]` la résout.
- [x] Un composant qui n'est référencé nulle part n'est pas émis.

**Vérification :** `vendor/bin/pest tests/Unit/SchemaFactoryTest.php tests/Unit/ComponentsTest.php`

**Dépendances :** T6

**Fichiers :** `src/Components.php`, `src/SchemaFactory.php`, `tests/Fixtures/Data/*.php`,
`tests/Unit/ComponentsTest.php`

**Taille :** M

---

### T9 : classes de définition + `Reusable`

**Description :** on crée `Definitions\Schema`, `Definitions\Parameters`, `Definitions\RequestBody`,
`Definitions\Response` (abstraites, avec `build()`) et l'interface `Definitions\Reusable`. Elles sont résolues par le
container. Le registre `Components` les accepte au même titre que les DTO `#[Schema]` : avec `Reusable`, elles vont dans
`components.{schemas|parameters|requestBodies|responses}` avec un `$ref`, sans `Reusable` elles sont insérées en ligne.
Le nom du composant vient de l'`objectId`, sinon du nom court de la classe.

**Critères d'acceptation :**

- [x] Une classe `Schema` réutilisable donne un `$ref`, et une classe non réutilisable donne le schéma en ligne.
- [x] Le mélange fonctionne dans les deux sens : `Schema::ref(UserData::class)` dans un `build()`, et
  `#[Property(items: UserSchema::class)]` dans un DTO.
- [x] Une classe `Response` réutilisable est émise sous son code HTTP, lu sur l'objet construit.
- [x] Une classe `Parameters` réutilisable donne un composant par paramètre.
- [x] Une dépendance injectée dans le constructeur d'une définition est résolue par le container.

**Vérification :** `vendor/bin/pest tests/Unit/DefinitionsTest.php`

**Dépendances :** T8

**Fichiers :** `src/Definitions/*.php`, `src/Components.php`,
`tests/Fixtures/OpenApi/{Schemas,Parameters,RequestBodies,Responses}/*.php`, `tests/Unit/DefinitionsTest.php`

**Taille :** M

---

### T10 : `#[RequestBody]` / `#[Response]` / `#[Parameters]`

**Description :** on branche les attributs sur l'opération.

- `RequestBody(body)` accepte une classe `RequestBody` de définition, ou un schéma (DTO `#[Schema]` ou classe `Schema`).
- `Response(status)` accepte un code HTTP (avec `schema` et `isList`) ou une classe `Response` de définition. Dans ce
  second cas, les autres arguments sont interdits.
- `Parameters(...classes)` fusionne ses paramètres avec ceux de `#[Parameter]` (unicité `in` + `name`).

On crée le jeu de fixtures complet, qui mêle attributs et classes de définition, puis `expected/document-3.0.json` et
`expected/document-3.1.json`.

**Critères d'acceptation :**
- [x] `#[Response(200, schema: UserData::class, isList: true)]` donne `content.application/json.schema = {type: array, items: {$ref}}`.
- [x] `#[Response(204, 'Supprimé')]` est émis sans `content`.
- [x] `#[Response(UserCreatedResponse::class)]` (réutilisable) donne
  `responses.201 = {$ref: '#/components/responses/UserCreated'}`, et `#[Response(UserCreatedResponse::class, 'x')]` lève
  une erreur.
- [x] Une réponse `JsonResource` est enveloppée selon `$wrap`.
- [x] La même opération décrite par attributs et par classes de définition donne une sortie équivalente.
- [x] Les fixtures complètes sont valides et égales aux fichiers `expected/`, en 3.0 et en 3.1.

**Vérification :** `vendor/bin/pest tests/Feature/FullDocumentTest.php`

**Dépendances :** T3, T7, T9

**Fichiers :** `src/Attributes/{RequestBody,Response,Parameters}.php`, `src/LaravelOpenapi.php`,
`tests/Fixtures/expected/*.json`, `tests/Feature/FullDocumentTest.php`

**Taille :** M

---

### Checkpoint B
- [x] Les fixtures complètes génèrent des documents 3.0 et 3.1 valides et égaux à `expected/*.json`
- [ ] Revue humaine des fichiers `expected/` et de l'API publique du builder (figée après la release)

---

## Phase 3 : multi-documents et sorties

### T11 : multi-documents + sécurité

**Description :** `Operation::documents` restreint une opération à certains documents, et chaque document filtre sur ses
propres préfixes. Les `security_schemes` de la config (tableaux) sont convertis en `Objects\SecurityScheme` dans
`components.securitySchemes`, et le `security` global est émis à la racine. Chaque document a son propre registre
`Components` : un composant n'apparaît que dans les documents qui le référencent.

**Critères d'acceptation :**

- [x] Deux documents (`public` sur `api`, `internal` sur `internal`) ne contiennent chacun que leurs routes **et leurs
  composants**.
- [x] Une opération avec `documents: ['internal']` est absente de `public`.
- [x] Les `securitySchemes` et le `security` global sont présents, et le document reste valide.

**Vérification :** `vendor/bin/pest tests/Feature/MultiDocumentTest.php`

**Dépendances :** T10

**Fichiers :** `src/LaravelOpenapi.php`, `tests/Feature/MultiDocumentTest.php`, `tests/Fixtures/Http/InternalController.php`

**Taille :** S

---

### T12 : commande `openapi:generate`

**Description :** la commande `openapi:generate {document?} {--all} {--output=} {--format=}` remplace le placeholder. Le format vient de `--format`, sinon de l'extension. Le YAML passe par `symfony/yaml` (avec `DUMP_OBJECT_AS_MAP`). Les répertoires manquants sont créés. Les erreurs `InvalidOpenapiDefinition` donnent un message et le code `FAILURE`.

**Critères d'acceptation :**
- [x] Le fichier JSON écrit est égal à `generate()`, et le YAML relu par `Yaml::parse` est égal au JSON.
- [x] `--all` écrit un fichier par document.
- [x] Un document inconnu ou une annotation invalide donne un code de sortie non nul et un message qui nomme la cause.

**Vérification :** `vendor/bin/pest tests/Feature/GenerateCommandTest.php`, et `composer update --prefer-lowest` reste vert

**Dépendances :** T11

**Fichiers :** `composer.json`, `src/Commands/GenerateCommand.php` (et suppression de `LaravelOpenapiCommand.php`), `src/LaravelOpenapiServiceProvider.php`, `tests/Feature/GenerateCommandTest.php`

**Taille :** M

---

### T13 : commandes `make:openapi-*`

**Description :** `make:openapi-schema`, `make:openapi-parameters`, `make:openapi-request-body` et
`make:openapi-response` étendent `GeneratorCommand`. Elles écrivent dans
`app/OpenApi/{Schemas,Parameters,RequestBodies,Responses}`. L'option `--reusable` ajoute `implements Reusable`. Les
stubs sont publiables (tag `openapi-stubs`). La génération depuis un modèle (`--model` de l'ancien paquet, qui reposait
sur Doctrine) n'est **pas** reprise.

**Critères d'acceptation :**

- [x] Chaque commande génère une classe qui compile, dans le bon namespace, et que `Components` accepte.
- [x] `--reusable` produit une classe émise en `$ref`.
- [x] Un stub publié dans `stubs/` de l'application remplace celui du paquet.

**Vérification :** `vendor/bin/pest tests/Feature/MakeCommandsTest.php`

**Dépendances :** T9, T12

**Fichiers :** `src/Commands/Make*Command.php`, `stubs/*.stub`, `src/LaravelOpenapiServiceProvider.php`,
`tests/Feature/MakeCommandsTest.php`

**Taille :** S

---

### T14 : route qui sert le fichier

**Description :** `routes/openapi.php` enregistre `GET {route.uri}` pour chaque document qui a `route.enabled`, avec son
middleware et le nom du document en `defaults`. `OpenapiController` (invocable) sert le fichier `output` avec le
`Content-Type` correspondant à son extension, ou répond 404 si le fichier n'existe pas.

**Critères d'acceptation :**
- [x] Route désactivée → 404 ; route activée sans fichier → 404.
- [x] Fichier `.json` → 200 avec `application/json` ; fichier `.yaml` → 200 avec `application/yaml`.
- [x] Le middleware configuré est appliqué, et `route:cache` fonctionne.

**Vérification :** `vendor/bin/pest tests/Feature/RouteTest.php`

**Dépendances :** T12

**Fichiers :** `routes/openapi.php`, `src/Http/OpenapiController.php`, `src/LaravelOpenapiServiceProvider.php`, `tests/Feature/RouteTest.php`

**Taille :** M

---

### T15 : README + CLAUDE.md

**Description :** le README boilerplate est remplacé par la doc d'usage : installation, config, attributs (contrôleur
classique et Action ADR), **builder objet et classes de définition**, commandes `make:openapi-*`, `openapi:generate`,
route, et rappel de relancer `openapi:generate` au déploiement. La section « Project state » de `CLAUDE.md` est mise à
jour, et une entrée est ajoutée dans `CHANGELOG.md`.

**Critères d'acceptation :**

- [ ] Les exemples du README correspondent aux fixtures testées.
- [ ] `CLAUDE.md` décrit l'architecture réelle (modèle objet, `Components`, `SchemaFactory`, définitions, commandes,
  route).
- [ ] Il ne reste plus aucun placeholder du skeleton.

**Vérification :** relecture humaine ; `grep -r "This is my package\|My command"` ne renvoie rien

**Dépendances :** T13, T14

**Fichiers :** `README.md`, `CLAUDE.md`, `CHANGELOG.md`, `composer.json` (description)

**Taille :** S

---

### Checkpoint final

- [ ] Les 9 critères de succès de `SPEC.md` sont remplis
- [ ] `composer test`, `composer analyse` et `vendor/bin/pint --test` passent
- [ ] Revue humaine avant merge dans `develop`
