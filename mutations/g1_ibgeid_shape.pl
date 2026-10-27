# is_ibgeid() accepts anything: an empty, non-numeric or overlong code reaches the integer column.
s~return preg_match\('/\^\[0-9\]\{1,10\}\$/', \$code\) === 1;~return true;~;
