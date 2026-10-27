# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## Unreleased

## 18 - 2026-10-27

### Added

- Privacy provider: exports and deletes the values users hold in fields of this type.
- Values uploaded in a CSV file or sent through the user web services are converted and validated:
  `UF / municipality name` or the stored JSON, with the code, the name or both.
- Brazilian Portuguese strings (in git; they reach sites through AMOS).
- PHPUnit and Behat coverage, the fleet CI workflow and the plugins directory readiness test.

### Changed

- Requires Moodle 4.5; supports 4.5 to 5.3.
- The installation, and the upgrade step that created the municipality table, queue an adhoc task
  that fills the list instead of calling IBGE during the upgrade.
- The JavaScript is an ES module; the municipality select starts with an empty option, is
  disabled while it loads and reports a failed request.
- The scheduled task runs at a random minute and fails when a UF could not be refreshed, so it is
  retried.

### Fixed

- Choosing a UF without a municipality no longer fails on PostgreSQL (`invalid input syntax for type
  bigint`).
- With both selects empty the field stores an empty value instead of `1`, which made a required
  field count as filled. Values stored as `1` by earlier versions count as empty too, so a user
  with such a value in a required field is asked to fill it in.
- A CSV upload or a web service no longer drops the value it sends; a web service value that is
  not usable is refused with an error instead of clearing the field.
- A locked field can no longer be changed by posting the selects.
- A required field needs a UF on the server too, not only in the browser.
- A municipality code too long for the database column no longer fails the query on PostgreSQL.
- An IBGE outage no longer empties the municipality list of a UF.
- A field saved with no UF selected offers every UF again, instead of none.
- Municipalities are listed by name.
- The displayed value is escaped.
- After changing the UF, the previously chosen municipality is no longer reselected by mistake.
