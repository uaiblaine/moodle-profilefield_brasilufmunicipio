moodle-profilefield_brasilufmunicipio
=====================================

[![Moodle Plugin CI](https://github.com/uaiblaine/moodle-profilefield_brasilufmunicipio/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/uaiblaine/moodle-profilefield_brasilufmunicipio/actions/workflows/ci.yml?query=branch%3Amain)

A custom user profile field for Brazilian addresses: the user picks a UF (state) and then a
municipality of that UF.

The field draws two selects. The UF select lists the UFs the administrator made available; the
municipality select is filled from the chosen UF. The municipality list comes from the public
[IBGE localities API](https://servicodados.ibge.gov.br/api/docs/localidades) and is kept in the
plugin's own table, so the form never waits on IBGE. The chosen value is stored in core's profile
data as JSON with the UF code, the IBGE code of the municipality and its name.


Requirements
------------

- Moodle 4.5 or later (tested up to Moodle 5.3)
- Outbound HTTPS access from the Moodle server to `servicodados.ibge.gov.br`, for the task that
  fills the municipality list


Installation
------------

Install the plugin like any other plugin to folder `/user/profile/field/brasilufmunicipio`.

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins.

The installation queues an adhoc task that fills the municipality list from IBGE on the next cron
run; until it has run, the municipality select stays empty. Cron must therefore be running. The
installation itself makes no request to IBGE.


Usage
-----

### Administrators

Add a field of the type *Brasil UF e Município* under *Site administration > Users > Accounts >
User profile fields*. Besides core's settings for every profile field, it has two of its own:

- **Available UFs**: the UFs a user may choose. With none selected, every UF is available.
- **List every UF**: list every UF in the select, but accept only the available ones.

### Users

On the profile edit page, on the sign-up page when the field is shown there, and anywhere else core
draws profile fields, the user picks a UF and then a municipality. When the field is required, a
user editing their own profile or signing up must choose both (the UF is also checked on the
server). A locked field cannot be changed by a user who lacks `moodle/user:update`. The profile shows the value as `UF / municipality`.

### CSV upload and web services

*Upload users* and the user web services (`core_user_create_users`, `core_user_update_users`)
accept the field's value in either form:

- `SC / Florianópolis`: a UF code and a municipality name, which is matched without regard to case;
- the stored JSON, such as `{"uf":"SC","codmunicipio":"4205407"}`, with the code, the name
  (`nome`) or both.

The UF must be one of the field's available UFs. The code and the name are completed from the
municipality list. An empty value clears the field. *Upload users* reports any other value as
invalid, and the web services refuse it with an error, leaving the stored value as it was.


Capabilities
------------

The plugin declares no capabilities. Who may edit or see a value is decided by core's settings of
each profile field (visibility, locked, required) and the capabilities of the page that shows it.


Settings
--------

The plugin has no site settings; each field's settings are described under Usage.


Scheduled task and web service
------------------------------

- `\profilefield_brasilufmunicipio\task\update_municipios` refreshes the municipality list from
  IBGE on day 2 of each month. A UF whose answer fails or is unusable keeps its current list, and
  the task fails so that it is retried.
- `profilefield_brasilufmunicipio_get_municipios` lists the municipalities of a UF for the
  municipality select. It is available without login, because the field is shown on the sign-up
  page, and returns only the public IBGE list.


Data stored
-----------

- `profilefield_brasilufmunicipio`: the municipality list (UF code, IBGE code, name), refreshed
  from IBGE by the scheduled task. It holds no personal data and is removed when the plugin is
  uninstalled.
- Core's `user_info_data` holds each user's value for each field of this type: the UF code, the
  IBGE code and the name of the municipality. The value is kept until the user, the field or the
  plugin is deleted.

The privacy provider exports a user's values in their user context and deletes them on request.
Requests to IBGE carry no user or site data.


Troubleshooting
---------------

- **The municipality select is empty.** The list is not filled yet, or IBGE could not be reached:
  check that cron runs and that *Refresh the municipality list from IBGE* succeeds in the task
  logs, and that the server can reach `servicodados.ibge.gov.br` (directly or through the proxy
  configured in Moodle).
- **A UF is refused.** It is not among the field's available UFs.


Credits
-------

Written by Daniel Neis Araujo ([Adapta](https://www.adapta.online)), who holds the copyright of
the original code. This repository is maintained by Anderson Blaine.


Translations
------------

The English strings ship in the package. The Brazilian Portuguese translation is maintained in this
repository (`lang/pt_br`) and reaches sites through [AMOS](https://lang.moodle.org/), as the plugins
directory requires; it is left out of the release package.


License
-------

This plugin is licensed under the [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).

Copyright: 2021 Daniel Neis Araujo, 2026 Anderson Blaine
