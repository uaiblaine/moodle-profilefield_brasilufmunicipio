# Claude instructions for `profilefield_brasilufmunicipio`

This file is auto-loaded as context whenever Claude works in this plugin's
directory tree. **Fleet-wide standards live in `~/dev/CLAUDE.md`** (coding
style, CI gates, lang-string rules, the `mdl` environment, git rules) — do not
repeat them here. This file keeps only what is true for this plugin, as rules
with a one-line reason each.

Plugin context: a Moodle **profilefield** plugin ("Brasil UF e Município") that
draws a UF select and a municipality select filled from the chosen UF. It owns
one table, `profilefield_brasilufmunicipio`, the IBGE municipality list (no user
data), filled by an adhoc task after installation and refreshed by a monthly
scheduled task; the chosen value lives in core's `user_info_data` as JSON
`{uf, codmunicipio, nome}`. Written by Daniel Neis Araujo (Adapta); his copyright
stays on his files, new files carry the owner's. One branch, `main`, supports Moodle
**4.5 through 5.3** (`$plugin->requires = 2024100700`, `$plugin->supported = [405, 503]`),
so `$plugin->version` is date-based. CI is the fleet reusable workflow, one job per
supported branch in `.github/workflows/ci.yml` — **update those jobs when `supported`
changes**. Mounted at `user/profile/field/brasilufmunicipio` on every stack (`auto`
in `~/dev/moodle-dev/plugins.conf`).

## Agent orchestration budget (fleet rule, repeated here on purpose)

Section 6 of `~/dev/CLAUDE.md` (`moodle-dev/CLAUDE.fleet.md`) is the authority and says why.
This short copy reaches sessions that do not load that file: cloud sessions and checkouts
outside `~/dev`. Every subagent gets the model and effort of its role from its agent
definition, and none runs on the session model.

| Role | model | effort | agent |
|---|---|---|---|
| Mechanical sweeps, greps, renames, counts, log reading | `haiku` | `medium` | `fleet-sweeper` |
| Checklists against evidence (handoff counts, spec lines against a sweep log, lang lockstep) | `haiku` | `high` | `fleet-checker` |
| Readers, measurers, graders | `sonnet` | `medium` | `fleet-reader` |
| Refuters and verifiers of a blocking finding | `sonnet` | `high` | `fleet-verifier` |
| Well-scoped implementation (established cause, settled design, written recipe) | `sonnet` | `medium` | `fleet-fixer` |
| Non-trivial implementation (open design, several files, long tasks) | `opus` | `high` | `fleet-implementer` |
| Consolidators, critics, estimators, ADR and documentation drafters | `opus` | `high` | `fleet-synthesist` |

- Launch the `Agent` tool with `subagent_type: "fleet-*"`; it has no `effort` parameter, so
  the role's effort comes from that definition (`mdl claude-setup` installs them). Where they
  are not installed, pass `model`. Aliases only; never `fable`; `xhigh` only for a long-horizon
  implementer whose prompt says why; never `xhigh`/`max` on Sonnet or Haiku.
- No long command inside a subagent: `mdl ci --matrix`, `mdl mutate` and Behat run from the
  main session in a background Bash command; a subagent runs the fast gate its prompt names and
  reports the command with its counts.
- Workflows only on the user's opt-in, every `agent()` with `agentType: 'fleet-*'`, under 10
  agents. Advisor off by default.

## Commands

```sh
mdl ci moodle-profilefield_brasilufmunicipio --matrix --behat   # the merge gate
mdl phpunit m503 profilefield_brasilufmunicipio                 # the suite (a path runs nothing: *_test.php)
mdl behat m503 @profilefield_brasilufmunicipio                  # Behat smoke tests
mdl grunt m503 user/profile/field/brasilufmunicipio             # rebuild amd/build (commit with src)
```

## Architecture gotchas

- **The JSON shape `{uf, codmunicipio, nome}` is a contract.** Stored values on
  production sites, CSV files and `enrol_apply`'s tests (`tests/fixtures/municipality_field.php`
  there, which uses this type as its fixture) all read it.
- **The two selects are read from the request, not from the form data.** From a form,
  `edit_save_data_preprocess()` receives the hidden element's integer, and QuickForm drops
  the municipality because its select has no server-side options. `enrol_apply` relies on
  this: it puts the selects in `$_POST` and calls `edit_save_data()` /
  `edit_save_data_preprocess()` with only `inputname` in `$usernew`, so never decide "form
  or not" from `$usernew`.
