# Changelog

All notable changes to `laravel-openapi` will be documented in this file.

## v1.0.0 - 2026-11-12

First version.

- Generate OpenAPI 3.0.x and 3.1.x documents, valid against the official meta-schemas, from the routes whose action
  carries `#[Operation]`, on classic controllers and invokable actions (ADR).
- Attributes `#[Operation]`, `#[Parameter]`, `#[Parameters]`, `#[RequestBody]` and `#[Response]` on actions;
  `#[Schema]` and `#[Property]` on DTOs, API Resources and models, with type deduction (scalars, arrays, `#[Schema]`
  classes, backed enums, nullable).
- Immutable fluent object model, serialized for the document's version, and definition classes (`Schema`,
  `Parameters`, `RequestBody`, `Response`) inlined or emitted in `components` with `Reusable`.
- Optional route segments duplicated into one path per variant.
- Several documents per application, each with its prefixes, security schemes and global security.
- `openapi:generate` (JSON or YAML), `make:openapi-{schema,parameters,request-body,response}` with publishable stubs,
  and an optional route serving the generated file.
