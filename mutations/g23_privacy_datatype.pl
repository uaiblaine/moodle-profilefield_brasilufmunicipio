# Deletes remove the user's values in every field type.
s~'userid = :userid AND fieldid IN \(SELECT id FROM \{user_info_field\} WHERE datatype = :datatype\)'~'userid = :userid AND :datatype = :datatype'~;
