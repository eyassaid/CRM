# CRM

Laravel 13 JSON API (`backend/`) + Vue 3 SPA (`frontend/`), run with Docker Compose.
See `README.md` for first-run setup and service URLs.

## Commands

Run from the repo root. Backend commands run inside the `app` container.

    docker compose up -d --build
    docker compose exec app php artisan test                          # full suite (in-memory SQLite)
    docker compose exec app php artisan test --filter=ClientApiTest
    docker compose exec app php artisan migrate
    docker compose exec app php artisan db:seed
    docker compose exec app vendor/bin/pint path/to/File.php          # format a specific file
    docker compose exec frontend npm run build                        # check the SPA compiles
    docker compose restart queue                                      # after backend changes: the worker caches code

- `db:seed` creates an admin (see `database/seeders/DatabaseSeeder.php`, password `password`) for logging into the SPA.
- Queued work (`ProjectAssigned` / `ProjectCompleted` emails) runs in the `queue` service (`QUEUE_CONNECTION=database`).
- Pint (default Laravel preset, no `pint.json`): run it **only on files you modified**. Many untouched files are not
  Pint-clean; do not reformat them. This is not a git repo, so `pint --dirty` does not work: pass explicit paths.
- No static analysis, linter for JS, or CI is configured.
- PHP: Docker image is 8.4, `composer.json` requires `^8.3`. Do not use 8.5-only features.

## Architecture

```
Team ─< Client ─< Project ─< Invoice     Section ─< Team >─< User (members, pivot team_user)
Client.created_by → User (who brought it) Team.leader_id → User
Project >─< User (assignees, pivot project_user)
```

- **Visibility / ownership**: admins see everything; a team's leader sees all of the team's clients and projects.
  A member **owns** the clients they brought (`created_by`, never reassigned): they manage those clients and all of
  their projects. Project assignees are **helpers** the leader adds: they see the client and edit only the projects
  they help on (`ClientPolicy`, `ProjectPolicy`, mirrored by the `Client::visibleTo()` / `Project::visibleTo()` scopes
  used in index queries). Only owners, leaders and admins create projects; members delete a project only to withdraw
  a pending request on a client they own. Project status is `App\Enums\ProjectStatus`
  (`not_started`, `working_on_it`, `cancelled`, `done`).
- **Project requests**: a project the client's owner creates (not the team's leader or an admin) is a request:
  `approved_at` is null. The leader or an admin approves it with
  `PUT /projects/{id}` `{ "approved": true }` (`ClientPolicy::assignProjects`). Pending projects get no invoices (409).
- **Completing a project**: projects cannot be created as `done`. Moving one to `done` (`PUT /projects/{id}`) requires
  `items: [{ name, cost }, ...]` (min 1, `ProjectRequest::completesProject()`); it creates an `Invoice` with those
  `items` (and `created_by`), then sends
  `App\Notifications\ProjectCompleted` (in-app right away, email queued) to every admin and the client's team leader,
  except the person who completed it. Users read theirs via `GET /notifications` (`?unread=1`) and
  `PUT /notifications/{id}` `{ "read": bool }`.
- **Invoices** are stored as data only (`items`, `created_by`); no file is kept. `POST /projects/{id}/invoices` and
  `PUT /invoices/{id}` take `items` (rules in `InvoiceRequest::itemRules()`). The PDF is rendered on demand by
  `Invoice::pdf()` from `resources/views/invoices/pdf.blade.php` (barryvdh/laravel-dompdf): downloaded via
  `GET /invoices/{id}/pdf` (`InvoicePdfController`, SPA `invoiceStore.downloadPdf()` as a Blob) and attached to the
  `ProjectCompleted` email when it is sent.
- **Project assignees (helpers)**: only the leader or members of the client's team (`ProjectRequest` `user_ids`); set by admins
  and that team's leader (`ClientPolicy::assignProjects`). `Team::unassignFormerMembers()` removes people who leave the
  team; call it whenever a team's people or a client's team change. People newly added (on store or update) get the
  `App\Notifications\ProjectAssigned` email (queued), except the person who added them.

