/**
 * Jest Configuration for Advanced Repeater for ACF.
 */

export default {
	testEnvironment: 'jsdom',
	setupFilesAfterEnv: [ '<rootDir>/tests/js/setup.js' ],
	testMatch: [ '<rootDir>/tests/js/**/*.test.js' ],
	moduleNameMapper: {
		'^@admin/(.*)$': '<rootDir>/src/js/admin/$1',
		'^@modules/(.*)$': '<rootDir>/src/modules/$1',
		'^@css/(.*)$': '<rootDir>/src/css/$1',
	},
	transform: {
		'^.+\\.js$': 'babel-jest',
	},
	collectCoverageFrom: [
		'src/js/**/*.js',
		'!src/js/**/*.test.js',
	],
	coverageDirectory: 'coverage',
	coverageReporters: [ 'text', 'lcov', 'html' ],
	verbose: true,
};