# An item without its code is inserted.
s~if \(!isset\(\$item->id, \$item->nome\) \|\| !is_int\(\$item->id\)\) \{\n                return null;\n            \}~~;
