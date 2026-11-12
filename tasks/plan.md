# Plan d'implémentation : biweb/laravel-openapi v1

Référence : [`SPEC.md`](../SPEC.md). Tâches détaillées : [`tasks/todo.md`](todo.md).

## Vue d'ensemble

On construit en tranches verticales : chaque tâche produit un document OpenAPI plus riche, validé contre les méta-schémas officiels dès la première tâche. Les risques techniques (validateurs, classes invocables ADR) sont traités en premier.

**Révision du 2026-11-12** : on réintroduit le **modèle objet fluent** et les **classes de définition** de l'ancien
paquet (décision 8 de la spec). Le modèle objet passe **avant** le reste des schémas (ex-T5 et ex-T6) : T1 à T4
produisent encore des tableaux, et sont migrées une seule fois (T6), avec leurs tests existants comme filet de sécurité.

## Graphe de dépendances

```
T1 squelette du document + validateurs de test            ✅
 └─ T2 #[Operation] / #[Parameter] sur contrôleur classique ✅
     ├─ T3 classes invocables (ADR)                         ✅
     └─ T4 #[Schema] / #[Property] : scalaires, nullable    ✅
         └─ T5 modèle objet OpenAPI + sérialisation 3.0/3.1     ✅
             └─ T6 migration de generate() et SchemaFactory vers les objets ✅
                 ├─ T7 paramètres optionnels {x?} → duplication des paths ✅
                 └─ T8 $ref imbriqués, tableaux, enums, collisions (registre Components)
                     └─ T9 classes de définition + Reusable
                         └─ T10 #[RequestBody] / #[Response] / #[Parameters]
                             └─ T11 multi-documents + sécurité
                                 └─ T12 commande openapi:generate (JSON/YAML)
                                     ├─ T13 commandes make:openapi-*
                                     └─ T14 route qui sert le fichier
                                         └─ T15 README + CLAUDE.md
```

T7 et T8 sont indépendantes une fois T6 terminée. Il en va de même pour T13 et T14 après T12.

## Décisions d'architecture

- **Validateurs** : deux dépendances de dev. Aucune librairie PHP connue ne couvre à la fois le draft-04 et le 2020-12.
  Opis ne descend qu'au draft-06.
  - `opis/json-schema` (draft 2020-12) → méta-schéma OpenAPI **3.1**.
  - `justinrainbow/json-schema` (draft-04) → méta-schéma OpenAPI **3.0**.
  - Les deux sont branchés derrière un seul helper Pest, `expect($doc)->toBeValidOpenapi()`.
- **Modèle objet (remplace « pas de modèle objet »)** : il est porté de `SlayerMonkey\LaravelOpenAPI\OpenAPI\Objects`
  vers `BIWEB\LaravelOpenapi\OpenApi\Objects`, avec les corrections suivantes :
    - les nullables sont explicites (`?string $x = null`, pas de dépréciation en PHP 8.4) ;
    - toutes les propriétés sont initialisées à `null` ;
    - les bornes numériques sont typées `int|float|null` ;
    - un helper privé `with(prop, value)` remplace le copier-coller `clone` / `assign` / `return` ;
    - les getters sont explicites, à la place du `__get` magique.
- **Sérialisation selon la version** : `BaseObject::toArray(string $version)` descend récursivement dans l'arbre. Les
  seuls écarts 3.0/3.1 convertis sont centralisés dans `Schema` : `nullable` et `exclusiveMinimum`/`exclusiveMaximum`.
  Un champ réservé à la 3.1 (`SINCE_3_1` : `Info::summary`, `License::identifier`) lève une erreur dans un document
  3.0, au lieu d'être émis ou ignoré. Les maps vides deviennent des `stdClass`, pour sortir en `{}`.
- **Registre `Components`** : c'est un objet par génération, jamais un état global. Il résout un `class-string` (DTO
  `#[Schema]` via `SchemaFactory`, ou classe de définition via `build()`) en objet, nomme le composant, détecte les
  collisions et ne garde que ce qui est référencé. Les `ref(Foo::class)` posés dans un `build()` sont collectés en
  parcourant l'arbre jusqu'à un point fixe, ce qui gère les cycles.
