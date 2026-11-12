# Plan d'implémentation : biweb/laravel-openapi v1

Référence : [`SPEC.md`](../SPEC.md). Tâches détaillées : [`tasks/todo.md`](todo.md).

## Vue d'ensemble

On construit en tranches verticales : chaque tâche produit un document OpenAPI plus riche, validé contre les méta-schémas officiels dès la première tâche. Les risques techniques (validateurs, classes invocables ADR) sont traités en premier.

## Graphe de dépendances

```
T1 squelette du document + validateurs de test
 └─ T2 #[Operation] / #[Parameter] sur contrôleur classique
     ├─ T3 classes invocables (ADR)
     └─ T4 #[Schema] / #[Property] : scalaires, nullable, required
         └─ T5 $ref imbriqués, tableaux, enums, collisions
             └─ T6 #[RequestBody] / #[Response]
                 └─ T7 multi-documents + sécurité
                     └─ T8 commande openapi:generate (JSON/YAML)
                         └─ T9 route qui sert le fichier
                             └─ T10 README + CLAUDE.md
```

T3 et T4 sont indépendantes une fois T2 terminée.

## Décisions d'architecture

- **Validateurs (question ouverte de la spec, tranchée ici)** : deux dépendances de dev. Aucune librairie PHP connue ne couvre à la fois le draft-04 et le 2020-12. Opis ne descend qu'au draft-06.
  - `opis/json-schema` (draft 2020-12) → méta-schéma OpenAPI **3.1**.
  - `justinrainbow/json-schema` (draft-04) → méta-schéma OpenAPI **3.0**.
  - Les deux sont branchés derrière un seul helper Pest, `expect($doc)->toBeValidOpenapi()`. La T1 vérifie dès le départ qu'ils chargent bien les méta-schémas, pour échouer tôt si ce n'est pas le cas.
- **Pas de modèle objet** : le document est un tableau PHP. Les maps qui peuvent être vides (`paths`, `components.schemas`, `properties`…) sont émises en `stdClass` ou converties en objet à la sérialisation, sinon `json_encode([])` produit `[]` là où OpenAPI attend `{}`.
- **Lecture des routes** : `Route::getRoutes()`, puis filtre par préfixe d'URI. Les routes closure sont ignorées. Pour chaque méthode HTTP, `HEAD` est exclu.
- **ADR** : Laravel résout l'action en `Class@__invoke`. On lit les attributs sur la méthode, et aussi sur la classe quand la méthode est `__invoke`. S'ils sont présents des deux côtés, c'est une erreur.
- **Erreurs** : une seule exception, `InvalidOpenapiDefinition` (`src/Exceptions/`), dont le message nomme la classe ou la méthode fautive. La commande l'attrape et renvoie `FAILURE`.
- **`operationId`** : jamais généré automatiquement (le champ est optionnel). Si une route a plusieurs méthodes HTTP et un `operationId` explicite, c'est une erreur, car l'id serait dupliqué.
- **Route servie** : un fichier de routes boucle sur les documents qui ont `route.enabled`. Chaque route pointe vers un contrôleur invocable et porte le nom du document en `defaults`, ce qui la rend compatible avec `route:cache`.
- **Placeholder** : `LaravelOpenapiCommand` est supprimée et remplacée par `GenerateCommand`.

## Liste des tâches

### Phase 1 : fondations et risques
- [x] T1 : squelette du document + validateurs de test
- [x] T2 : `#[Operation]` / `#[Parameter]` sur contrôleur classique
- [x] T3 : classes invocables (ADR)

### Checkpoint A
- [ ] `composer test` et `composer analyse` passent
- [ ] Revue humaine : forme des attributs d'opération (signature figée après la release)

### Phase 2 : schémas
- [ ] T4 : `#[Schema]` / `#[Property]` : scalaires, nullable 3.0/3.1, required
- [ ] T5 : `$ref` imbriqués, tableaux, enums, collisions de noms
- [ ] T6 : `#[RequestBody]` / `#[Response]`

### Checkpoint B
- [ ] Les fixtures complètes génèrent des documents 3.0 et 3.1 valides, égaux à `expected/*.json`
- [ ] Revue humaine des fichiers `expected/`

### Phase 3 : multi-documents et sorties
- [ ] T7 : multi-documents + sécurité
- [ ] T8 : commande `openapi:generate` (JSON/YAML)
- [ ] T9 : route qui sert le fichier
- [ ] T10 : README + CLAUDE.md

### Checkpoint final
- [ ] Les 7 critères de succès de la spec sont remplis
- [ ] `composer test`, `composer analyse` et `vendor/bin/pint --test` passent
- [ ] Prêt pour la revue

## Risques et mitigations

| Risque | Impact | Mitigation |
|---|---|---|
| Un validateur charge mal son méta-schéma (`$dynamicRef` en 3.1, `id` en draft-04) | Élevé | T1 traitée en premier, avec un test négatif (document invalide rejeté) pour prouver que la validation est réelle. |
| Le méta-schéma 3.1 valide les Schema Objects de façon laxiste (dialecte libre) | Moyen | La comparaison avec `expected/*.json` reste l'assertion principale, la validation ne fait que s'y ajouter. |
| `[]` au lieu de `{}` en JSON ou en YAML | Moyen | Test dédié en T1 (document sans route) ; `Yaml::DUMP_OBJECT_AS_MAP` en T8. |
| Contrainte `symfony/yaml` incompatible avec Laravel 12 en `prefer-lowest` | Moyen | `^7.2 \|\| ^8.0`, aligné sur la version de Symfony requise par Laravel 12 ; vérifié en local en `--prefer-lowest` lors de la T8. |
| Chemins Windows ou Linux dans `output` (CI sur les deux OS) | Faible | Les tests utilisent des répertoires temporaires et des chemins construits avec `DIRECTORY_SEPARATOR` ou des helpers Laravel. |

## Questions ouvertes

Aucune bloquante.
