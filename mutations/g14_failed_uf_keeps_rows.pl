# A UF whose answer is unusable is emptied and not reported.
s~if \(\$municipios === null\) \{\n            return false;\n        \}~if (\$municipios === null) {\n            \$municipios = [];\n        }~;
