# The name lookup becomes case-sensitive. MariaDB compares case-insensitively anyway (PG only).
s~\$DB->sql_equal\('municipio', ':name', false\)~\$DB->sql_equal('municipio', ':name')~;
