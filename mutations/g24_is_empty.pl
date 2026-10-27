# A stored '1' counts as a value again.
s~return \$this->get_value\(\) === null;~return parent::is_empty();~;