- **Classes de définition** : ce sont les `Bases\*` de l'ancien paquet, renommées `Definitions\*`.
    - `Response` ne porte plus `$code` ni `$description` en propriétés : le code HTTP est lu sur l'objet construit.
    - `Parameters` devient réutilisable (l'ancien paquet ne le permettait pas).
    - `Callback`, `Extension` et `SecurityScheme` ne sont pas portées (hors périmètre).
- **Lecture des routes** : `Route::getRoutes()`, puis filtre par préfixe d'URI. Les routes closure sont ignorées. Pour chaque méthode HTTP, `HEAD` est exclu.
- **Paramètres optionnels** : un `PathExpander` produit *n* + 1 variantes de l'URI. L'`operationId` explicite reçoit le
  suffixe `By{Param}` par segment optionnel présent.
- **ADR** : Laravel résout l'action en `Class@__invoke`. On lit les attributs sur la méthode, et aussi sur la classe quand la méthode est `__invoke`. S'ils sont présents des deux côtés, c'est une erreur.
- **Erreurs** : une seule exception, `InvalidOpenapiDefinition`, dont le message nomme la classe ou la méthode fautive.
  La commande l'attrape et renvoie `FAILURE`.
- **`operationId`** : jamais généré automatiquement. Si une route a plusieurs méthodes HTTP et un `operationId`
  explicite, c'est une erreur.
- **Route servie** : un fichier de routes boucle sur les documents qui ont `route.enabled`, avec un contrôleur
  invocable. Elle est compatible avec `route:cache`.
- **Placeholder** : `LaravelOpenapiCommand` est supprimée et remplacée par `GenerateCommand`.

## Liste des tâches

### Phase 1 : fondations et risques
- [x] T1 : squelette du document + validateurs de test
- [x] T2 : `#[Operation]` / `#[Parameter]` sur contrôleur classique
- [x] T3 : classes invocables (ADR)

### Checkpoint A
- [ ] `composer test` et `composer analyse` passent
- [ ] Revue humaine : forme des attributs d'opération (signature figée après la release)

### Phase 2 : modèle objet et schémas
- [x] T4 : `#[Schema]` / `#[Property]` : scalaires, nullable 3.0/3.1, required
- [x] T5 : modèle objet OpenAPI + sérialisation 3.0/3.1
- [x] T6 : migration de `generate()` et `SchemaFactory` vers les objets
- [x] T7 : paramètres optionnels → duplication des paths
- [x] T8 : `$ref` imbriqués, tableaux, enums, collisions (registre `Components`)
- [x] T9 : classes de définition + `Reusable`
- [x] T10 : `#[RequestBody]` / `#[Response]` / `#[Parameters]`

### Checkpoint B

- [ ] Les fixtures complètes (attributs **et** classes de définition) génèrent des documents 3.0 et 3.1 valides, égaux à
  `expected/*.json`
- [ ] Revue humaine des fichiers `expected/` et de l'API du builder

### Phase 3 : multi-documents et sorties

- [ ] T11 : multi-documents + sécurité
- [ ] T12 : commande `openapi:generate` (JSON/YAML)
- [ ] T13 : commandes `make:openapi-schema|parameters|request-body|response`
- [ ] T14 : route qui sert le fichier
- [ ] T15 : README + CLAUDE.md

### Checkpoint final

- [ ] Les 9 critères de succès de la spec sont remplis
- [ ] `composer test`, `composer analyse` et `vendor/bin/pint --test` passent
- [ ] Prêt pour la revue

## Risques et mitigations

| Risque                                                          | Impact | Mitigation                                                                                                                                           |
|-----------------------------------------------------------------|--------|------------------------------------------------------------------------------------------------------------------------------------------------------|
| Un validateur charge mal son méta-schéma                        | Élevé  | Traité en T1, avec un test négatif.                                                                                                                  |
| Le portage du modèle objet (une trentaine de classes) gonfle T5 | Élevé  | T5 est découpée en deux lots (objets du cœur, puis objets secondaires). Chaque objet arrive avec son test. On ne porte rien qu'aucun test ne couvre. |
| La migration T6 change la sortie de T1 à T4                     | Moyen  | Les tests existants restent inchangés, seul l'appel passe à `->toArray($version)`. Toute différence de sortie est un bug.                            |
| Cycles de `$ref` entre classes de définition                    | Moyen  | Le registre mémorise le nom avant d'appeler `build()`. Un test de cycle `User` → `Team` → `User` est prévu en T8.                                    |
| `[]` au lieu de `{}` en JSON ou en YAML                         | Moyen  | La sérialisation émet `stdClass` (T5) ; `Yaml::DUMP_OBJECT_AS_MAP` en T12.                                                                           |
| Contrainte `symfony/yaml` incompatible en `prefer-lowest`       | Moyen  | `^7.2 \|\| ^8.0`, vérifié en T12.                                                                                                                    |
| Chemins Windows ou Linux dans `output`                          | Faible | Répertoires temporaires et helpers Laravel dans les tests.                                                                                           |

## Après la v1 (hors périmètre, à ne pas commencer)

Ces pistes viennent de la revue de l'ancien paquet. Chacune demandera une révision de la spec, puisque la v1 interdit
toute inférence hors du tableau de déduction.

- Inférence du `RequestBody` depuis les `rules()` d'un `FormRequest`.
- Route model binding : `User $user` donne le type de la clé (`integer` ou `uuid`).
- Contraintes `whereNumber` / `whereUuid` / `whereIn` → schéma du paramètre de chemin.
- Lecture des attributs sur un Responder ADR, via le type de retour de l'Action.
- Resolver pour `lorisleiva/laravel-actions` (`asController`).
- Interface de documentation (Scalar, Swagger UI ou Redoc).
- Classes de définition `SecurityScheme`, `Callback`, webhooks (3.1).
