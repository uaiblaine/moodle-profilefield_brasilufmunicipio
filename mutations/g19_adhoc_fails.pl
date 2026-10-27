# The adhoc task drops the UFs that failed instead of queueing them again.
s~if \(!\$failed\) \{\n            return;\n        \}~return;~;
