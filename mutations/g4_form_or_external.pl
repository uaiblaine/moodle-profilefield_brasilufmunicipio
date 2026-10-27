# The path is decided from the request alone: the upload form posts the selects too.
s~if \(\$uf !== null && \(is_int\(\$data\) \|\| ctype_digit\(\(string\)\$data\)\)\) \{~if (\$uf !== null) {~;
