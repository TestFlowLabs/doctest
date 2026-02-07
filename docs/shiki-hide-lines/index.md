# shiki-hide-lines

A [Shiki](https://shiki.style) transformer that hides marked lines from rendered code blocks while keeping them in the source.

Hide boilerplate (imports, setup, autoloading) that is necessary for execution but distracting for readers.

[npm](https://www.npmjs.com/package/shiki-hide-lines) · [GitHub](https://github.com/TestFlowLabs/shiki-hide-lines)

## Install

```bash
npm install shiki-hide-lines
```

## Modes

### Fully Hidden (default)

Marked lines are completely removed from the rendered output. No trace remains.

```ts
import { codeToHtml } from 'shiki'
import { transformerHideLines } from 'shiki-hide-lines'

const html = await codeToHtml(code, {
  lang: 'php',
  theme: 'github-dark',
  transformers: [transformerHideLines()]
})
```

### Revealable (`reveal: true`)

Marked lines are collapsed behind a clickable placeholder that readers can expand. A `··· N hidden lines` placeholder appears. Clicking it expands the hidden lines and changes the text to `▲ N hidden lines`. Clicking again collapses them back.

```ts
transformerHideLines({ reveal: true })
```

Reveal mode needs the companion CSS:

```ts
import 'shiki-hide-lines/style.css'
```

**Live example** — click the placeholder below to reveal the hidden lines:

```php
<?php // [!code hide]
declare(strict_types=1); // [!code hide]
require_once 'vendor/autoload.php'; // [!code hide]
use App\Models\User; // [!code hide]

$user = User::find(1);
echo $user->name;
```

## Custom Placeholder Text

Customize the collapsed and expanded placeholder text.

**String template** — use `{count}` for the line count:

```ts
transformerHideLines({
  reveal: true,
  summaryTemplate: '▶ {count} lines collapsed',
  expandedSummaryTemplate: '▼ {count} lines shown'
})
// Collapsed: ▶ 3 lines collapsed
// Expanded:  ▼ 3 lines shown
```

**Function** — full control for i18n or pluralization:

```ts
transformerHideLines({
  reveal: true,
  summaryTemplate: (count) =>
    count === 1 ? '1 ligne masquée' : `${count} lignes masquées`,
  expandedSummaryTemplate: (count) =>
    count === 1 ? '▲ 1 ligne' : `▲ ${count} lignes`
})
```

## Custom Marker

Change the marker keyword (default: `hide`):

```ts
transformerHideLines({ marker: 'hidden' })
// Uses: // [!code hidden] and // [!code hidden:start] / // [!code hidden:end]
```

## Platform Integrations

### VitePress

```ts
// .vitepress/config.ts
import { transformerHideLines } from 'shiki-hide-lines'

export default defineConfig({
  markdown: {
    codeTransformers: [
      transformerHideLines({ reveal: true })
    ]
  }
})
```

### Astro

```ts
// astro.config.mjs
import { transformerHideLines } from 'shiki-hide-lines'

export default defineConfig({
  markdown: {
    shikiConfig: {
      transformers: [transformerHideLines()]
    }
  }
})
```

### Nuxt Content

```ts
// nuxt.config.ts
import { transformerHideLines } from 'shiki-hide-lines'

export default defineNuxtConfig({
  content: {
    highlight: {
      transformers: [transformerHideLines()]
    }
  }
})
```

### Direct Shiki

```ts
import { codeToHtml } from 'shiki'
import { transformerHideLines } from 'shiki-hide-lines'

const html = await codeToHtml(code, {
  lang: 'php',
  theme: 'github-dark',
  transformers: [transformerHideLines()]
})
```

## Client Script Setup

Reveal mode requires a small client-side script for the toggle interaction. Choose one:

### Option A — Inline Script (SSG/SSR sites)

```ts
import { getToggleScript } from 'shiki-hide-lines'

// Inject into your page <head> or layout
const script = `<script>${getToggleScript()}</script>`
```

### Option B — Direct Import (SPA/client-side)

```ts
import { setupHiddenLinesToggle } from 'shiki-hide-lines'

setupHiddenLinesToggle()
```

Both options register click and keyboard (Enter/Space) handlers with full ARIA support.

## API

```ts
function transformerHideLines(options?: HideLinesOptions): ShikiTransformer
```

### Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `reveal` | `boolean` | `false` | Enable reveal mode with clickable placeholders |
| `marker` | `string` | `'hide'` | The marker keyword in `[!code <marker>]` |
| `summaryTemplate` | `string \| (count: number) => string` | `'··· N hidden line(s)'` | Customize collapsed placeholder text. Use `{count}` in strings |
| `expandedSummaryTemplate` | `string \| (count: number) => string` | `'▲ N hidden line(s)'` | Customize expanded placeholder text. Use `{count}` in strings |

### Exports

| Export | Description |
|--------|-------------|
| `transformerHideLines` | The Shiki transformer factory |
| `getToggleScript` | Returns toggle script as a string (for inline injection) |
| `setupHiddenLinesToggle` | Registers click and keyboard handlers (for SPA use) |
| `HideLinesOptions` | TypeScript options interface |

## CSS Classes

| Class | Element | Description |
|-------|---------|-------------|
| `has-hidden-lines` | `<pre>` | Code block contains hidden lines |
| `hidden-line` | `<span class="line">` | A hidden line |
| `hidden-lines-summary` | `<span>` | Clickable placeholder (`role="button"`) |
| `hidden-lines-revealed` | `<pre>` | Toggled to expanded state |
| `hidden-lines-collapsed-text` | `<span>` | Text shown when collapsed |
| `hidden-lines-expanded-text` | `<span>` | Text shown when expanded |

## Accessibility

Reveal mode follows the [ARIA disclosure pattern](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/):

- Summary elements have `role="button"` and `tabindex="0"` for keyboard access
- `aria-expanded` reflects the current toggle state
- `aria-label` describes the action (e.g. "Toggle 3 hidden lines")
- Enter and Space keys trigger the toggle
- Focus indicator appears on keyboard navigation (`:focus-visible`)

## Supported Languages

The comment marker adapts to the language:

| Marker | Languages |
|--------|-----------|
| `//` | PHP, JavaScript, TypeScript, Go, Rust, Java, C |
| `#` | Python, Ruby, Bash, YAML, Perl, Dockerfile, Elixir |
| `--` | SQL, Lua, Haskell, Elm |
| `%` | LaTeX, TeX, Erlang |
| `;` | Lisp, Scheme, Clojure, ASM |
