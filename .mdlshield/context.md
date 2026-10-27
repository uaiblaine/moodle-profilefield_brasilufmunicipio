# Review context for profilefield_brasilufmunicipio

`profilefield_brasilufmunicipio` is a user profile field type: the user picks a Brazilian state
(UF) and then a municipality. The municipality list is held in the plugin's own table, filled
from the public IBGE localities API by tasks. It has **one table**,
`profilefield_brasilufmunicipio` (`uf`, `ibgeid`, `municipio`), which holds reference data and
no user data. The chosen value is stored in core's `user_info_data` as JSON
(`uf`, `codmunicipio`, `nome`). One branch, `main`, supports Moodle 4.5 to 5.3
(`$plugin->supported = [405, 503]`); the review is pinned to 5.3.

## Who is trusted

- Site administrators are fully trusted: they create the field and choose which UFs it offers
  (`param1`) and whether the select lists every UF (`param2`).
- The plugin declares **no capabilities** (there is no `db/access.php`). Who may edit a field
  value and who may see it is decided entirely by core's profile field settings (visibility,
  lock, required) and the capabilities of the page or web service that calls the field class.
- Any user who can edit a profile, and anyone on the sign-up page when the field is shown
  there, is untrusted: the UF and municipality code arrive as request parameters.
- Callers of core's user web services and CSV uploaders hold `moodle/user:update` or
  `moodle/user:create` and may set the field to any valid value.
- The IBGE API and the table it fills are trusted reference data, but only as far as the
  table: nothing a user types is ever written to it.

## Surfaces

- One web service, `profilefield_brasilufmunicipio_get_municipios` (`ajax`, `read`), with
  **`loginrequired => false` and no capability**: it takes a UF (`PARAM_ALPHA`), returns `[]`
  for anything but one of the 27 UF codes, and otherwise the table's rows for it sorted by
  name. The field has to work on the sign-up page, and the data is public.
- No page scripts, no hooks, no observers, no file serving.
- Two tasks call `api::update_municipios()`: the scheduled `update_municipios` (day 2 of each
  month) and the adhoc `populate_municipios`, queued by `db/install.php` and by the upgrade
  step 2025090400, which used to call IBGE itself. They fetch
  `https://servicodados.ibge.gov.br/api/v1/localidades/estados/<UF>/municipios` with Moodle's
  `\curl`; a UF whose answer is not HTTP 200 with a non-empty list keeps its rows, and the task
  then fails so it is retried. This is the only outbound request, and it carries no user or site
  data.
- `field.class.php` (the field), `define.class.php` (the admin form), `amd/src/field.js`
  (fills the municipality select from the web service) and `classes/privacy/provider.php`
  (export and delete of the field's `user_info_data` rows, mirroring core's text field).

## Facts that look like findings but are by design

- **The web service is anonymous on purpose**: it serves public IBGE data to the sign-up and
  edit forms. A new web service, or a change that makes this one return anything beyond the
  table, would be a finding.
- **`edit_save_data_preprocess()` reads the two selects with `optional_param()`.** From a
  form, the field's own element is a hidden integer, and QuickForm drops the municipality
  because its select has no server-side options. When the field's value is not an integer (a
  CSV upload, whose form draws the selects too, or a user web service), the value itself is
  converted through `convert_external_data()`, and an unusable one is refused. Every path
  stores a UF only if it is one of the field's available UFs, and looks a code up only when it
  has 1 to 10 digits (the column is an integer). A locked field keeps its stored value for a
  user without `moodle/user:update`, because core freezes only the hidden element.
- **A municipality code of another UF is stored without a name**, as an unknown code is, so a
  site whose table is not filled yet still stores the choice.
- **`display_data()` escapes** the UF and the name and shows nothing for a UF outside the 27.
- **The municipality is required in the browser only**; the UF is also checked on the server.
  Forms that submit through a web service (`enrol_apply`) have no municipality in the request
  at validation time.

## De-emphasise

- `amd/build/**` is minified output of `amd/src/**`; review the source.
- `lang/**`, `tests/**` and the repository's CI configuration carry no production behaviour.
