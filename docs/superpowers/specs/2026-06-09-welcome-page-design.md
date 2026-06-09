# Welcome Page Design

**Date:** 2026-06-09
**Status:** Approved

## Summary

Replace the stock Laravel boilerplate `welcome.tsx` with a real landing page for Bloom. The page IS the app experience — it renders the full-screen feed UI in demo mode, cycling through public posts, with Bloom branding and sign-up/login CTAs overlaid at the bottom.

## Routing

- `GET /` serves `welcome.tsx` for unauthenticated visitors via a new `WelcomeController`
- Authenticated users hitting `/` are redirected to the feed (unchanged)
- The existing redirect-to-login behaviour for guests is removed from the home route

## Backend: WelcomeController

A new controller fetches public Mastodon posts, normalises them, and passes them to the page as props.

**Source:** `GET https://{instance}/api/v1/timelines/public?limit=10&only_media=false` — no authentication required. Instance is configurable (default: `mastodon.social`) via `config/services.php` or `config/feed.php`.

**Caching strategy:**
- Cache key: `welcome.posts`, TTL: 6 hours
- On cache miss: fetch from public timeline, normalise through `PostNormalizer`, cache result
- On fetch failure: serve stale cache regardless of age (log a warning); do not surface errors to the visitor
- Cache completely cold AND fetch fails: fall back to 3–4 hardcoded example posts baked into the controller
- Only posts with a non-empty body are kept (filters out media-only posts)

**Props passed to `welcome.tsx`:**
- `initialPosts: Post[]` — 5–10 normalised posts
- `canRegister: bool` — whether registration is open (existing prop, unchanged behaviour)

## Frontend: welcome.tsx

The page is `feed.tsx` in demo mode. Reuse all feed components and animation logic; change only the chrome layer.

### What is reused verbatim
- `PostBackground` — blurred/saturated background layer
- `PostContent` — post body, media, reply context, hashtags
- `SourceBadge` — platform indicator (Mastodon / Bluesky)
- `Attribution` — author name, avatar, handle
- `ProgressBar` — thin progress bar at top showing time until next post
- `useAutoAdvance` — timer hook driving auto-advance
- GSAP `handleAdvance` animation — full zoom/blur crossfade transition (identical to feed)

### What changes
- **No `useFeedQueue`** — welcome page uses a local looping queue instead (no cursor, no API fetching)
- **Looping:** when the queue is exhausted, reset to `initialPosts` so posts cycle indefinitely
- **Chrome layer — removed:** dashboard link, wake-lock button, pause/play button, debug panel
- **Chrome layer — added:** Bloom CTA panel pinned at the bottom, overlaid above the attribution:
  - App name: `<AppLogoIcon>` + "Bloom" wordmark (small, muted — reuses the existing `AppLogo` component)
  - Tagline: "Social media. Without the scroll."
  - Sub-tagline: "Full-screen · Mastodon & Bluesky · No algorithm"
  - **Sign up** button (primary, white) — links to `register()`; hidden if `canRegister` is false
  - **Log in** button (secondary, ghost) — links to `login()`

### Layout
```
┌────────────────────────────────┐
│ [progress bar — top edge]      │
│                                │
│  Post body text (upper area)   │
│                                │
│  ↕ gradient fade to black      │
│                                │
│  Author avatar · name · handle │
│  ──────────────────────────    │
│  [AppLogoIcon] Bloom           │
│  Social media.                 │
│  Without the scroll.           │
│  Full-screen · Mastodon &      │
│  Bluesky · No algorithm        │
│                                │
│  [Sign up]      [Log in]       │
└────────────────────────────────┘
```

## What is NOT in scope
- Any feature flags or A/B testing
- Screenshot carousel or static image gallery
- Mobile-specific layout changes (the feed is already responsive)
- Analytics or conversion tracking
