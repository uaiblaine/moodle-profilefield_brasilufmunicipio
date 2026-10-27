# The path is decided from the value alone: a number sent by a web service is read as a form's integer.
s~if \(\$uf !== null && \(is_int\(\$data\) \|\| ctype_digit\(\(string\)\$data\)\)\) \{~if (is_int(\$data) || ctype_digit((string)\$data)) {~;
