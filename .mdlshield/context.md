# Review context for profilefield_brasilufmunicipio

`profilefield_brasilufmunicipio` is a user profile field type: the user picks a Brazilian state
(UF) and then a municipality. The municipality list is held in the plugin's own table, filled
from the public IBGE localities API by a scheduled task. It has **one table**,
`profilefield_brasilufmunicipio` (`uf`, `ibgeid`, `municipio`), which holds reference data and
no user data. The chosen value is stored in core's `user_info_data` as JSON
(`uf`, `codmunicipio`, `nome`). The plugin declares no `$plugin->supported`; the review is
pinned to 5.2.

## Who is trusted

- Site administrators are fully trusted: they create the field and choose which UFs it offers
  (`param1`) and whether to show all of them (`param2`).
- The plugin declares **no capabilities** (there is no `db/access.php`). Who may edit a field
  value and who may see it is decided entirely by core's profile field settings (visibility,
  lock, required) and the capabilities of the page or web service that calls the field class.
- Any user who can edit a profile, and anyone on the sign-up page when the field is shown
  there, is untrusted: the UF and municipality code arrive as request parameters.
- The IBGE API and the table it fills are trusted reference data, but only as far as the
  table: nothing a user types is ever written to it.

## Surfaces

- One web service, `profilefield_brasilufmunicipio_get_municipios` (`ajax`, `read`), with
  **`loginrequired => false` and no capability**: it takes a UF (`PARAM_TEXT`) and returns the
  table's rows for it (`id`, `uf`, `ibgeid`, `municipio`). The field has to work before login,
  and the data is public. It reads only the plugin's own table, with a placeholder.
- No page scripts, no hooks, no observers, no file serving.
- One scheduled task, `update_municipios` (day 2 of each month, 01:00), plus the same call in
  an upgrade step. It fetches `https://servicodados.ibge.gov.br/api/v1/localidades/estados/<UF>/municipios`
  with Moodle's `\curl` and replaces each UF's rows inside a transaction. This is the only
  outbound request, and it carries no user or site data.
- `field.class.php` (the field), `define.class.php` (the admin form) and `amd/src/field.js`
  (fills the municipality select from the web service).
- Privacy: the plugin ships no provider. Field values live in core's profile data; whether
  that satisfies core's privacy compliance check for this component is not verified.

## Facts that look like findings but are by design

- **The web service is anonymous on purpose**: it serves public IBGE data to the sign-up and
  edit forms. A new web service, or a change that makes this one return anything beyond the
  table, would be a finding.
- **`field.js` writes municipality names with `innerHTML`.** The names come from the plugin's
  table, filled from IBGE, never from a user. A change that feeds user-controlled text into
  that call is a finding.
- **Municipality codes are not checked against the chosen UF on save.**
  `edit_validate_field()` only checks the UF against the allowed list; the name is looked up
  by IBGE code and left out when the code is unknown.
- **The JSON value is the storage format.** `edit_load_user_data()` and `display_data()`
  read `uf`, `codmunicipio` and `nome` from it; an upgrade step re-encodes old rows once.

## Open questions, stated rather than guessed

- `display_data()` concatenates the stored UF and name without escaping. The name comes from
  the table; the UF is validated only by the form. Whether another route (a web service that
  writes custom profile fields) can store an arbitrary UF was not verified.
- When IBGE answers with an error or an empty body, `update_municipios()` replaces that UF's
  rows with nothing until the next run. Whether that is intended is not documented.

## De-emphasise

- `amd/build/**` is minified output of `amd/src/**`; review the source.
- `lang/**`, `tests/**` and the repository's CI configuration carry no production behaviour.
