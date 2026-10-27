# The path is decided from the request instead of the value: the upload form posts the selects too.
s~if \(is_int\(\$data\) \|\| ctype_digit\(\(string\)\$data\)\) \{~if (optional_param(\$this->inputname . '_uf', null, PARAM_TEXT) !== null) {~;
