# Advanced Repeater for ACF - Manual QA Checklist

## Pre-Release Testing Checklist

### Plugin Installation & Activation
- [ ] Plugin installs without errors on clean WordPress 6.0+
- [ ] Plugin activates successfully with PHP 8.1, 8.2, 8.3
- [ ] Plugin shows proper error notice when ACF is not installed
- [ ] Plugin shows version notice when ACF < 6.0 is installed
- [ ] Plugin deactivates cleanly
- [ ] Plugin uninstalls and cleans up options

### Field Group Editor
- [ ] "Repeater" field appears under Layout category
- [ ] Field settings render correctly:
  - [ ] Minimum Rows input
  - [ ] Maximum Rows input
  - [ ] Button Label input
  - [ ] Layout radio (Table/Block)
  - [ ] Collapsed Field select (populated with sub-fields)
  - [ ] Enable Row Sorting toggle
  - [ ] Enable Row Duplication toggle
  - [ ] Delete Confirmation toggle
  - [ ] Default Rows repeater
- [ ] Sub-fields can be added, edited, removed, reordered
- [ ] All ACF Free field types available as sub-fields
- [ ] Conditional logic works on sub-fields
- [ ] Field group saves without errors
- [ ] Field group exports to JSON correctly
- [ ] Field group imports from JSON correctly
- [ ] Field group exports to PHP correctly

### Post Edit Screen (Table Layout)
- [ ] Repeater renders as table with headers
- [ ] Add Row button works
- [ ] Rows display with drag handle, sub-fields, actions
- [ ] Drag and drop reordering works
- [ ] Row collapse/expand works (when collapsed field set)
- [ ] Row duplicate works
- [ ] Row delete works with confirmation
- [ ] Min rows validation prevents deletion
- [ ] Max rows validation prevents addition
- [ ] Required sub-field validation works
- [ ] Email/URL/number validation works
- [ ] Keyboard navigation (Tab, arrows, Enter, Escape)
- [ ] Mobile responsive layout
- [ ] Empty state notice shows when no rows
- [ ] Min rows notice shows when below minimum

### Post Edit Screen (Block Layout)
- [ ] Repeater renders as card blocks
- [ ] Block header shows collapsed title
- [ ] Block toggle expands/collapses content
- [ ] Add Row button works
- [ ] Row duplicate works
- [ ] Row delete works with confirmation
- [ ] Drag and drop reordering works
- [ ] Sub-fields render correctly in block content
- [ ] Mobile responsive

### Sub-Field Types Testing
Test each field type as sub-field:
- [ ] Text
- [ ] Textarea
- [ ] Number (min/max/step)
- [ ] Email
- [ ] URL
- [ ] Image (select, preview, remove)
- [ ] File (select, preview, remove)
- [ ] Gallery
- [ ] Select (single, multiple, UI, AJAX)
- [ ] Radio
- [ ] Checkbox
- [ ] True/False
- [ ] WYSIWYG (TinyMCE init, save)
- [ ] Date Picker
- [ ] Time Picker
- [ ] DateTime Picker
- [ ] Color Picker
- [ ] Link
- [ ] Nested Repeater
- [ ] Flexible Content
- [ ] Clone

### Nested Repeaters
- [ ] Nested repeater renders inside parent row
- [ ] Nested add/duplicate/delete/sort works
- [ ] Nested collapsed titles work
- [ ] Nested validation works
- [ ] Multiple levels of nesting (3+)
- [ ] Data saves correctly for all levels
- [ ] Data loads correctly for all levels

### Data Storage & Retrieval
- [ ] Data saves to postmeta correctly
- [ ] Data loads from postmeta correctly
- [ ] Row IDs persist across saves
- [ ] Row order persists
- [ ] get_field() returns formatted array
- [ ] the_field() outputs correctly
- [ ] have_rows()/the_row()/get_sub_field() work
- [ ] Nested repeater data structure correct
- [ ] Default rows populate on new posts

### AJAX Operations
- [ ] Add row via AJAX returns HTML
- [ ] Remove row via AJAX removes from DOM
- [ ] Duplicate row via AJAX inserts copy
- [ ] Sort rows via AJAX saves order
- [ ] Save row via AJAX updates data
- [ ] Get row layout via AJAX
- [ ] Nonce verification works
- [ ] Capability checks work
- [ ] Error responses handled gracefully

