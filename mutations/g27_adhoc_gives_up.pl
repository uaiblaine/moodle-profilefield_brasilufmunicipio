# The adhoc task never gives up, queueing a retry every hour for ever.
s~if \(\$attempt >= self::MAX_ATTEMPTS\) \{\n            return;\n        \}~~;
