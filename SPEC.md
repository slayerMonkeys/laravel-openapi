# Spec : biweb/laravel-openapi v1

## Objectif

Générer un document **OpenAPI 3.1 ou 3.0** à partir d'une application Laravel, en se basant uniquement sur des
déclarations explicites : des **attributs PHP** posés sur les contrôleurs et les classes de données, et des **classes de
définition** (schémas, paramètres, request bodies, réponses) qui construisent leurs objets OpenAPI avec un **builder
objet fluent**. Le paquet est public (Packagist) et gère **plusieurs documents** par application (par ex. `public` et
`internal`, ou `v1` et `v2`).

Utilisateur cible : un développeur Laravel qui veut une spec OpenAPI fiable et versionnable, sans inférence « magique » ni annotations dans les docblocks. Le paquet doit fonctionner aussi bien avec des contrôleurs classiques qu'avec des **classes invocables** (pattern ADR, Action-Domain-Responder : une classe Action par route).

### User stories

1. En tant que dev, j'annote une action de contrôleur avec `#[Operation]`, `#[Response]`, etc., puis je lance `php artisan openapi:generate` et j'obtiens un `openapi.json` (ou `.yaml`) valide.
1. bis. En tant que dev ADR, j'annote directement ma classe Action invocable (`Route::get('/users/{id}', ShowUserAction::class)`), sur la classe ou sur `__invoke`, et l'opération est documentée de la même façon.
2. En tant que dev, j'annote un DTO ou une Resource avec `#[Schema]` et ses propriétés avec `#[Property]` ; il apparaît dans `components/schemas` et il est référencé par `$ref`.
2. bis. En tant que dev, je génère une classe avec `php artisan make:openapi-response UserCreated` (ou `-schema`,
   `-parameters`, `-request-body`). Dans sa méthode `build()`, je construis l'objet avec le builder
   (`Response::created()->description(…)->content(MediaType::json()->schema(Schema::ref(UserData::class)))`), puis je la
   référence depuis un attribut : `#[Response(UserCreatedResponse::class)]`. Si la classe implémente `Reusable`, elle va
   dans `components` et l'opération la référence par `$ref`. Sinon, elle est insérée en ligne.
3. En tant que dev, je déclare dans la config plusieurs documents, chacun avec sa version OpenAPI, ses infos, ses serveurs, ses préfixes de routes et ses `securitySchemes`.
4. En tant que consommateur de l'API, je récupère via une route HTTP le document produit par la commande, si le dev a activé cette route.

### Hors périmètre v1

- Inférence depuis les FormRequest, les règles de validation, les types de retour ou les API Resources.
- UI de documentation (Swagger UI, Scalar, Redoc).
- Validation des requêtes et réponses à l'exécution, et assertions de test.
- Attributs dédiés à `oneOf`/`anyOf`/`allOf`, `discriminator`, links et `examples` multiples. Ces constructions restent
  disponibles **via le builder objet**, dans une classe de définition.
- Webhooks, callbacks, extensions `x-*` par attribut, et classes de définition pour les `securitySchemes` (ils restent
  déclarés dans la config).
- Génération du document à la volée dans la route HTTP : elle sert uniquement le fichier produit par la commande.
- Routes définies par closure : elles ne peuvent pas porter d'attributs et sont ignorées.
- Attributs posés sur une classe Responder ADR : seule la classe Action (celle de la route) est lue. Pour partager une
  réponse entre plusieurs Actions, on utilise une classe `Response` réutilisable.