### REST API
- [ ] REST API enabled in settings
- [ ] GET /posts/{id} includes acf_repeater
- [ ] GET /repeater-field-for-acf/v1/repeater/{field_key} works
- [ ] GET /repeater-field-for-acf/v1/repeater/{field_key}/rows/{index} works
- [ ] POST /repeater-field-for-acf/v1/repeater/{field_key}/rows adds row
- [ ] PUT /repeater-field-for-acf/v1/repeater/{field_key}/rows/{index} updates row
- [ ] DELETE /repeater-field-for-acf/v1/repeater/{field_key}/rows/{index} removes row
- [ ] Permission checks (read/edit)
- [ ] Validation on write endpoints
- [ ] Schema exposed correctly

### ACF JSON Sync
- [ ] Field group exports to JSON with repeater
- [ ] Field group imports from JSON
- [ ] Sub-field keys regenerated on import
- [ ] Nested repeater keys regenerated
- [ ] Custom save path setting works
- [ ] Custom load paths setting works
- [ ] Sync icon appears in field group list

### PHP Export
- [ ] Field group exports to PHP code
- [ ] Repeater field included in export
- [ ] Sub-fields included in export
- [ ] Generated code is valid PHP

### Gutenberg Editor
- [ ] Repeater works in Gutenberg sidebar
- [ ] Repeater works in block editor (ACF Blocks)
- [ ] All row operations work in Gutenberg
- [ ] Sub-fields render correctly

### Classic Editor / Meta Boxes
- [ ] Repeater works in classic meta boxes
- [ ] Repeater works in custom post types
- [ ] Repeater works in taxonomy terms
- [ ] Repeater works in user profiles
- [ ] Repeater works in widgets
- [ ] Repeater works in options pages

### Multisite
- [ ] Network activation works
- [ ] Settings work per site
- [ ] Field groups sync across network (if configured)

### Frontend Forms (acf_form)
- [ ] Repeater renders in frontend form
- [ ] Add/remove/sort/duplicate work
- [ ] Sub-fields initialize correctly
- [ ] Form submission saves data
- [ ] Validation works

### Translations
- [ ] All strings wrapped in translation functions
- [ ] POT file generated and complete
- [ ] Text domain correct: repeater-field-for-acf
- [ ] Language files load correctly

### Accessibility (WCAG 2.1 AA)
- [ ] Keyboard navigation for all controls
- [ ] ARIA labels on buttons
- [ ] Focus indicators visible
- [ ] Color contrast ratios met
- [ ] Screen reader announcements for dynamic changes
- [ ] Modal dialogs trap focus
- [ ] Reduced motion respected

### Performance
- [ ] 100+ rows load without significant delay
- [ ] Drag/drop smooth with 50+ rows
- [ ] Lazy initialization of complex fields (WYSIWYG, Gallery)
- [ ] No memory leaks in JS
- [ ] AJAX requests debounced appropriately
- [ ] CSS/JS minified in production

### Browser Compatibility
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome/Safari

### Error Handling
- [ ] PHP errors logged but not displayed
- [ ] JS errors caught and logged
- [ ] Graceful degradation when ACF not active
- [ ] Invalid field keys handled
- [ ] Corrupted data handled

### Security
- [ ] Nonces on all AJAX requests
- [ ] Capability checks on all endpoints
- [ ] Input sanitization on all inputs
- [ ] Output escaping on all outputs
- [ ] No direct file access
- [ ] SQL queries prepared
- [ ] XSS prevention in dynamic content

### Code Quality
- [ ] PHPStan Level 8 passes
- [ ] PHPCS WordPress standards pass
- [ ] PHPUnit tests pass
- [ ] Jest tests pass
- [ ] No deprecated functions used
- [ ] PHPDoc on all public methods

## Post-Release Monitoring

### First 24 Hours
- [ ] Check WordPress.org support forum
- [ ] Monitor error logs
- [ ] Check GitHub issues

### First Week
- [ ] Review user feedback
- [ ] Check for compatibility reports
- [ ] Monitor performance metrics

### Ongoing
- [ ] Test with WordPress updates
- [ ] Test with ACF updates
- [ ] Update translations
- [ ] Security audit