## 2024-08-16 - Prevent Arbitrary Post Meta Manipulation (IDOR)
**Vulnerability:** Insecure Direct Object Reference (IDOR) where a user with `edit_post` capability could arbitrarily sort any array-based post meta using the `ajax_sort_rows` endpoint because it blindly accepted any `field_key`.
**Learning:** ACF and WordPress AJAX endpoints must always validate that user-provided field keys correspond to the expected type and exist before manipulating them, especially when bypassing standard wrapper functions like `update_field` and using low-level `update_post_meta` instead.
**Prevention:** Use `acf_get_field()` to validate the field exists and its type matches expectations before acting on the data.
