# EMG FAQ (WordPress plugin)

**Version:** 1.1.2 (see `emg-faq.php` header)

Renders FAQ sections on the front end **only when you use a shortcode**. Global defaults live under **WP Admin → EMG FAQ**. Optional **JSON-LD** (manual or auto `FAQPage`) is output in the footer; CSS loads once in the document head when the main post content contains the shortcode (or on first shortcode render as a fallback).

---

## Installation

1. Copy the `EMG-FAQ` folder into `wp-content/plugins/`.
2. In **Plugins**, activate **EMG FAQ**.
3. Configure **EMG FAQ** in the admin sidebar (dashicon: help).

---

## Admin: **EMG FAQ** settings

| Option | What it does |
|--------|----------------|
| **Default FAQ (Global)** | Used when a shortcode has **no inner content** (self-closing or empty between opening/closing tags). One FAQ per line: `Question \|\| Answer`. |
| **Use custom default FAQ schema (JSON-LD)** | **Checked:** shows the schema textarea and, when valid JSON is saved, the plugin **prefers that global JSON** for schema output (see [Schema behaviour](#schema-behaviour-by-case) below). **Unchecked:** hides the textarea; structured FAQs can still get **auto-generated** `FAQPage` JSON-LD. |
| **Default FAQ Schema (Global JSON-LD)** | Visible only when the checkbox above is checked. Paste **JSON only** (not wrapped in `<script>`—if you paste a full script tag, the plugin strips the wrapper). Supports `{{city}}`, `{City}`, `{city}` for replacement when the shortcode passes `city="…"`. |
| **FAQ Display Mode** | Global default layout: **Accordion** (`<details>`) or **Plain** (question + answer stacked). Can be overridden per shortcode with `mode="…"`. |

### City placeholders (defaults + schema string)

When `city="Houston"` (example) is set on the shortcode:

- `{{city}}` → replaced first (do not break this by replacing `{city}` before `{{city}}`).
- `{City}` → replaced.
- `{city}` → replaced (case-insensitive for `{city}`/`{{city}}` where applicable).

If **no** `city` attribute is passed, placeholders stay **literal** in the output.

---

## Shortcodes

Two names, same behaviour:

- `[emg_faq …]…[/emg_faq]`
- `[emg-faq …]…[/emg-faq]`

**You must close with `[/emg_faq]` or `[/emg-faq]`.**  
Using `[emg_faq]` again at the end **does not close** the shortcode; it opens a new one and breaks parsing.

---

## Shortcode attributes

| Attribute | Required | Values / notes |
|-----------|----------|----------------|
| `city` | No | Plain text city name; drives placeholder replacement in content + global schema string. |
| `mode` | No | `plain` or `accordion`. Overrides the **global** FAQ Display Mode for **this** instance only. Invalid values fall back to `accordion`. |
| `class` | No | Extra CSS classes on the wrapper `<section class="emg-faq-box …">`. Multiple classes: `class="foo bar"`. |
| `title` | No | Optional heading (`<h2 class="emg-faq-title">`) above the FAQ body. Placeholders in the title are replaced when `city` is set. |

### Examples

```text
[emg_faq city="Dallas" mode="plain" class="city-faq" title="Dallas FAQs"]
```

```text
[emg_faq city="Dallas" mode="accordion" class="accordion-faq"]
```

(Self-closing / empty inner → uses **global** `Question || Answer` lines.)

---

## Inner content: three behaviours (by case)

### Case 1 — **No inner content** (default list from settings)

**When:** Opening tag immediately followed by closing tag, or only whitespace between tags.

```text
[emg_faq city="Dallas" mode="accordion"]
[/emg_faq]
```

**Output:** Parses **Default FAQ (Global)** lines (`Question || Answer`).  
**Layout:** `mode` on the shortcode, else global **FAQ Display Mode**.  
**Schema:** See [Schema behaviour](#schema-behaviour-by-case).

---

### Case 2 — **Structured inner HTML** (`<h3>` question + answer block)

**When:** Inner HTML contains at least one `<h3>…</h3>` followed by content until the next `<h3>` or end of shortcode.

```text
[emg_faq city="Dallas" mode="plain"]
<h3>What is portable storage in {{city}}?</h3>
<p>Answer text for Dallas…</p>
[/emg_faq]
```

**Output:** **Only** this inner content is used; **global default FAQ lines are ignored** for this block. Questions are plain text (tags stripped from the heading). Answers allow safe HTML via `wp_kses_post`.  
**Schema:** If the plugin can build valid Q&A pairs, it may output **auto `FAQPage`** or **global manual JSON** depending on settings (see below).  
**Note:** If **Use custom default FAQ schema** is **checked** and saved JSON is **valid and non-empty**, that **global** JSON is used for schema—not rebuilt from this block’s Q&A. That can mismatch visible FAQs; keep manual JSON in sync or uncheck manual schema to prefer auto from items.

---

### Case 3 — **Freeform inner content** (no `<h3>` pairs detected)

**When:** There is non-empty inner content, but it does **not** match the `<h3>` + block pattern (e.g. plain lines, or other tags only).

```text
[emg_faq city="Dallas" mode="plain"]
Line one {{city}}
Line two
[/emg_faq]
```

**Output:** The inner block is printed inside `.emg-faq-freeform-body` after `city` replacement and `wp_kses_post`. If there is no block-level HTML (`p`, `div`, `h1–h6`, etc.), **wpautop** wraps lines into paragraphs.  
**Schema:** **No auto `FAQPage`** from this block. JSON-LD is output **only** if **Use custom default FAQ schema** is **checked** **and** the saved global JSON is **valid** (same global string for the request, with `city` replaced).

---

## Schema behaviour (by case)

Summary for **each shortcode instance** that actually outputs a section:

| Manual schema checkbox | Global JSON in settings | Shortcode has structured Q&A items? | Resulting JSON-LD |
|-------------------------|-------------------------|-------------------------------------|---------------------|
| Unchecked | (ignored for “use manual”) | Yes | **Auto `FAQPage`** built from **that** instance’s items. |
| Unchecked | — | No (freeform inner) | **None** (no auto FAQPage for freeform). |
| Checked | Valid + non-empty | Yes or no (if instance still queues schema) | **Global manual JSON** (with `city` replacement). **Not** rebuilt from visible Q&A unless global JSON is empty/invalid. |
| Checked | Empty or invalid | Yes | **Fallback:** auto **`FAQPage`** from that instance’s items. |
| Checked | Empty or invalid | No (freeform) | **None** (unless you later add valid global JSON). |

**Duplicate scripts:** Identical JSON strings are deduplicated (one `<script type="application/ld+json">` per unique payload).

**Where it prints:** Queued JSON-LD is printed in **`wp_footer`** (priority `1`), which is valid for Google and keeps markup out of each FAQ box.

---

## Front-end assets

- **CSS:** Registered as `emg-faq-frontend`, inlined once. On singular posts whose content contains the shortcode, styles are enqueued early so they usually appear in **`<head>`**. If the shortcode appears only outside that detection (e.g. some widget flows), styles may load on first shortcode render (WordPress may print them later).
- **Accordion JS:** If **any** instance uses `mode="accordion"` (or global accordion with accordion items), one script `#emg-faq-accordion` runs once in **`wp_footer`** (priority `5`).

---

## Gutenberg

Use a **Shortcode** block (or Classic block) and paste the shortcode, including **closing** tag and inner HTML if needed.

---

## Troubleshooting

| Issue | Likely cause |
|-------|----------------|
| Nothing shows | Empty global FAQ and empty inner content; or inner content invalid. |
| Inner text not showing | Wrong closing tag (`[emg_faq]` instead of `[/emg_faq]`). |
| Schema missing | Freeform inner + manual schema off; or manual on but invalid/empty JSON; or no items for auto FAQPage. |
| Schema doesn’t match visible FAQs | Manual schema **checked** with a **fixed global JSON** that doesn’t describe this page’s questions. |
| `{{city}}` not replaced | Missing `city="…"` on the shortcode. |

---

## File reference

- Main plugin: `emg-faq.php`
- Docs: `README.md` (English), `README-bn.md` (Bangla)

---

## Changelog (high level)

- **1.1.x:** Inner shortcode content (structured / freeform), `mode` / `class` / `city`, manual vs auto schema, single CSS + single accordion script, footer JSON-LD queue.

---

*Author: Hridoy Ahmed / EMG*
