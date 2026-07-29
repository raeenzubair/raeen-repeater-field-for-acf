/**
 * Jest setup file for Advanced Repeater for ACF tests.
 */

// Mock global ACF object.
global.acf = {
	addAction: jest.fn(),
	doAction: jest.fn(),
	get: jest.fn(),
};

// Mock jQuery.
global.jQuery = global.$ = jest.fn( ( selector ) => {
	const element = document.querySelector( selector );
	if ( ! element ) {
		return {
			each: jest.fn(),
			find: jest.fn( () => global.$() ),
			on: jest.fn(),
			off: jest.fn(),
			append: jest.fn(),
			remove: jest.fn(),
			data: jest.fn(),
			attr: jest.fn(),
			addClass: jest.fn(),
			removeClass: jest.fn(),
			hasClass: jest.fn(),
			closest: jest.fn(),
			siblings: jest.fn(),
			parent: jest.fn(),
			children: jest.fn(),
			val: jest.fn(),
			html: jest.fn(),
			text: jest.fn(),
			css: jest.fn(),
			animate: jest.fn(),
			sortable: jest.fn(),
			trigger: jest.fn(),
			serialize: jest.fn(),
			serializeArray: jest.fn(),
		};
	}

	return {
		...element,
		each: jest.fn( ( callback ) => callback( 0, element ) ),
		find: jest.fn( ( sel ) => global.$( element.querySelector( sel ) ) ),
		on: jest.fn(),
		off: jest.fn(),
		append: jest.fn(),
		remove: jest.fn(),
		data: jest.fn(),
		attr: jest.fn(),
		addClass: jest.fn(),
		removeClass: jest.fn(),
		hasClass: jest.fn(),
		closest: jest.fn( ( sel ) => global.$( element.closest( sel ) ) ),
		siblings: jest.fn(),
		parent: jest.fn(),
		children: jest.fn(),
		val: jest.fn(),
		html: jest.fn(),
		text: jest.fn(),
		css: jest.fn(),
		animate: jest.fn(),
		sortable: jest.fn(),
		trigger: jest.fn(),
		serialize: jest.fn(),
		serializeArray: jest.fn(),
	};
} );

// Mock wp.media.
global.wp = {
	media: jest.fn( () => ({
		on: jest.fn(),
		open: jest.fn(),
		state: jest.fn( () => ({
			get: jest.fn( () => ({
				first: jest.fn( () => ({
					toJSON: () => ({ id: 123, url: 'https://example.com/image.jpg', title: 'Test Image' }),
				})),
			})),
		})),
	})),
};

// Mock tinyMCE.
global.tinyMCE = {
	get: jest.fn( () => ({ remove: jest.fn() })),
};

// Mock window.acfRepeater (localized script data).
global.acfRepeater = {
	nonce: 'test-nonce',
	ajax_url: '/wp-admin/admin-ajax.php',
	i18n: {
		add_row: 'Add Row',
		delete_row: 'Delete Row',
		duplicate_row: 'Duplicate Row',
		collapse_row: 'Collapse Row',
		expand_row: 'Expand Row',
		sort_rows: 'Sort Rows',
		confirm_delete: 'Are you sure you want to delete this row?',
		min_rows_error: 'Minimum %d rows required.',
		max_rows_error: 'Maximum %d rows allowed.',
		required_field: 'This field is required',
		loading: 'Loading...',
		no_rows: 'No rows added yet.',
		row_collapsed: 'Row collapsed',
		row_expanded: 'Row expanded',
		drag_to_reorder: 'Drag to reorder',
	},
};

// Mock fetch globally.
global.fetch = jest.fn( () =>
	Promise.resolve({
		ok: true,
		json: () => Promise.resolve({ success: true }),
	})
);

// Mock URLSearchParams.
global.URLSearchParams = URLSearchParams;

// Mock console methods to reduce noise.
global.console = {
	...console,
	log: jest.fn(),
	warn: jest.fn(),
	error: jest.fn(),
};

// Clean up after each test.
afterEach( () => {
	jest.clearAllMocks();
	document.body.innerHTML = '';
} );