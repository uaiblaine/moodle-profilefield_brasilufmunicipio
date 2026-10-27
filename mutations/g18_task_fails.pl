# The scheduled task succeeds when a UF failed, so it is not retried.
s~if \(\$failed\) \{~if (false) {~;
