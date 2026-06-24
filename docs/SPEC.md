# Erasmus Plus — Spec

Multi-tenant multi-panel SaaS for Erasmus+ programme operations. Organizations are tenants, projects are sub-tenants inside an organization, finance and mobility data are project-scoped.

## Architecture

Filament panels are the unit of context. Each tenant level is its own panel; switching context means switching panel, and the whole sidebar swaps with it.

- **`admin`** — `admin.{host}`, no tenancy, platform staff only (provisioned out-of-band).
- **`app`** — tenant = `Organization`. Sidebar shows org-wide concerns: members, organization roles, projects, settings.
- **`project`** — tenant = `Project`, nested inside `app`. Sidebar shows project-only concerns: finance, mobility, project members, project roles.

URLs: `app.{host}/{org}/...` for the `app` panel, `app.{host}/{org}/{project}/...` for `project`. Each panel takes its own slug as its Filament tenant; project panel resolves the org from the leading path segment. The same `User` reaches every panel through distinct `canAccessPanel()` gates. Further tenant levels (e.g. workspaces under projects) are added by stacking the same pattern: new panel, new Filament tenant, parent context resolved from the URL/host.

Tenant-owned models use `BelongsToOrganization`. Project-owned models additionally use `BelongsToProject`. Both apply global scopes that read the active panel's tenant and auto-assign foreign keys on create.

## Data model

`Organization` (`id`, `name`, `slug`) — has many users (pivot), projects, organization roles. `Project` (`id`, `organization_id`, `name`, `slug`) — belongs to organization, has many users (pivot), has many project roles. `User` implements `FilamentUser` + `HasTenants`, belongs to many organizations and many projects. Pivots: `organization_user`, `project_user`. Audit log captures `organization_id`, `user_id`, `project_id?`, `action`, `target_type`, `target_id`, `created_at`.

Every record belongs to exactly one organization, directly or indirectly. Project access requires explicit project membership. Totals are derived, never stored. Deleting a user must not remove historical project data.

## Access control

Roles and permissions are scoped separately at two levels: **organization-scoped** and **project-scoped**. Spatie permissions are partitioned by scope (team_id = `organization_id` for org roles, team_id = `project_id` for project roles); a user can hold different roles in different organizations and different roles in different projects within the same organization. A permission check always names its scope.

The **Organization Admin** is the role granted to the user who registers an organization. It is not immutable — it can be transferred to another member, but at least one Organization Admin must exist per organization at all times. Custom organization and project roles are defined by users with the appropriate permissions.

Strict tenant isolation, least privilege, and auditability of sensitive actions apply throughout.

## Functional scope

**Organization & users** — register organization (registering user becomes Organization Admin, transferable), invite/remove members, manage organization roles and permissions, create projects, assign project membership and project roles.

**Finance** — project-scoped entries with amount, operation (add/subtract), project reference. Totals derived from entries. Excel export. Access gated by project membership and a project-scoped finance permission.

**Mobility** — project-scoped participant records. Participant role catalog defined at organization level with system defaults; customizable per organization. Excel export. Access gated by project membership and a project-scoped mobility permission.