- **Auth**: Laravel Passport (`auth:api` guard, Bearer tokens via `createToken()`). Sanctum is installed but unused;
  do not use it. Roles are `App\Enums\UserRole` (`admin` | `team_leader` | `employee`, cast on `users.role`, mirrored in
  `src/constants/userRole.js`); use `User::isAdmin()` / `User::isTeamLeader()`. Only team leaders can be a team's
  `leader_id`; a user who leads a team cannot lose the role.
- **Routes**: `backend/routes/api.php` only. Use `Route::apiResource`; child resources are nested and `->shallow()`
  (`clients.projects`, `projects.invoices`, `sections.teams`). Protected routes go in the `auth:api` group;
  admin-only routes also use the `admin` middleware (`App\Http\Middleware\AdminMiddleware`).
- **Accounts**: there is no public registration. Admins and team leaders create users through `POST /users`
  (`UserPolicy`): admins choose `employee` or `team_leader`; leaders add employees to a team they lead.
- **Reports** (`TeamReportController`, SPA `reports/ReportListView` / `reports/TeamReportView`): `GET /reports/teams` (admin) lists teams
  with `clients_count`, `projects_count` and `projects_by_status`; `GET /reports/teams/{team}` (admin or that team's
  leader, `TeamPolicy::viewReport`) adds how many of the team's clients each member brought, and the clients with
  their projects. Leaders reach their teams' reports through `GET /me/teams`.
- **Sections and teams** are admin-only (`admin` middleware), except `GET /teams/{team}`, which the team's people can
  view (`TeamPolicy::view`). Teams can be deleted only when they have no members (409 otherwise).
- **Search**: `GET /clients` and `GET /users` take `?search=` (`ClientIndexRequest` / `UserIndexRequest`; name or phone,
  name or email), applied on top of the visibility filter; the list views use `BaseSearchInput`.
- **Navigation**: the SPA hides links the user cannot open, using `isAdmin` / `isTeamLeader` / `canManageUsers` from
  the `auth` store (based on `user.role`). Keep these in sync with the policies.
- **Controllers**: `App\Http\Controllers\Api\{Model}Controller`, resource methods only (`index/store/show/update/destroy`),
  route-model binding, Eloquent used directly. For shallow-nested `store`, take the parent from the route
  (`$project->invoices()->create(...)`), not from the request body.
- **Services**: only `App\Services\FileUploadService` (upload/delete on the `public` disk). There is no
  repository/service layer otherwise; do not add one.
- **Exceptions**: `bootstrap/app.php` renders JSON for `api/*`.
- **Response cache** (`cache.api` → `App\Http\Middleware\CacheApiResponses`, on both protected route groups, after
  `auth:api` / `admin`): 200 JSON GET responses are cached per user + URL for 10 min; any successful non-GET request
  changes a version key, which invalidates all of them. Data changed outside the API needs `php artisan cache:clear`.

## Backend conventions

