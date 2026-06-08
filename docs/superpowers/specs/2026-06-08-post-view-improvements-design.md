# Post View Improvements Design

**Date:** 2026-06-08
**Issues:** #100 (Biggest Word Adaptations), #101 (URL Detection)

---

## Issue #100 — Biggest Word Adaptations

### Problem

The feed highlights and animates the "longest word" in each post. Currently the selection considers all words including `@mentions` and `#hashtags`. These tokens are often long but are not meaningful content words, so selecting one as the focal word produces misleading or visually odd results.

### Design

Before running the longest-word reduce, filter out word elements whose `textContent` starts with `@` or `#`. Use the filtered list for the selection; fall back to the full list if the filtered list is empty (e.g. an all-mention post) so there is always a result.

### Files to Change

- `resources/js/components/feed/PostAnimator.tsx` — line ~296, highlight colour assignment
- `resources/js/lib/animations/templates/arc.ts` — line ~8, arc focal word selection

### No-change scope

- No backend changes.
- No changes to how words are rendered or animated — only which word is *selected* as longest.

---

## Issue #101 — URL Detection for Bare Domains

### Problem

`PostNormalizer.php` detects URLs using `https?://\S+`. Bare domain URLs (e.g. `fosstodon.org/users/foo`, `github.com/aquarion/bloom`) do not have a scheme and are missed. This means they are neither stripped from display text nor extracted for link card generation.

### Design

Add a secondary bare-domain regex alongside the existing scheme-based one:

```
/(?<![.@\w])([a-zA-Z][a-zA-Z0-9-]*(?:\.[a-zA-Z0-9][a-zA-Z0-9-]*)+\/\S+)/
```

**Why this is safe:**
- Requires the match to start with a letter — rules out version strings like `1.2/path`
- Requires at least one dot-separated segment before the slash — rules out single-word paths
- Requires a `/` followed by non-whitespace — rules out bare hostnames (low signal, high false-positive risk)
- Negative lookbehind `(?<![.@\w])` prevents matching mid-word or email-address fragments

**Normalisation:** When a bare-domain URL is extracted for use as a link (e.g. in `extractFirstLink`), prepend `https://` before passing it to `safeUrl()`.

### Functions to Change

Both functions in `app/Services/Feed/PostNormalizer.php`:

- `stripUrls(string $text)` — extend to also strip bare-domain-with-path matches
- `extractFirstLink(string $text)` — extend to also detect bare-domain URLs; prepend `https://` before normalising

`extractFirstLinkFromHtml` does not need changes — Mastodon wraps links in `<a>` tags with full URLs already.

### Edge Cases

- If a bare-domain URL and a scheme URL both appear, the first one in the string wins (existing behaviour for scheme URLs; same ordering for the combined match).
- Trailing punctuation (`.`, `,`, `!`, `)`) should be stripped from bare-domain matches the same way `rtrim($m[0], '.,;!?)>')` currently handles scheme URLs.

---

## Testing

- Unit tests for `PostNormalizer`: add cases for bare-domain URLs with paths in both `stripUrls` and `extractFirstLink`
- Unit/integration tests for `PostAnimator` and `arc`: add cases where the longest token is a mention or hashtag and verify a content word is selected instead
