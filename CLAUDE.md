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
  in the fleet CLAUDE.md (section 4, "MDL Shield review budget").

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

