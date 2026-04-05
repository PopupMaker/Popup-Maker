---
description: Scaffold a new TypeScript package in this plugin's monorepo. Creates the package directory with proper types, registers in webpack and Assets.php. Use when asked to "create a package", "add a new package", "scaffold a package", or "new TS module".
user_invocable: true
args: <package-name> [--bundled] [--admin-only] [--with-styles]
---

# Create Package

**Plugin**: `popup-maker`
**Scope**: `@popup-maker`
**Handle prefix**: `popup-maker`
**Namespace**: `PopupMaker`
**varsName prefix**: `popupMaker`

## Steps

### 1. Validate

- Name must be lowercase kebab-case (e.g. `split-testing`, `cta-analytics`)
- Must not conflict with existing dirs in `packages/`

### 2. Create package directory

Create `packages/<name>/` with these files:

#### `package.json`

```json
{
  "name": "@popup-maker/<name>",
  "version": "1.0.0",
  "private": true,
  "description": "<description>",
  "author": "Code Atlantic",
  "license": "GPL-2.0-or-later",
  "main": "build/index.js",
  "module": "src/index.ts",
  "types": "build-types/index.d.ts",
  "exports": {
    ".": {
      "types": "./build-types/index.d.ts",
      "default": "./build/index.js"
    }
  },
  "dependencies": {},
  "devDependencies": {
    "@types/jquery": "^3.5.32"
  },
  "peerDependencies": {
    "jquery": "^3.5.32"
  },
  "scripts": {
    "build:tsc": "tsc",
    "build:types": "tsc --build",
    "clean": "rimraf build build-types tsconfig.tsbuildinfo",
    "lint": "wp-scripts lint-js .",
    "format": "wp-scripts lint-js . --fix",
    "packages-update": "wp-scripts packages-update"
  }
}
```

#### `tsconfig.json`

```json
{
  "extends": "../../tsconfig.base.json",
  "compilerOptions": {
    "rootDir": "src",
    "outDir": "build",
    "declarationDir": "build-types"
  },
  "include": ["./src/**/*"],
  "types": ["jquery"]
}
```

Add `"references"` array if importing from other local packages.

#### `src/index.ts`

```typescript
/**
 * <description>
 */

import './types';
```

#### `src/types/index.ts`

```typescript
declare global {
  interface Window {
    // Window augmentations here.
  }
}

export {};
```

If `--with-styles`, also create `src/index.scss`:

```scss
// Styles for <name>.
```

### 3. Register in `webpack.config.js`

Add to the `packages` object (alphabetical order):

```javascript
'<name>': 'packages/<name>',
```

### 4. Register in `classes/Controllers/Assets.php`

Add to the `$packages` array in `init()`. Alphabetical order.

**Frontend-bundled** (`--bundled`):

```php
'<name>' => [
    'handle'   => 'popup-maker-<name>',
    'styles'   => false, // true if --with-styles
    'varsName' => 'popupMaker<PascalCaseName>',
    'vars'     => [],
    'deps'     => [ 'popup-maker-site' ],
    'bundled'  => true,
],
```

**Admin-only** (`--admin-only` or default):

```php
'<name>' => [
    'handle'   => 'popup-maker-<name>',
    'styles'   => false, // true if --with-styles
    'varsName' => 'popupMaker<PascalCaseName>',
    'vars'     => [],
    'bundled'  => false,
],
```

### 5. Dependency extraction (if needed)

If other packages will import from `@popup-maker/<name>`, add a mapping in `packages/dependency-extraction-webpack-plugin/`.

### 6. Verify

```bash
npx tsc --noEmit --project packages/<name>/tsconfig.json
npm run build
php -l classes/Controllers/Assets.php
```

## Flags

| Flag | Default | Description |
| --- | --- | --- |
| `--bundled` | off | Frontend-bundled (loads with popups) |
| `--admin-only` | off | Admin/editor screens only |
| `--with-styles` | off | Include scss, set styles: true |

## Examples

```
/create-package split-testing --bundled
/create-package reporting-charts --admin-only --with-styles
```
