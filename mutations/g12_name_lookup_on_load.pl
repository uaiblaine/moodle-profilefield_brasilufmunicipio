# A value uploaded with only a name gets no code for the select.
s~if \(\$value->codmunicipio === '' && \(\$record = api::get_municipio_by_name\(\$value->uf, \$value->nome\)\)\) \{~if (false) {~;