- **The form path needs both facts: an integer `$data` and the UF select in the request.**
  `tool_uploaduser`'s second form draws every profile field, so the selects are in its request
  (empty) while `$data` is the CSV value; a web service may send a number with no selects.
  Anything else is an external value, converted by
  `convert_external_data()`, which `tool_uploaduser` also calls to validate (null = invalid);
  the user web services validate nothing, so preprocess throws on null rather than clearing the
  field. Every path stores only an available UF.
- **Core locks only the hidden element**, so preprocess returns the stored value for a locked
  field when the user lacks `moodle/user:update`.
- **The municipality cannot be required on the server**: `enrol_apply` validates through a
  web service form whose request does not carry it. Only the UF is checked there.
- **Look a code up only through `api::is_ibgeid()`** (1 to 10 digits): the column is an
  integer, and PostgreSQL fails the whole query on `ibgeid = ''` or a value out of range.
- **Empty means `''`.** Earlier versions stored `1` for "nothing chosen", which made a
  required field count as filled; such rows still exist, and `get_value()` and `is_empty()`
  read them as empty, so a user holding one in a required field is asked to fill it in.
- **No request to IBGE inside install or upgrade**: queue `populate_municipios`. A UF
  whose answer is unusable keeps its rows (`api::update_municipios()` returns it). The
  scheduled task then throws so cron retries it, but **the adhoc task must never throw**:
  Behat's `install_site()` runs queued adhoc tasks and dies on an exception (the cron lock is
  left unreleased), so every CI installing this plugin would depend on IBGE. It queues the
  failed UFs an hour later instead, up to `MAX_ATTEMPTS`.

## Testing notes

- `\curl::mock_response()` serves the LAST queued answer first and reports HTTP 200; the
  refresh asks for the 27 UFs in order, so tests queue them reversed. An unqueued request
  goes to the network.
- The plugin still supports 4.5, so test metadata stays in docblocks (`@covers`,
  `@dataProvider`), which PHPUnit 11 reports as deprecations.

## MDL Shield reviews

Pull request reviews by MDL Shield are **manual only**. Comment `!mdlshield review` (what is
new since the last review) or `!mdlshield review full` (the whole pull request) on a pull
request; the commands work for the repository owner and the `trusted_users` of
`.mdlshield/config.yml`, and a forced command ignores the filters and a draft's silence.
Nothing runs on its own because `include.branches` names a branch no pull request targets
(a non-empty include list is a requirement; an empty one was measured NOT to stop automatic
reviews).

- **A review is a counted resource, so ask for one on purpose.** The monthly quota is shared by every
  repository, and only a completed review uses one. The owner posts the command; a session never
  does without being told to for that pull request. Keep a pull request at or under **3,000 effective
  changed lines** (additions plus deletions after `exclude.paths`; the limit is MDL Shield's own
  and 12,000 is the hard one, forced reviews included): `mdl mdlshield size <repo-dir>` estimates it.
  One `review full` per pull request, after the local matrix is green. The rule and its reasons are
  in the fleet CLAUDE.md (section 4, "MDL Shield reviews").

- `.mdlshield/config.yml` and `.mdlshield/context.md` are read **from the default branch
  only** (`main`), so a pull request cannot change the rules that judge it, and the pull
  request that first adds them is judged by the website settings instead. Both are
  `export-ignore`d and never reach the release zip. The file overrides the website for each
  key it names; an invalid file stops reviews for the repository until it is fixed, and an
  unknown key only raises a warning on the summary comment.
- **One Moodle version per repository.** There is no per-branch mapping, so the file pins
  `moodle.versions: ["5.3"]`, the highest Moodle `main` supports.
- `fail_on.severity` is `high`: the check fails on an open finding at or above it. Code
  quality findings never block, only security findings do.
- The context file is project knowledge that **influences** the reviewer and is not a
  filter. Keep it in step with the code it states: capabilities, the web service list and
  the facts it calls deliberate. The website's own review context is replaced entirely by
  this file, not merged.
- On the website the repository still needs *Enable pull request reviews* and a granted
  preview access; the file cannot do either.