- Découverte des composants par scan de répertoires (le `locations` de l'ancien paquet) : seuls les composants
  **référencés** par une opération ou par un autre composant sont émis.

## Tech Stack

- PHP ^8.3 ; Laravel 12/13 (`illuminate/contracts`) ; `spatie/laravel-package-tools` ^1.16.
- **Nouvelle dépendance runtime** : `symfony/yaml` (^7.0 || ^8.0) pour la sortie YAML.
- Dev : Pest 4 (+ arch, laravel), Testbench 10/11, Larastan 3 (niveau 5), Pint.
- **Nouvelle dépendance dev** : un validateur JSON Schema, choisi en phase Plan. Il doit gérer le draft-04 (méta-schéma officiel OpenAPI 3.0) **et** le draft 2020-12 (méta-schéma 3.1). Les méta-schémas officiels sont copiés dans `tests/Fixtures/schemas/` : les tests ne font aucun appel réseau.

## Concepts et API publique

### Attributs (`BIWEB\LaravelOpenapi\Attributes\`)

| Attribut                                                                                         | Cible                                               | Rôle                                                                                                                                                                                                                                                                                         |
|--------------------------------------------------------------------------------------------------|-----------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Operation(summary, description, operationId, tags, deprecated, security, documents)`            | méthode ou classe invocable                         | **Opt-in** : seules les routes dont l'action porte cet attribut sont documentées. `documents` (liste de noms, vide = tous) restreint l'opération à certains documents.                                                                                                                       |
| `Parameter(name, in, type, format, required, description)`                                       | méthode ou classe invocable, répétable              | Paramètre `query` / `header` / `path` / `cookie`.                                                                                                                                                                                                                                            |
| `RequestBody(body, contentType = 'application/json', required = true, description)`              | méthode ou classe invocable                         | `body` : une classe `RequestBody` de définition, ou un **schéma** (classe `#[Schema]` ou classe `Schema` de définition).                                                                                                                                                                     |
| `Response(status, description, schema = null, contentType = 'application/json', isList = false)` | méthode ou classe invocable, répétable              | `status` : un code HTTP, ou une classe `Response` de définition (les autres arguments sont alors interdits). `schema` : une classe `#[Schema]` ou une classe `Schema` de définition. `isList: true` donne `{type: array, items: $ref}`. Une `JsonResource` est enveloppée (voir ci-dessous). |
| `Parameters(...classes)`                                                                         | méthode ou classe invocable, répétable              | Une ou plusieurs classes `Parameters` de définition. Leurs paramètres s'ajoutent à ceux de `#[Parameter]`, avec la même règle d'unicité `in` + `name`.                                                                                                                                       |
| `Schema(name = null, description = null)`                                                        | classe                                              | Composant `components/schemas/{name ?? short class name}`.                                                                                                                                                                                                                                   |
| `Property(name, type, format, description, nullable, required, example, items)`                  | propriété (y compris promue), ou classe (répétable) | Sur une propriété, les arguments surchargent ce qui est déduit du type PHP natif et `name` est ignoré. Sur la classe, `name` et `type` sont obligatoires (voir ci-dessous).                                                                                                                  |

**Classes invocables (ADR)** : Laravel résout `Route::get(…, ShowUserAction::class)` en `ShowUserAction@__invoke` (`RouteAction::makeInvokable`). Pour une action `__invoke`, les attributs d'opération peuvent être posés **sur la classe ou sur `__invoke`, mais pas sur les deux**. Une opération répartie sur les deux lève une erreur qui nomme la classe. Sur un contrôleur classique (action autre que `__invoke`), les attributs d'opération posés sur la classe sont ignorés.

**Paramètres de chemin** : les segments `{id}` de l'URI sont ajoutés automatiquement (`in: path`, `required: true`, `type: string`), sauf si un `#[Parameter]` du même nom les redéfinit.

**Paramètres optionnels** : OpenAPI ne connaît pas de segment optionnel, et Laravel n'en autorise qu'en fin d'URI. Une
route avec *n* segments `{x?}` est donc **dupliquée en *n* + 1 paths** : `/posts/{post}/comments/{comment?}` donne
`/posts/{post}/comments` et `/posts/{post}/comments/{comment}`. Chaque variante ne contient que ses propres paramètres
de chemin, qui sont tous required. Un `operationId` explicite reçoit un suffixe par variante (`listComments`, puis
`listCommentsByComment`), pour rester unique.

## Builder objet et classes de définition

**Modèle objet** (`BIWEB\LaravelOpenapi\OpenApi\Objects\`) : le document est un arbre d'objets **immuables et fluents**,
porté de l'ancien paquet et corrigé. Chaque setter renvoie un clone. Les objets couvrent `OpenApi`, `Info`, `Contact`,
`License`, `Server`, `ServerVariable`, `Tag`, `ExternalDocs`, `PathItem`, `Operation`, `Parameter`, `RequestBody`,
`MediaType`, `Encoding`, `Response`, `Header`, `Link`, `Example`, `Schema`, `AllOf`, `AnyOf`, `OneOf`, `Not`,
`Discriminator`, `Xml`, `Components`, `SecurityScheme`, `SecurityRequirement` et `OAuthFlow`. Les énumérations
(`SchemaType`, `SchemaFormat`, `ParameterIn`, `Style`, `HttpMethod`, `SecuritySchemeType`, `Flow`) sont des backed
enums.

- Les objets décrivent une **intention**, indépendante de la version. La sérialisation `toArray(string $version)`
  produit la forme 3.0 ou 3.1 (par exemple `->nullable()` donne `nullable: true` ou `type: [T, "null"]`).
- Les maps vides sont émises en `{}`, les clés nulles ou vides sont omises, les extensions `x-*` sont gérées par
  `->x(key, value)`.
- Un champ requis manquant lève `InvalidOpenapiDefinition`, avec le chemin de l'objet fautif (par exemple
  `paths./users.get.responses`).
- `Schema::ref()`, `Response::ref()`, etc. acceptent un `class-string` de définition ou de `#[Schema]`, résolu en
  `#/components/...` au moment de la génération, ou un pointeur `#/...` brut.

**Classes de définition** (`BIWEB\LaravelOpenapi\Definitions\`) : ce sont des classes abstraites que l'application
étend, résolues par le container (injection possible dans le constructeur).

| Classe        | `build()` renvoie                                                                                            | Composant si `Reusable`                              |
|---------------|--------------------------------------------------------------------------------------------------------------|------------------------------------------------------|
| `Schema`      | `Objects\Schema` ou une composition `AllOf`/`AnyOf`/`OneOf`/`Not`                                            | `components.schemas`                                 |
| `Parameters`  | `list<Objects\Parameter>`                                                                                    | `components.parameters` (un composant par paramètre) |
| `RequestBody` | `Objects\RequestBody`                                                                                        | `components.requestBodies`                           |
| `Response`    | `Objects\Response` (le code HTTP est porté par l'objet : `Response::ok()`, `::created()`, `->statusCode(…)`) | `components.responses`                               |

- **`Reusable`** (interface marqueur) : la classe va dans `components` et l'opération la référence par `$ref`. Sans
  `Reusable`, l'objet est inséré en ligne.
- **Nom du composant** : l'`objectId` passé au builder (`Schema::object('User')`), sinon le nom court de la classe. La
  règle de collision est la même que pour `#[Schema]`.
- **Mélange** : dans `build()`, `Schema::ref(UserData::class)` peut viser un DTO `#[Schema]`, et une
  `#[Property(items: UserSchema::class)]` peut viser une classe `Schema` de définition. Les deux mondes produisent le
  même modèle objet.
- **Commandes `make:openapi-schema`, `-parameters`, `-request-body`, `-response`** : elles génèrent la classe dans
  `app/OpenApi/{Schemas,Parameters,RequestBodies,Responses}` à partir de stubs publiables. L'option `--reusable` ajoute
  `implements Reusable`.

**Déduction des types d'une propriété `#[Schema]`** (seule déduction faite par le paquet, et toujours surchargeable) :

| Type PHP | Schéma |
|---|---|
| `int` / `float` / `string` / `bool` | `integer` / `number` / `string` / `boolean` |
| `array` | `array` ; `items` est exigé via `#[Property(items: …)]`, sinon une erreur est levée |
| classe portant `#[Schema]` | `$ref` |
| `BackedEnum` | type du backing + `enum` des valeurs |
| `?T` | nullable (voir ci-dessous) |

Une propriété est **required** si elle n'est pas nullable et n'a pas de valeur par défaut, sauf `#[Property(required: …)]` explicite.

**Propriétés déclarées sur la classe (API Resources, modèles)** : les champs d'une `JsonResource` (sortie de `toArray()`) ou d'un modèle Eloquent ne sont pas des propriétés PHP. On les déclare par des `#[Property(name: …, type: …)]` répétés sur la classe. Dès qu'une classe porte au moins un `#[Property]` de classe, **ses propriétés PHP ne sont pas lues**. Rien n'est déduit : `name` et `type` manquants lèvent une erreur qui nomme la classe, `nullable` vaut `false` par défaut, et la propriété est required si elle n'est pas nullable, sauf `required:` explicite. `toArray()` n'est jamais lu.

**Enveloppe des Resources** : une réponse dont le `schema` est une sous-classe de `JsonResource` suit son enveloppe `$wrap` (`data` par défaut) : `{type: object, properties: {data: $ref}, required: [data]}`, ou `data: {type: array, items: $ref}` avec `isList: true`. Si `$wrap` vaut `null`, le schéma n'est pas enveloppé. Les `links`/`meta` de pagination ne sont pas documentés en v1.

**Différences 3.0 / 3.1** gérées par le paquet :

- nullable : `nullable: true` en 3.0, `type: ["string", "null"]` en 3.1 ;
- champ `openapi` : toute valeur `3.0.x` ou `3.1.x` est acceptée et recopiée telle quelle ; toute autre valeur lève une erreur.

**Noms de composants** : si deux classes `#[Schema]` différentes donnent le même nom (par ex. `App\A\User` et `App\B\User`), une erreur est levée et nomme les deux classes. On la résout avec `#[Schema(name: …)]`.

### Config (`config/openapi.php`)

```php
return [
    'default' => 'default',

    'documents' => [
        'default' => [
            'openapi' => '3.1.0',                       // toute version 3.0.x ou 3.1.x
            'info' => [
                'title' => 'API',                        // pas d'env() : refusé par Larastan hors config de l'app
                'version' => '1.0.0',
                'description' => null,
            ],
            'servers' => [],                            // ex. [['url' => 'https://api.example.com']]
            'routes' => ['prefixes' => ['api']],        // filtre sur l'URI des routes
            'security_schemes' => [
                // 'bearer' => ['type' => 'http', 'scheme' => 'bearer'],
            ],
            'security' => [],                           // exigence globale, ex. [['bearer' => []]]
            'output' => base_path('openapi.json'),      // l'extension .yaml/.yml impose le YAML
            'route' => [
                'enabled' => false,
                'uri' => 'openapi/default.json',
                'middleware' => [],
            ],
        ],
    ],
];
```

### Points d'entrée

- **Commande** : `php artisan openapi:generate {document?} {--all} {--output=} {--format=json|yaml}`. Elle remplace la commande placeholder `laravel-openapi`. Le format vient de `--format`, sinon de l'extension du fichier de sortie. Code de sortie non nul en cas d'erreur (document inconnu, `#[Schema]` manquant, `items` manquant…).
- **Route** : pour chaque document dont `route.enabled` vaut `true`, `GET {route.uri}` sert **tel quel le fichier `output`** produit par la commande. Le `Content-Type` dépend de l'extension : `application/json`, ou `application/yaml` pour `.yaml`/`.yml`. Si le fichier n'existe pas, la route répond 404. Elle est servie par un contrôleur invocable, compatible avec `route:cache`. Un fichier écrit ailleurs avec `--output` n'est pas servi.
- **Façade** : `LaravelOpenapi::generate(?string $document = null): array` renvoie le document sous forme de tableau. C'est la commande qui l'utilise.

## Commandes

```bash
composer install
composer test                                   # vendor/bin/pest
vendor/bin/pest tests/Feature/GenerateCommandTest.php
vendor/bin/pest --filter "nullable 3.0"
composer test-coverage                          # vendor/bin/pest --coverage
composer analyse                                # phpstan niveau 5 sur src/ et config/
composer format                                 # pint
```

## Structure du projet

```
config/openapi.php                  → config multi-documents
routes/openapi.php                  → routes servant les fichiers des documents (chargées via hasRoute)
src/Attributes/*.php                → Operation, Parameter, Parameters, RequestBody, Response, Schema, Property
src/OpenApi/Objects/*.php           → modèle objet fluent et immuable (builder)
src/OpenApi/Enums/*.php             → SchemaType, SchemaFormat, ParameterIn, HttpMethod…
src/Definitions/*.php               → Schema, Parameters, RequestBody, Response (abstraites) + Reusable
src/LaravelOpenapi.php              → generate() : routes → arbre OpenApi → toArray($version)
src/SchemaFactory.php               → classe #[Schema] → Objects\Schema
src/Components.php                  → registre des composants référencés (résolution des class-string, collisions)
src/Http/OpenapiController.php      → contrôleur invocable qui sert le fichier `output`
src/Commands/GenerateCommand.php    → openapi:generate (remplace LaravelOpenapiCommand)
src/Commands/Make*Command.php       → make:openapi-schema|parameters|request-body|response
stubs/*.stub                        → stubs publiables des classes de définition
src/Facades/LaravelOpenapi.php      → façade existante
tests/Fixtures/                     → contrôleurs, DTO et enums annotés servant de fixtures
tests/Fixtures/expected/*.json      → documents attendus (3.0 et 3.1)
tests/Fixtures/schemas/*.json       → méta-schémas officiels OpenAPI 3.0 et 3.1
tests/Unit/                         → SchemaFactory, déduction des types
tests/Feature/                      → génération complète, commande, route, multi-documents
```

Pas de `workbench/` : les fixtures de test suffisent.

## Style de code

Suivre Pint (preset Laravel), PSR-4, `declare` absent comme dans le reste du squelette. Attributs en `final readonly class` avec constructeur promu :

```php
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Response
{
    /** @param class-string|null $schema */
    public function __construct(
        public int $status,
        public string $description = '',
        public ?string $schema = null,
        public string $contentType = 'application/json',
        public bool $isList = false,
    ) {}
}
```

Usage côté application :

```php
#[Operation(summary: 'Afficher un utilisateur', tags: ['users'], security: [['bearer' => []]])]
#[Response(200, 'Utilisateur', schema: UserData::class)]
#[Response(404, 'Introuvable')]
public function show(int $id): JsonResponse { /* … */ }

// ADR : Route::get('/users/{id}', ShowUserAction::class)
#[Operation(summary: 'Afficher un utilisateur', tags: ['users'])]
#[Response(200, 'Utilisateur', schema: UserData::class)]
final class ShowUserAction
{
    public function __construct(private ShowUserResponder $responder) {}

    public function __invoke(int $id): JsonResponse { /* … */ }
}

#[Schema]
final class UserData
{
    public function __construct(
        public int $id,
        #[Property(format: 'email')] public string $email,
        public ?string $nickname = null,
        public Role $role = Role::Member,
    ) {}
}

#[Schema]
#[Property('id', type: 'integer')]
#[Property('email', type: 'string', format: 'email')]
#[Property('nickname', type: 'string', nullable: true)]
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array { /* … */ }
}
```

Classe de définition équivalente, réutilisable :

```php
final class UserCreatedResponse extends Response implements Reusable
{
    public function build(): Objects\Response
    {
        return Objects\Response::created('UserCreated')
            ->description('Utilisateur créé')
            ->content(MediaType::json()->schema(Schema::ref(UserData::class)));
    }
}

#[Operation(summary: 'Créer un utilisateur', tags: ['users'])]
#[RequestBody(CreateUserBody::class)]
#[Response(UserCreatedResponse::class)]   // → responses.201 = {$ref: '#/components/responses/UserCreated'}
#[Response(422, 'Erreur de validation')]
final class CreateUserAction { /* … */ }
```

Le document est construit sous forme d'**arbre d'objets OpenAPI**, attributs et classes de définition confondus, puis
sérialisé selon la version du document. Les clés vides ou nulles sont omises de la sortie.

## Stratégie de test

- **Pest + Testbench** ; `TestCase` enregistre le provider et définit les routes de fixtures dans `defineRoutes()`.
- **Feature (le cœur)** : une app de fixtures couvrant toutes les fonctionnalités est générée en 3.0 et en 3.1. Chaque document est **validé contre le méta-schéma officiel** de sa version, puis comparé à `tests/Fixtures/expected/*.json` (`toEqual` sur les tableaux décodés). Les fixtures incluent un contrôleur classique et des Actions invocables, annotées sur la classe pour l'une et sur `__invoke` pour l'autre.
- **Unit, modèle objet** : un test par objet (construction, immutabilité, `toArray` en 3.0 et en 3.1, champ requis
  manquant).
- **Unit, classes de définition** : inline ou `$ref` selon `Reusable`, nom du composant, mélange `#[Schema]` et
  définition, collision.
- **Unit** : table de déduction des types (y compris enum et nullable dans les deux versions), règle `required`, propriétés déclarées sur la classe (`JsonResource`), erreurs (`items` absent, classe sans `#[Schema]`, collision de noms de composants, attributs à la fois sur la classe et sur `__invoke`, version `openapi` invalide).
- **Commande** : écriture JSON et YAML (le YAML relu par `Yaml::parse` doit être égal au JSON), `--all`, document inconnu → code de sortie non nul.
- **Route** : désactivée → 404 ; activée sans fichier → 404 ; activée avec un fichier → 200 avec son contenu et le bon `Content-Type` (JSON et YAML) ; middleware appliqué.
- **Arch** (existant) : pas de `dd`/`dump`/`ray`.
- Couverture visée : ≥ 90 % sur `src/`.

## Limites

- **Toujours** : lancer `composer test` et `composer analyse` avant chaque commit ; mettre à jour les fixtures `expected/` en même temps que le comportement ; garder la compatibilité Laravel 12 et 13, PHP 8.3 à 8.5 (matrice CI existante).
- **Demander d'abord** : toute nouvelle dépendance autre que `symfony/yaml` et le validateur JSON Schema de dev ; un changement de la forme de la config ou des signatures d'attributs après la première release ; la modification des workflows CI.
- **Jamais** : activer la route JSON par défaut ; inférer quoi que ce soit hors du tableau de déduction ci-dessus ; supprimer ou désactiver un test qui échoue sans accord ; modifier `vendor/` ; relancer `configure.php`.

## Critères de succès

1. Les fixtures génèrent un document en 3.0.x et en 3.1.x, **valide contre le méta-schéma officiel** de sa version et identique aux fichiers `expected/`.
1. bis. Une Action invocable annotée sur la classe et une autre annotée sur `__invoke` produisent la même opération qu'un contrôleur classique annoté de la même façon.
2. `php artisan openapi:generate` écrit un JSON ou un YAML équivalent ; `--all` génère tous les documents configurés.
3. Une route sans `#[Operation]` n'apparaît dans aucun document ; une route hors des `prefixes` d'un document n'apparaît pas dans ce document.
4. Une opération `documents: ['internal']` n'apparaît que dans le document `internal`.
5. Les `securitySchemes` de la config sont présents dans `components`, et le `security` d'une `#[Operation]` est repris tel quel.
6. Les erreurs de configuration ou d'annotation produisent un message qui nomme la classe ou la méthode fautive.
7. `composer test`, `composer analyse` et `composer format --test` passent sur toute la matrice CI.
8. Une réponse, un request body, un schéma ou des paramètres déclarés par une classe de définition produisent la même
   sortie que leur équivalent par attributs. Avec `Reusable`, ils sont dans `components` et référencés par `$ref`, sans
   `Reusable` ils sont insérés en ligne.
9. Une route `{x?}` produit les deux paths attendus, valides et avec des `operationId` uniques.

## Décisions (revue du 2026-11-11)

1. La validité du document est prouvée par un validateur JSON Schema de dev, appliqué aux méta-schémas officiels.
2. La route sert le fichier produit par la commande, sans générer à la volée.
3. Toute version `3.0.x` / `3.1.x` est acceptée.
4. Les versions supportées sont celles de `composer.json` : PHP ^8.3, Laravel 12/13, Testbench 10/11.
5. Une collision de noms de composants lève une erreur.
6. Les attributs d'opération sont compatibles avec les classes invocables (ADR).
7. (2026-11-12) Les API Resources et les modèles sont documentés par des `#[Property(name:, type:)]` répétés sur la classe, sans lecture de `toArray()`. Les réponses `JsonResource` suivent l'enveloppe `$wrap`.
8. (2026-11-12) **Retour du modèle objet** : la décision « tableaux PHP sans modèle objet » est abandonnée. On garde le
   builder objet fluent de l'ancien paquet (corrigé : nullables explicites, `int|float`, pas de `__get` magique) et ses
   classes de définition `Schema` / `Parameters` / `RequestBody` / `Response` avec `build()`. Les attributs et les
   classes de définition produisent le même arbre d'objets.
9. (2026-11-12) Les segments optionnels `{x?}` sont **dupliqués** en plusieurs paths, au lieu d'être rendus
   obligatoires.
10. (2026-11-12) Les composants sont émis à la demande (référencés uniquement), sans scan de répertoires.

## Questions ouvertes

1. **Choix du validateur** (phase Plan) : identifier une librairie maintenue qui gère à la fois le draft-04 (OpenAPI 3.0) et le draft 2020-12 (OpenAPI 3.1). S'il n'en existe pas, en utiliser deux, ou valider la 3.0 avec un validateur OpenAPI dédié.
