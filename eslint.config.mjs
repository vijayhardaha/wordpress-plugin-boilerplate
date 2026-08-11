/**
 * =====================================================================
 * Eslint Configuration (Flat)
 * =====================================================================
 * Purpose: Project-wide ESLint configuration for WordPress plugin JavaScript.
 *          Enforces code quality, accessibility, and consistent styling.
 * Docs:    https://eslint.org/docs/latest/use/configure/configuration-files-new
 * Usage:   bunx eslint .
 * =====================================================================
 */

import { createConfig } from '@vijayhardaha/dev-config/eslint';

export default createConfig({
  importOrder: false,
  plugins: [{ files: ['**/*.js'], languageOptions: { globals: { wp: 'readonly', jQuery: 'readonly' } } }],
});
