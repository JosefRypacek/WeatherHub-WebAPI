import { defineConfig } from 'vite';
import nette from '@nette/vite-plugin';
import inject from '@rollup/plugin-inject'; // Globalni zpristupneni knihoven...

export default defineConfig({
	plugins: [
		inject({
			$: 'jquery', // Nastaví $ a jQuery jako globální proměnné.
			jQuery: 'jquery',
			naja: 'naja', // Mapuje globální naja na npm balíček 'naja'
		}),
		nette(
			//{input: 'main.js',} // not required when defined below
		)
	],

	build: {

		emptyOutDir: true,
		rollupOptions: {
			// Toto je mozna potreba proto, ze nette plugin neni kompatibilni se starou verzi nette?
			input: [
				'assets/main.js',
			],
		},
	},

	css: {
		devSourcemap: true,
	},
});