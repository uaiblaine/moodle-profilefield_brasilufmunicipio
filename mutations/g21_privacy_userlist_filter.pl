# Deleting for a list of users deletes the context owner even when not in the list.
s~ && in_array\(\$context->instanceid, \$userlist->get_userids\(\)\)~~;