- **Validation**: always a Form Request, never `$request->validate()` in controllers. Place new requests in
  `App\Http\Requests\{PluralDomain}\` (e.g. `Clients\`, `Teams\`). Use one `{Model}Request` for store + update unless
  the rules differ substantially (as with `ClientStoreRequest` / `ClientUpdateRequest`). On update, use
  `Rule::unique(...)->ignore($this->route('model'))`. Use `$request->validated()` and never pass `$request->all()`.
- **Models**: `$fillable` array, `casts()` method, typed relation return types (`HasMany`, `BelongsTo`, ...),
  `HasFactory` with a factory that produces a valid record. Add the inverse relation only when it is used.
- **Resources**: `App\Http\Resources\{Model}Resource`. Use `whenLoaded()` / `whenCounted()` for relations and never
  lazy-load inside a resource; eager-load in the controller (`load()` / `with()` / `withCount()`).
  Return stored files as `Storage::disk('public')->url($path)`, and `null` when the path is empty.
- **Files**: store on the `public` disk under `{plural}/{id}/...` (e.g. `clients/{id}`, `projects/{id}/images`)
  using `FileUploadService`. On update, upload the new file, save the model, and delete the old file only after
  the save succeeds; if the save fails, delete the newly uploaded file.
- **Error handling**: let exceptions reach Laravel's handler. Do **not** wrap actions in `try/catch` that return
  `$e->getMessage()` or use `$e->getCode()` as an HTTP status. Use `try/catch` only to undo side effects
  (e.g. remove an uploaded file) and then rethrow. Use `abort(403|404|409, 'message')` for expected failures.
  Use `DB::transaction()` when one action writes several tables (e.g. a team and its `team_user` members).
- **Authorization**: `auth:api` + `admin` middleware for whole endpoints; per-record rules live in Policies
  (`App\Policies\{Model}Policy`, auto-discovered) and are checked with `Gate::authorize()` in the controller.
  Projects use `ProjectPolicy`, invoices authorize through their project, and project index/store through the
  client (`ClientPolicy`). Index endpoints must filter
  their query the same way (see `ClientController::index`). Form Request `authorize()` returns `true`; rules that
  depend on the user (e.g. which `team_id` is allowed) go in the Form Request rules.

## API response format (project-wide standard)

Every endpoint follows this; when you touch an endpoint that does not, bring it in line and update the frontend and
tests in the same change.

| Case | Response |
|---|---|
| Single resource | `(new XResource($model))->response()` (add `->setStatusCode(201)` on create) → `{ "data": {...} }` |
| List | `XResource::collection($query->paginate())->response()` → `{ "data": [...], "links": {...}, "meta": {...} }` |
| Delete | `response()->noContent()` → 204, empty body |
| Action with no resource (e.g. logout) | `response()->json(['message' => '...'])` |
| Login | `{ "data": { "user": {...}, "access_token": "...", "token_type": "Bearer" } }` |
| Validation error | Laravel default 422 → `{ "message": "...", "errors": { "field": ["..."] } }` |
| Other errors | Laravel handler → `{ "message": "..." }` with the proper status (401, 403, 404, 500) |

Do not return `response()->json($resource)` or raw models/arrays from controllers.

## Frontend conventions (`frontend/src`)

Vue 3 `<script setup>` SFCs (script, then template, then `<style scoped>` only if needed), Composition API, Vite,
Pinia, Vue Router 4, axios. Always import through the `@` alias (→ `src/`). 4-space indent, single quotes, semicolons.

```
src/
├── api/http.js            the ONE axios instance + interceptors      api/log.js  ResponseLog panel entries
├── api/services/          one {resource}Service.js per backend resource (authService, clientService, ...)
├── stores/                Pinia setup stores, one per domain (auth, clients, projects, invoices, notifications,
│                          sections, teams, users, reports)
├── router/routes.js       route records only                          router/index.js  createRouter + guards
├── views/                 route pages: DashboardView, NotFoundView, {domain}/{Model}ListView, {Model}DetailView
├── components/common/     reusable UI, `Base` prefix (BaseErrorBanner, BasePagination, BaseSearchInput) + ResponseLog
├── components/{domain}/   feature components (projects/ProjectRoute, teams/TeamLine)
├── composables/           useXxx.js (useForm, useSidebar: collapsed rail, kept in localStorage)
├── layouts/               AppLayout, GuestLayout
├── utils/                 pure helpers (formData.js)
└── constants/             values mirrored from backend enums
```

- **Data flow is one-directional**: view → store action → service → `@/api/http` → API. Views, components and stores
  never import `axios` or `http`; services never touch store state; never call `fetch` or create another axios instance.
- **HTTP** (`@/api/http`): base URL from `VITE_API_URL` (`/api`; falls back to `/api` because `.env` is not copied into
  the Docker build), 15 s timeout, Bearer token from `localStorage`, every response recorded for the `ResponseLog`
  panel. Every failed request rejects with `{ status, message, errors }` (`errors` = Laravel's 422 field errors).
  401 handling lives only in the interceptor: it calls `auth.clearSession()` and pushes `login` with `?redirect=`.
  Vite (dev) / nginx (prod) proxy `/api` to Laravel, so there is no CORS setup.
- **Services** (`src/api/services/`): one default-exported object per resource; methods return the response body
  (`r.data`, i.e. `{ data, links?, meta? }`), never the axios response. Shallow-nested resources take the parent id
  (`projectService.listForClient(clientId, params)`, `create(clientId, fields)`). Services that carry files build the
  multipart body with `toFormData(fields, 'PUT')` from `@/utils/formData` (sent as POST with `_method=PUT`);
  `withoutEmpty(fields)` drops blank optional fields. JSON updates of a resource that also has a multipart form use
  `update()` (e.g. `projectService.update(id, { user_ids: [] })`, `updateForm()` for the form).
- **Stores** (`src/stores/`): setup syntax only, `defineStore('{plural}')`, hook `use{Model}Store`. State `items`,
  `current`, `meta`, `loading`, `error` (+ `$reset()`). Fetch actions (`fetchAll`, `fetchOne`, `fetchFor{Parent}`) wrap
  the request in try / catch / finally, set `loading` and `error`, and rethrow. Mutations (`create`, `update`, `remove`,
  `approve`, ...) update `items` / `current` and rethrow; they do not touch `loading` / `error`. The `auth` store holds
  the user, token, cached `teams` (`fetchTeams({ refresh })`, `forgetTeams()` after team changes) and the role getters.
- **Using stores in views**: destructure state/getters with `storeToRefs` (`const { items: clients, meta, loading,
  error } = storeToRefs(clientStore)`), call actions on the store. Never assign store state from a component; add an
  action. Fetches in `onMounted` end with `.catch(() => {})` because their failure is shown from the store's `error`.
- **Forms and row actions** (`useForm` from `@/composables/useForm`): `const form = useForm({ name: '' })`, bind
  `v-model="form.fields.name"`, call `await form.submit((fields) => store.create(fields))`, which resolves `true`/`false`
  and exposes `processing`, `message` and 422 `errors` (template: `form.errors.value.name[0]` with `.field-error`,
  `<BaseErrorBanner :message="form.message.value" />`). `form.reset(values)` to edit a record or clear the form. Row
  actions (delete, approve, mark read) use a field-less `const action = useForm()`; show
  `error?.message || action.message.value` above the list.
- **States**: every view that fetches data handles loading, error (`BaseErrorBanner`) and empty states explicitly.
- **Constants**: values mirrored from backend enums live in `src/constants/` (e.g. `projectStatus.js` ↔ `ProjectStatus`);
  update both sides together.
- **Routing**: records in `src/router/routes.js`, guards in `router/index.js` (guards return a location, no `next()`;
  `useAuthStore()` is called inside the guard). Every route has a kebab-case `name` (`client-detail`, `team-report`);
  navigate by name, never by path. Views are lazy-loaded; `meta: { requiresAuth: true }` (on a parent it covers its
  children) or `{ guestOnly: true }`, plus `title` (→ `document.title`). Routes with params use `props: true` and the
  view declares `defineProps({ id: { type: String, required: true } })` instead of `useRoute()`. A catch-all
  `not-found` route comes last. Admin-only pages are not guarded client-side; the backend's 403 is shown.
- **Views and components**: PascalCase, multi-word names; views end in `View`, wrapped in `AppLayout` (or
  `GuestLayout` for auth pages). Props down, events up (never mutate props; `defineModel()` for `v-model` on form
  components). Keep templates logic-light (use `computed`), always `:key` on `v-for`, never `v-if` with `v-for` on one
  element. Reuse `BaseErrorBanner` and `BasePagination`.
- **Styling**: Tailwind utilities plus the component classes in `src/assets/main.css` (`card`, `label`, `input`,
  `field-error`, `btn`, `btn-secondary`, `link`, `link-danger`, `table`). Reuse these instead of adding new ones.
- **Checks**: `npm run build` (in the `frontend` container) is the only check; there is no JS linter.
- **New frontend resource**: service → store → views (`views/{domain}/`) → feature components → route records
  (name + `meta`) → `npm run build`.

## When to create what

Do not generate the full set of Laravel components for every table. Create only what the feature needs:

- **Migration + Model + Factory**: a new persisted entity. Pivot tables (e.g. `team_user`) get no model.
- **API Controller + route**: only if the entity is exposed over HTTP.
- **Form Request**: every endpoint that accepts input.
- **API Resource**: every model returned by the API.
- **Policy**: only for per-record authorization (see `ClientPolicy`, `TeamPolicy`).
- **Service class**: only when the same logic is needed in two or more places.
- **Feature test**: every new or changed endpoint.
- **Frontend view/route**: only when the feature has UI.

## Testing

- PHPUnit 12 Feature tests: `backend/tests/Feature/{Domain}/{Domain}ApiTest.php`, extending `Tests\TestCase`.
  There are no Unit tests yet.
- `use RefreshDatabase;` authenticate with `Passport::actingAs(User::factory()->create())`; admins via
  `User::factory()->create(['role' => 'admin'])`.
- Method names `test_snake_case_description(): void`. Use `getJson/postJson/putJson/deleteJson` and
  `assertOk/assertCreated/assertNoContent/assertUnprocessable/assertForbidden/assertUnauthorized`,
  `assertJsonPath('data.…')`, `assertDatabaseHas/Missing`.
- Uploads: `Storage::fake('public')` + `UploadedFile::fake()->create(...)`.
- Endpoints that issue tokens need a personal access client:
  `app(ClientRepository::class)->createPersonalAccessGrantClient('Test')` in `setUp()` (see `UserApiTest`).
- Cover the success path, validation (422), and access rules (401 / 403) for each endpoint.
- Run the affected test file while working and the full suite before finishing.
- **Never cache config in development** (`php artisan config:cache`). A cached config ignores `phpunit.xml`, so the
  tests run `RefreshDatabase` against the real MySQL database and wipe it. If `backend/bootstrap/cache/config.php`
  exists, run `php artisan config:clear` before testing.

## Rules for changes

- Before creating anything, search for existing code that does it (Form Requests, Resources, `FileUploadService`,
  factories, relations, services, stores, `useForm`, `@/utils/formData`, components) and reuse or extend it.
- Follow the conventions in this file, not the one-off variants found in older code.
- Keep changes scoped to the task. Do not reformat, rename, or refactor unrelated code. If you find a bug outside the
  task, report it instead of fixing it.
- No new abstractions (base controllers, traits, interfaces, repositories, DTOs, helpers) unless the task requires them.
- Do not duplicate validation rules, resource mappings, or frontend helpers; extract only when actually shared.
- When an API response changes, update the frontend callers and the tests in the same change.
- Migrations: the project is pre-production, so editing an existing migration is allowed. Afterwards run
  `php artisan migrate:fresh --seed` (this wipes the local database) and keep factories/seeders in sync.
- Do not add Composer/npm packages or change config, Docker, or `.env` files without asking.

## Known gaps (verify before relying on them)

Not yet compliant with the response standard / error-handling rules above:

- `ClientController` / `ProjectController` store and replace **images** inline with `Storage` (deleting the old file
  before the save) instead of using `FileUploadService`.
- `AuthController::login` returns a bare 422 `{ "message" }` for bad credentials (no `errors` key).
- `GET /me/teams` returns an unpaginated list (used for the client form's team picker).

Functional gaps:

- Team `store` ignores the `{section}` route parameter (the SPA sends `section_id` in the body).
- The SPA's leader and members pickers use `GET /users`, which is paginated (first 15 users).
- Deleting a client that has projects, or a project that has invoices, fails on the foreign key.
- `LoginRequest::authenticate()` / rate limiting is unused; login is not throttled.
- Backlog (2FA, social login, `/api/profile`) lives in `backend/notes.md`.
