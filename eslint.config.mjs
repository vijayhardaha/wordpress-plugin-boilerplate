/**
 * =====================================================================
 * Eslint Configuration (Flat)
 * =====================================================================
 * Purpose: Project-wide ESLint configuration for Next.js, TypeScript, and
 *          React. Enforces code quality, accessibility, and consistent styling.
 * Docs:    https://eslint.org/docs/latest/use/configure/configuration-files-new
 * Usage:   bunx eslint .
 * =====================================================================
 */

import { createConfig } from '@vijayhardaha/dev-config/eslint';

export default createConfig({ importOrder: false });
