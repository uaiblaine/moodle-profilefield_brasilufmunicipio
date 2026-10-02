## MDL Shield reviews

Pull request reviews by MDL Shield are **manual only**. Comment `!mdlshield review` (what is
new since the last review) or `!mdlshield review full` (the whole pull request) on a pull
request; the commands work for the repository owner and the `trusted_users` of
`.mdlshield/config.yml`, and a forced command ignores the filters and a draft's silence.
Nothing runs on its own because `include.branches` is an empty list, which MDL Shield reads
as a rule that matches no branch.

- `.mdlshield/config.yml` and `.mdlshield/context.md` are read **from the default branch
  only** (`main`), so a pull request cannot change the rules that judge it, and the pull
  request that first adds them is judged by the website settings instead. Both are
  `export-ignore`d and never reach the release zip. The file overrides the website for each
  key it names; an invalid file stops reviews for the repository until it is fixed, and an
  unknown key only raises a warning on the summary comment.
- **One Moodle version per repository.** There is no per-branch mapping, so the file pins
  `moodle.versions: ["5.2"]`. The plugin declares no supported range; the pinned version is the highest Moodle this configuration assumes.
- `fail_on.severity` is `high`: the check fails on an open finding at or above it. Code
  quality findings never block, only security findings do.
- The context file is project knowledge that **influences** the reviewer and is not a
  filter. Keep it in step with the code it states: capabilities, the web service list and
  the facts it calls deliberate. The website's own review context is replaced entirely by
  this file, not merged.
- On the website the repository still needs *Enable pull request reviews* and a granted
  preview access; the file cannot do either.

