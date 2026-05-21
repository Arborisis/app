# Arborisis — Graphic Charter & Design System

> **Version**: 1.0  
> **Date**: 2026-05-05  
> **Platform**: Laravel 12 + Inertia.js + Vue 3 + Tailwind CSS  
> **Theme**: Dark immersive — Organic Editorial

---

## 1. Brand Identity

### 1.1 Philosophy

Arborisis is a premium social platform dedicated to nature field recording. The interface must evoke the feeling of stepping into a quiet forest at dusk — immersive, respectful, contemplative. Every pixel should honor the sounds that the platform hosts.

**Design mantra**: *"Silence is not empty. It is full of answers."*

### 1.2 Mood & Tone

| Attribute | Value |
|-----------|-------|
| **Vibe** | Dark, warm, immersive, contemplative |
| **Metaphor** | A vintage recording studio hidden in a moss-covered cabin |
| **Density** | Generous negative space — sounds need room to breathe |
| **Motion** | Subtle, organic, never jarring |
| **Texture** | Analog warmth meets digital precision |

### 1.3 Logo Usage

- **Primary**: SVG wordmark "Arborisis" in Cormorant 600, with leaf motif
- **Minimum size**: 32px height on desktop, 24px on mobile
- **Clear space**: Minimum 8px padding around logo
- **On dark**: Use `#F3F0E7` (cream) or `#34D399` (emerald accent)
- **Favicon**: 32x32 and 180x180 touch icon, monochrome emerald on dark

---

## 2. Color System

### 2.1 Primary Palette

| Token | Hex | Usage |
|-------|-----|-------|
| `arbor-night` | `#0B1220` | Primary background, deep base |
| `arbor-deep` | `#111827` | Card backgrounds, elevated surfaces |
| `arbor-charcoal` | `#1A1F2E` | Secondary surfaces, input backgrounds |
| `arbor-fog` | `#2A3142` | Borders, dividers, subtle elevations |

### 2.2 Nature Palette

| Token | Hex | Usage |
|-------|-----|-------|
| `arbor-moss` | `#4A6741` | Primary actions, brand presence |
| `arbor-moss-light` | `#5A7D4F` | Hover states, gradients |
| `arbor-emerald` | `#34D399` | Success, active states, highlights |
| `arbor-emerald-dark` | `#10B981` | Pressed states |
| `arbor-sage` | `#8FA68E` | Secondary text, muted elements |

### 2.3 Warm Accent Palette

| Token | Hex | Usage |
|-------|-----|-------|
| `arbor-amber` | `#D4A574` | Warm accents, ECHO credits, featured content |
| `arbor-amber-glow` | `#C9842B` | Hover on amber elements |
| `arbor-copper` | `#B87333` | Recording equipment motifs, analog feel |

### 2.4 Text & Surface Palette

| Token | Hex | Usage |
|-------|-----|-------|
| `arbor-cream` | `#F3F0E7` | Primary text on dark |
| `arbor-cream-dark` | `#E8E4D9` | Secondary text, descriptions |
| `arbor-glass` | `rgba(255,255,255,0.05)` | Glass morphism backgrounds |
| `arbor-glass-border` | `rgba(255,255,255,0.1)` | Glass morphism borders |

### 2.5 Semantic Colors

| State | Color | Usage |
|-------|-------|-------|
| Success | `#34D399` | Positive actions, confirmations |
| Warning | `#F59E0B` | Cautions, pending states |
| Error | `#EF4444` | Validation errors, destructive actions |
| Info | `#60A5FA` | Informational messages |

### 2.6 Gradient Patterns

```css
/* Hero glow — ethereal forest light */
.bg-hero-glow {
  background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(74, 103, 65, 0.3), transparent);
}

/* Studio ambiance — warm analog feel */
.bg-studio-gradient {
  background: linear-gradient(180deg, #0B1220 0%, #111827 50%, #0d1117 100%);
}

/* Moss depth — for featured cards */
.bg-moss-depth {
  background: linear-gradient(135deg, rgba(74, 103, 65, 0.2) 0%, rgba(17, 24, 39, 0.8) 100%);
}

/* Amber warmth — for ECHO/Economy sections */
.bg-amber-warmth {
  background: linear-gradient(135deg, rgba(212, 165, 116, 0.15) 0%, rgba(17, 24, 39, 0.9) 100%);
}
```

---

## 3. Typography

### 3.1 Font Families

| Role | Font | Fallback | Usage |
|------|------|----------|-------|
| **Display** | Cormorant | Georgia, serif | Headlines, titles, brand moments |
| **Body** | DM Sans | system-ui, sans-serif | Body text, UI labels, navigation |
| **Mono** | JetBrains Mono | monospace | Code, metadata, technical data |

### 3.2 Type Scale

| Token | Size | Weight | Line-Height | Letter-Spacing | Font |
|-------|------|--------|-------------|----------------|------|
| `text-hero` | 64px / 4rem | 600 | 1.1 | -0.02em | Cormorant |
| `text-h1` | 48px / 3rem | 600 | 1.15 | -0.01em | Cormorant |
| `text-h2` | 36px / 2.25rem | 600 | 1.2 | -0.01em | Cormorant |
| `text-h3` | 28px / 1.75rem | 600 | 1.3 | 0 | Cormorant |
| `text-h4` | 22px / 1.375rem | 600 | 1.4 | 0 | Cormorant |
| `text-h5` | 18px / 1.125rem | 600 | 1.4 | 0 | DM Sans |
| `text-body-lg` | 18px / 1.125rem | 400 | 1.7 | 0 | DM Sans |
| `text-body` | 16px / 1rem | 400 | 1.6 | 0 | DM Sans |
| `text-body-sm` | 14px / 0.875rem | 400 | 1.5 | 0 | DM Sans |
| `text-caption` | 12px / 0.75rem | 500 | 1.4 | 0.02em | DM Sans |
| `text-mono` | 14px / 0.875rem | 400 | 1.4 | 0 | JetBrains Mono |

### 3.3 Responsive Typography

```css
/* Mobile-first scaling */
.text-hero {
  font-size: clamp(2.5rem, 6vw, 4rem);
}
.text-h1 {
  font-size: clamp(2rem, 4vw, 3rem);
}
.text-h2 {
  font-size: clamp(1.75rem, 3vw, 2.25rem);
}
```

### 3.4 Text Colors

| Context | Color | Contrast Ratio |
|---------|-------|----------------|
| Primary text on dark | `arbor-cream` (#F3F0E7) | 15.2:1 |
| Secondary text on dark | `arbor-sage` (#8FA68E) | 7.8:1 |
| Muted text on dark | `arbor-sage` at 60% opacity | 4.7:1 |
| Accent text | `arbor-emerald` (#34D399) | 10.1:1 |
| Warm accent | `arbor-amber` (#D4A574) | 8.9:1 |

---

## 4. Spacing System

### 4.1 Base Unit

Base unit: **4px**

| Token | Value | Usage |
|-------|-------|-------|
| `space-1` | 4px | Tight gaps, icon padding |
| `space-2` | 8px | Small gaps, button padding-y |
| `space-3` | 12px | Component internal spacing |
| `space-4` | 16px | Standard gap, card padding |
| `space-5` | 20px | Section small gaps |
| `space-6` | 24px | Component separation |
| `space-8` | 32px | Section padding |
| `space-10` | 40px | Large section gaps |
| `space-12` | 48px | Page section separation |
| `space-16` | 64px | Major section breaks |
| `space-20` | 80px | Hero spacing |
| `space-24` | 96px | Page-level padding |

### 4.2 Container Widths

| Token | Max-Width | Usage |
|-------|-----------|-------|
| `container-sm` | 640px | Narrow content, forms |
| `container-md` | 768px | Medium content |
| `container-lg` | 1024px | Standard pages |
| `container-xl` | 1280px | Wide layouts |
| `container-2xl` | 1536px | Full immersive |

### 4.3 Border Radius

| Token | Value | Usage |
|-------|-------|-------|
| `radius-sm` | 6px | Small buttons, tags |
| `radius-md` | 10px | Inputs, small cards |
| `radius-lg` | 14px | Cards, modals |
| `radius-xl` | 18px | Large cards, panels |
| `radius-2xl` | 24px | Feature cards |
| `radius-full` | 9999px | Pills, avatars |

---

## 5. Layout & Grid

### 5.1 Grid System

```
Mobile-first 12-column grid

Breakpoints:
- sm: 640px
- md: 768px
- lg: 1024px
- xl: 1280px
- 2xl: 1536px

Gutter: 24px (mobile) → 32px (desktop)
Page padding: 16px (mobile) → 24px (tablet) → 32px (desktop)
```

### 5.2 Z-Index Scale

| Layer | Z-Index | Usage |
|-------|---------|-------|
| Background | -10 | Decorative elements |
| Content | 0 | Default content |
| Elevated | 10 | Cards, dropdowns |
| Sticky | 20 | Sticky headers |
| Overlay | 30 | Backdrops |
| Modal | 40 | Modals, dialogs |
| Toast | 50 | Notifications |
| Tooltip | 60 | Tooltips |

### 5.3 Navigation Structure

**Header (Fixed)**
```
[Logo] [Nav Links: Explorer | Carte | Créateurs] [Search] [User Menu]
Height: 64px
Background: arbor-night/80 + backdrop-blur-md
Border-bottom: 1px solid arbor-glass-border
```

**Footer**
```
4-column grid on desktop, stacked on mobile
Background: arbor-deep
Border-top: 1px solid arbor-glass-border
Padding: 48px vertical
```

---

## 6. Component Library

### 6.1 Buttons

#### Primary Button
```
Background: arbor-moss
Text: white
Border-radius: radius-xl (14px)
Padding: 12px 24px
Font: DM Sans 500, 14px
Hover: bg-arbor-moss-light, shadow-lg shadow-arbor-moss/20
Transition: all 300ms ease
Focus: ring-2 ring-arbor-emerald ring-offset-2 ring-offset-arbor-night
```

#### Secondary Button
```
Background: transparent
Border: 1px solid arbor-glass-border
Text: arbor-cream
Border-radius: radius-xl
Padding: 12px 24px
Hover: border-arbor-sage, bg-arbor-glass
```

#### Ghost Button
```
Background: transparent
Text: arbor-sage
Hover: text-arbor-cream
No border
```

#### Amber Button (ECHO/Featured)
```
Background: arbor-amber/20
Border: 1px solid arbor-amber/30
Text: arbor-amber
Hover: bg-arbor-amber/30
```

### 6.2 Cards

#### Glass Card (Default)
```
Background: arbor-glass
Backdrop-filter: blur(12px)
Border: 1px solid arbor-glass-border
Border-radius: radius-2xl (24px)
Padding: 24px
Hover: bg-arbor-glass/50, subtle scale(1.01)
Transition: all 300ms ease
```

#### Sound Card
```
Aspect ratio: 16/9 image + content area
Image: object-cover, rounded-top radius-2xl
Overlay: gradient from transparent to arbor-night/80 at bottom
Play button: centered, 56px circle, bg-arbor-emerald/90
Content padding: 20px
Hover: image scale(1.05), overlay opacity increase
```

#### Stat Card (Tape Reel Style)
```
Background: arbor-charcoal
Border: 1px solid arbor-fog
Border-radius: radius-xl
Padding: 24px
Top border: 3px solid gradient (arbor-emerald → arbor-moss)
Icon: 40px circle with tinted background
```

### 6.3 Forms

#### Text Input
```
Background: arbor-charcoal
Border: 1px solid arbor-fog
Border-radius: radius-lg
Padding: 12px 16px
Text: arbor-cream
Placeholder: arbor-sage at 50%
Focus: border-arbor-emerald, ring-2 ring-arbor-emerald/20
Transition: all 200ms
```

#### Select/Dropdown
```
Same as text input
Dropdown: arbor-deep background, arbor-glass-border
Option hover: arbor-glass
```

#### Textarea
```
Same as text input
Min-height: 120px
Resize: vertical
```

### 6.4 Audio Player

#### Mini Player (Persistent)
```
Fixed bottom: 0
Height: 72px
Background: arbor-deep/95 + backdrop-blur-lg
Border-top: 1px solid arbor-glass-border
Left: waveform visualization
Center: track info + progress bar
Right: controls (play, volume, queue)
Progress bar: 4px height, bg-arbor-fog, fill: arbor-emerald
```

#### Full Player
```
Background: arbor-night
Centered layout
Large cover art: 320px square, radius-2xl
Waveform visualization: canvas-based, arbor-emerald
Controls: 64px play button, prev/next 40px
```

### 6.5 Modals

```
Backdrop: arbor-night/80 + backdrop-blur-sm
Panel: arbor-deep, radius-2xl
Max-width: 520px (default), 720px (large)
Padding: 32px
Close button: top-right, arbor-sage hover
Animation: fade-in + scale from 0.95
```

### 6.6 Badges & Tags

```
Category badge:
- Padding: 6px 12px
- Radius: radius-sm
- Font: DM Sans 500, 12px
- Background: tint of category color at 15%
- Text: category color

Status badge:
- Same sizing
- Dot + text pattern
- Colors match semantic palette
```

### 6.7 Data Display

#### Waveform Visualization
```
Container: full-width, 48px height
Bars: 3px width, 2px gap
Colors: arbor-emerald (played), arbor-fog (unplayed)
Animation: bars scaleY with audio frequency data
Hover: cursor pointer for seeking
```

#### Stats Counter
```
Font: Cormorant 600, 36px
Color: arbor-emerald
Label: DM Sans 400, 12px, arbor-sage, uppercase
Animation: count-up on viewport entry
```

---

## 7. Animations & Interactions

### 7.1 Motion Principles

1. **Purposeful**: Every animation guides attention or provides feedback
2. **Organic**: Easing curves mimic natural movement (ease-out for entrances)
3. **Restrained**: Animations are subtle, never distracting from content
4. **Performant**: CSS transforms and opacity only, avoid layout triggers

### 7.2 Easing Tokens

| Name | Value | Usage |
|------|-------|-------|
| `ease-smooth` | `cubic-bezier(0.4, 0, 0.2, 1)` | Standard transitions |
| `ease-enter` | `cubic-bezier(0, 0, 0.2, 1)` | Elements entering viewport |
| `ease-exit` | `cubic-bezier(0.4, 0, 1, 1)` | Elements leaving |
| `ease-bounce` | `cubic-bezier(0.34, 1.56, 0.64, 1)` | Playful interactions |
| `ease-dramatic` | `cubic-bezier(0.87, 0, 0.13, 1)` | Hero reveals |

### 7.3 Animation Tokens

| Name | Duration | Properties |
|------|----------|------------|
| `duration-instant` | 100ms | Hover color changes |
| `duration-fast` | 200ms | Button states, focus rings |
| `duration-normal` | 300ms | Card hovers, dropdowns |
| `duration-slow` | 500ms | Page transitions, modals |
| `duration-dramatic` | 800ms | Hero reveals, major transitions |

### 7.4 Key Animations

#### Page Entrance (Stagger)
```css
@keyframes staggerFadeIn {
  from {
    opacity: 0;
    transform: translateY(24px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
/* Stagger children with 0.08s delay increments */
```

#### Waveform Bars
```css
@keyframes wave {
  0%, 100% { transform: scaleY(0.3); }
  50% { transform: scaleY(1); }
}
/* Each bar: animation-delay = index * 0.1s */
```

#### Glow Pulse
```css
@keyframes glowPulse {
  0%, 100% { opacity: 0.4; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(1.05); }
}
```

#### Tape Reel Spin
```css
@keyframes reelSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
/* Duration: 8s linear infinite */
```

#### Scroll Reveal
```javascript
// IntersectionObserver-based
// Elements animate when 20% visible
// One-shot (don't re-animate on scroll back)
```

### 7.5 Micro-interactions

| Element | Trigger | Effect | Duration |
|---------|---------|--------|----------|
| Button | Hover | Scale 1.02, shadow increase | 200ms |
| Button | Active | Scale 0.98 | 100ms |
| Card | Hover | TranslateY(-4px), shadow | 300ms |
| Link | Hover | Color shift + underline slide | 200ms |
| Input | Focus | Border color + subtle glow | 200ms |
| Play button | Hover | Scale 1.1, glow pulse | 200ms |
| Like | Click | Heart scale + particle burst | 400ms |
| Nav link | Active | Underline slide from left | 200ms |

---

## 8. Page Templates

### 8.1 Landing Page

```
[Navigation — Fixed]
[Hero — Full viewport]
  - Animated ambient background (subtle particles + gradient)
  - Centered: Badge, Headline, Subheadline, CTAs
  - Scroll indicator (animated bounce)
[Stats Bar — Full width, border-y]
[Features — 3-column grid]
[Map Preview — 2-column: text + visual]
[ECHO Section — Centered card]
[Mission — Centered text]
[CTA — Final conversion]
[Footer]
```

### 8.2 Sound Explorer (Index)

```
[Navigation]
[Page Header]
  - Title + description
  - Filter chips (horizontal scroll on mobile)
[Sound Grid]
  - Responsive: 1 col (mobile) → 2 (sm) → 3 (lg) → 4 (xl)
  - Gap: 24px
[Empty State — Centered illustration + CTA]
[Footer]
```

### 8.3 Sound Detail (Show)

```
[Navigation]
[Breadcrumb / Back link]
[Main Grid — 2 columns (lg)]
  Left (2/3):
    - Cover image (16:9)
    - Audio player (glass card)
    - Action buttons (like, share, report)
    - Description
    - Metadata grid
    - Comments section
  Right (1/3):
    - Creator card
    - Tags
    - Stats
    - Related sounds
[Footer]
```

### 8.4 Creator Dashboard

```
[Navigation]
[Hero Stats — Full width]
  - Welcome message
  - 4 stat cards (tape reel style)
[Content Grid — 2 columns]
  Left (2/3):
    - Recent Sounds (horizontal scroll)
    - Activity Feed
  Right (1/3):
    - Quick Actions
    - ECHO Balance
    - Profile completion
[Footer]
```

### 8.5 Map Explorer

```
[Navigation]
[Full-viewport Map]
  - Leaflet with custom dark theme
  - Floating search/filter panel (top-left)
  - Mini player when sound selected
[Footer — Minimal, overlay]
```

---

## 9. Accessibility Standards

### 9.1 WCAG 2.1 AA Compliance

| Requirement | Implementation |
|-------------|----------------|
| **Contrast** | All text meets 4.5:1 minimum (7:1 for large text) |
| **Focus** | Visible focus rings on all interactive elements |
| **Keyboard** | Full keyboard navigation support |
| **ARIA** | Proper labels, roles, and states |
| **Motion** | `prefers-reduced-motion` respected |
| **Semantics** | Proper HTML5 landmark elements |

### 9.2 Focus States

```
All interactive elements:
- Outline: 2px solid arbor-emerald
- Outline-offset: 2px
- Border-radius: inherit
```

### 9.3 Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

### 9.4 Screen Reader Support

- All images have descriptive alt text
- Icons have aria-labels or aria-hidden
- Live regions for dynamic content updates
- Skip-to-content link

---

## 10. Responsive Breakpoints

| Name | Width | Target |
|------|-------|--------|
| `xs` | < 640px | Mobile phones |
| `sm` | 640px | Large phones |
| `md` | 768px | Tablets |
| `lg` | 1024px | Small laptops |
| `xl` | 1280px | Desktops |
| `2xl` | 1536px | Large screens |

### Mobile Adaptations

- Navigation collapses to hamburger menu
- Grid columns reduce (4→3→2→1)
- Typography scales down (clamp() values)
- Horizontal scroll for filter chips
- Full-width cards
- Touch targets minimum 44x44px

---

## 11. Assets & Media

### 11.1 Image Guidelines

- **Photography**: Natural, muted tones. No oversaturated images. Prefer twilight/golden hour lighting.
- **Icons**: Lucide icon set (consistent 1.5px stroke width)
- **Illustrations**: Minimal, line-art style in arbor-sage or arbor-emerald
- **Cover images**: 16:9 ratio, minimum 1280x720px

### 11.2 Icon Usage

```
Size mapping:
- xs: 14px (inline text)
- sm: 16px (buttons, lists)
- md: 20px (navigation)
- lg: 24px (feature icons)
- xl: 32px (empty states)
- 2xl: 48px (hero sections)

Stroke width: 1.5px (default), 2px (emphasis), 1px (subtle)
```

### 11.3 Audio Visualization

- Waveform: Generated from audio file on backend
- Color: arbor-emerald on dark background
- Style: Smooth curves (not blocky bars)
- Animation: Real-time frequency response

---

## 12. Technical Implementation

### 12.1 CSS Architecture

```
app.css
├── @layer base
│   ├── Font imports
│   ├── HTML/Body resets
│   ├── Scrollbar styling
│   └── Selection colors
├── @layer components
│   ├── .glass-card
│   ├── .btn-primary
│   ├── .btn-secondary
│   ├── .btn-amber
│   └── .section-padding
└── @layer utilities
    └── Custom utilities
```

### 12.2 Tailwind Configuration

```javascript
// tailwind.config.js key settings
{
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['DM Sans', 'system-ui', 'sans-serif'],
        display: ['Cormorant', 'Georgia', 'serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      colors: { /* arbor palette */ },
      animation: { /* custom animations */ },
    },
  },
}
```

### 12.3 Vue Component Patterns

```vue
<!-- Standard page structure -->
<template>
  <Head title="Page Title" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-arbor-night">
      <!-- Ambient background -->
      <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="bg-hero-glow" />
      </div>
      
      <!-- Content -->
      <div class="relative z-10">
        <section class="section-padding py-24">
          <div class="max-w-7xl mx-auto">
            <!-- Page content -->
          </div>
        </section>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
```

### 12.4 Performance Guidelines

- Lazy load images below fold
- Use `will-change` sparingly (only during animation)
- Prefer CSS animations over JS where possible
- Use `transform` and `opacity` for animations
- Implement virtual scrolling for long lists
- Compress images to WebP format

### 12.5 SEO Requirements

- Semantic HTML (header, main, section, article, nav, footer)
- Proper heading hierarchy (h1 → h2 → h3)
- Meta descriptions for all pages
- Open Graph tags for social sharing
- Structured data (JSON-LD) for sounds and creators
- Canonical URLs

---

## 13. ECHO Economy Visual Language

### 13.1 ECHO Branding

ECHO is the platform's internal credit system. Visual treatment:

- **Icon**: Stylized "E" with concentric circles (like ripples)
- **Color**: arbor-amber (#D4A574) — warm, valuable, human
- **Typography**: JetBrains Mono for ECHO amounts (technical precision)

### 13.2 Transaction States

| State | Visual |
|-------|--------|
| Pending | Spinner + arbor-amber |
| Success | Checkmark + arbor-emerald |
| Failed | X + error red |
| Processing | Animated dots |

### 13.3 Balance Display

```
[ECHO Icon] 1,240 ECHO
Font: JetBrains Mono 500
Color: arbor-amber
Background: arbor-amber/10 + border arbor-amber/20
```

---

## 14. Implementation Checklist

### Phase 1: Foundation
- [x] Update Tailwind config with design tokens
- [x] Update font imports (Cormorant, DM Sans, JetBrains Mono)
- [x] Implement base CSS (scrollbar, selection, resets)
- [x] Build component utilities (glass-card, buttons)

### Phase 2: Global Elements
- [ ] Redesign Navigation component
- [ ] Redesign Footer component
- [ ] Implement mini audio player
- [ ] Build modal system

### Phase 3: Pages
- [ ] Landing page redesign
- [ ] Dashboard (Creator Studio)
- [ ] Sound Explorer (Index)
- [ ] Sound Detail (Show)
- [ ] Map Explorer

### Phase 4: Polish
- [ ] Add scroll-triggered animations
- [ ] Implement waveform visualizations
- [ ] Add loading states and skeletons
- [ ] Test responsive breakpoints
- [ ] Accessibility audit
- [ ] Performance optimization

---

## 15. File Structure

```
resources/
├── css/
│   └── app.css                 # Global styles, design tokens
├── js/
│   ├── Components/
│   │   ├── UI/                 # Reusable UI primitives
│   │   │   ├── Button.vue
│   │   │   ├── Card.vue
│   │   │   ├── Input.vue
│   │   │   ├── Modal.vue
│   │   │   ├── Badge.vue
│   │   │   └── Skeleton.vue
│   │   ├── Audio/
│   │   │   ├── Waveform.vue
│   │   │   ├── MiniPlayer.vue
│   │   │   └── FullPlayer.vue
│   │   ├── Navigation/
│   │   │   ├── MainNav.vue
│   │   │   ├── Footer.vue
│   │   │   └── Breadcrumbs.vue
│   │   └── Social/
│   │       ├── LikeButton.vue
│   │       ├── FollowButton.vue
│   │       ├── CommentSection.vue
│   │       └── ShareButton.vue
│   ├── Composables/
│   │   ├── useAudioPlayer.js
│   │   ├── useIntersectionObserver.js
│   │   ├── useWaveform.js
│   │   └── useEcho.js
│   ├── Pages/
│   │   ├── Landing.vue
│   │   ├── Dashboard.vue
│   │   ├── Sounds/
│   │   │   ├── Index.vue
│   │   │   ├── Show.vue
│   │   │   └── Create.vue
│   │   ├── Map/
│   │   │   └── Index.vue
│   │   ├── Profile/
│   │   │   ├── Show.vue
│   │   │   └── Edit.vue
│   │   └── Auth/
│   └── app.js
└── views/
    └── app.blade.php           # Root template with fonts
```

---

## 16. Changelog

| Version | Date | Changes |
|---------|------|---------|
| 2.0 | 2026-05-17 | Artistic redesign direction: living sound atlas, premium naturalist UI, page-by-page refactor plan, Tailwind tokens |
| 1.0 | 2026-05-05 | Initial design system creation |

---

*This document is a living guide. Update it as the design system evolves.*

---

# Arborisis Design System 2.0 — Le Vivant S'Ecoute

> **Status**: frontend implementation brief
> **Date**: 2026-05-17
> **Goal**: transform Arborisis into a memorable premium field-recording platform: living sound map, poetic atlas, naturalist lab, and community of explorers.

## 1. Vision Design

Arborisis is no longer framed as a conventional social platform. It is a **living atlas of the audible world**.

Core idea: **Le vivant s'écoute.**

Every published sound should feel like:

- a living trace from a place
- a sound postcard
- a naturalist field note
- a scientific observation
- a time capsule from a territory

The interface should feel quiet, deep, editorial, and precise. The user is not browsing content; they are entering a contemporary naturalist archive.

## 2. Fast Audit Of The Current UI

Current strengths:

- Dark immersive base already exists in `resources/css/app.css`.
- Vue pages already use field-recording concepts: map, sounds, creators, scientific stats, listening points.
- Existing fonts (`Cormorant`, `DM Sans`, `JetBrains Mono`) can support an editorial/scientific identity.
- Existing components such as `SoundCard`, `SoundMap`, `WaveSurfer`, `AudioAnalysisPanel`, gamification drawers, and scientific charts give a strong foundation.

Current weaknesses:

- Too many surfaces rely on the same `glass-card` pattern, making pages feel uniform.
- The palette leans toward emerald/moss and risks "green tech" rather than premium naturalist.
- Hero and CTA language is useful but not yet iconic.
- Scientific screens still read as dashboard blocks instead of a modern naturalist lab.
- Gamification risks standard XP/trophy language instead of an adult exploration journal.
- Map UX is functional but should be the emotional center of the product.

## 3. Artistic Direction

Direction name: **Atlas du Vivant Audible**.

Visual language:

- **Forest black** for depth and silence.
- **Mineral paper** for field notes and scientific context.
- **Lichen gold** for precious objects, badges, and primary actions.
- **Firefly cyan-green** for living traces, pins, audio activity, and focus states.
- **Bark and clay** for grounded warmth.
- **Mist white** for premium editorial readability.

Do not use a flat SaaS grid as the dominant structure. Prefer editorial spreads, floating map panels, field-note cards, scientific plates, and acoustic timelines.

## 4. Inspiration Principles Without Copying

| Reference | What to learn | Arborisis translation |
|-----------|---------------|----------------------|
| Apple | restraint, hierarchy, precision | fewer UI elements, more silence, better typography |
| Spotify | audio emotion, discovery | immersive player and sound-first browsing |
| Airbnb | trust, places, map UX | place cards, approximate location confidence, human hosts |
| National Geographic | exploration and documentary authority | field-note language, scientific evidence, species context |
| AllTrails | terrain and routes | seasonal modes, public listening points, exploration progress |
| Arc | modern polish | soft panels, precise transitions, small premium details |
| Linear | system consistency | durable tokens and predictable component states |
| A24 | atmosphere and memorability | darker cinematic moments, mystery, strong copy |
| Patagonia | nature ethics | calm contribution, privacy, respect for fragile locations |
| Kinfolk / Monocle | editorial calm | spacious layouts, quiet typography, mature imagery |
| NYT Visual Stories | data storytelling | explain scientific data through narrative visual modules |

## 5. Color Tokens

These tokens extend the current palette and preserve backward compatibility with existing `arbor-*` classes.

```js
colors: {
  arbor: {
    forest: '#07110D',
    ink: '#0A0D0B',
    canopy: '#102018',
    night: '#111827',
    moss: '#4A6741',
    sage: '#8FA68E',
    bark: '#6B4F3A',
    clay: '#A66F4E',
    mineral: '#D8D0BD',
    mist: '#F2EFE7',
    lichen: '#D7B46A',
    firefly: '#8FE6C1',
    cyanTrace: '#78D6D6'
  }
}
```

Usage:

- `forest` / `ink`: page background, map chrome, immersive sections.
- `canopy`: panels, navigation, elevated atlas surfaces.
- `mineral` / `mist`: editorial text, field-note paper, light scientific inserts.
- `lichen`: primary CTA, badges, featured sound objects.
- `firefly`: active map pins, focus, living audio traces.
- `bark` / `clay`: secondary warm accents, creator/community modules.

Avoid using `emerald` as the main brand accent on new screens. Keep it for legacy success states or compatibility.

## 6. Typography

Keep the installed fonts for implementation speed:

- `font-display` / Cormorant: large editorial headlines, page titles, sound names.
- `font-sans` / DM Sans: UI labels, paragraphs, navigation.
- `font-mono` / JetBrains Mono: duration, scores, coordinates, API fields, model versions.

Rules:

- Use Cormorant for emotional hierarchy, not every card title.
- Use JetBrains Mono only where data is truly technical.
- Avoid negative letter spacing. Use normal tracking except small uppercase labels.
- Prefer human microcopy: "Observe", "Ecouter", "Documenter", "Lieu approximatif", "Archive vivante".

## 7. Component System

Create or refactor these Vue components:

| Component | Purpose | Implementation target |
|-----------|---------|-----------------------|
| `AtlasNav.vue` | premium navigation with map-first hierarchy | replace parts of `GuestLayout.vue` |
| `EditorialFooter.vue` | magazine-like footer, mission, open data, newsletter | replace footer in `GuestLayout.vue` |
| `SoundArchiveCard.vue` | sound as precious archive object | replace/enhance `Components/SoundCard.vue` |
| `LivingMapPin.vue` | organic map pin with pulse and biome state | integrate into `Components/Map/SoundMap.vue` |
| `ExplorationSidebar.vue` | map search, filters, modes, active sound | extract from `Pages/Map/Index.vue` |
| `FieldNoteBlock.vue` | paper-like note for context and scientific copy | use on sound/science pages |
| `ScientificMetricPlate.vue` | less SaaS stat card | replace `Scientific/StatCard.vue` where possible |
| `NaturalistBadge.vue` | adult badge/medal language | gamification components |
| `TemporalListeningTimeline.vue` | same place across dates/seasons | sound and scientific pages |
| `PoeticEmptyState.vue` | branded empty states | lists, profiles, upload |
| `SoundWaveLoader.vue` | loading state inspired by quiet waveform | global loading/skeleton use |

Base CSS utilities added in `resources/css/app.css`:

- `.atlas-surface`
- `.field-note`
- `.sound-archive-card`
- `.badge-naturalist`
- `.map-pin-living`

## 8. Page Refactors

### 8.1 Home Page (`resources/js/Pages/Landing.vue`)

New structure:

1. Immersive hero: headline **"Le vivant s'écoute."**
2. Subline: "Une carte mondiale de fragments sonores capturés par des naturalistes, artistes et field recorders."
3. Hero includes live map preview or sound atlas preview above the fold.
4. Three entry points: `Explorer la carte`, `Ecouter une archive`, `Publier une observation`.
5. Section "Explorer le vivant par le son": biomes, seasons, time of day.
6. Section "Archives vivantes": selected sounds as field-note cards.
7. Section "Laboratoire naturaliste": scientific stats as narrative modules.
8. Section "Communauté": creators as explorers, not influencers.

Visual changes:

- Reduce generic gradients.
- Use image/texture depth and field-note overlays.
- Make the first viewport feel like entering a world, not a landing page.

### 8.2 Sound Map (`resources/js/Pages/Map/Index.vue`)

The map is the central product.

Modes:

- `Découverte`
- `Espèces`
- `Lieux`
- `Saisons`
- `Archives`

UX:

- Desktop: left exploration sidebar, map full height, active sound card floating near selected pin.
- Mobile: map full screen, bottom sheet filters, persistent mini-player.
- Pins use `.map-pin-living` with biome color variants.
- Add filter groups: biome, species, time of day, season, weather, duration, recording quality.
- Map copy should use "lieu approximatif" where relevant to reinforce privacy.

### 8.3 Sound Detail (`resources/js/Pages/Sounds/Show.vue`)

Treat the sound as both artwork and data.

Layout:

- Top: large title, approximate place, date, season, recordist.
- Main: premium audio player with waveform as central object.
- Secondary: field note with weather, environment, equipment, microphone position.
- Scientific: spectrogram, species detections, analysis confidence.
- Timeline: same listening point on other dates.
- Related: sounds from similar biome, season, or species.

Tone:

- Replace generic metadata lists with "Contexte du lieu", "Signature acoustique", "Espèces probables", "Archives du même lieu".

### 8.4 Scientific Page (`resources/js/Pages/ScientificStats/Index.vue`)

Make it a **modern naturalist lab**, not a SaaS analytics page.

Structure:

- Hero: "Observer la biodiversité par le son."
- Dataset readiness as a scientific plate.
- Tabs remain useful, but labels should feel like research sections.
- Add explanatory field notes next to charts.
- Visualizations should be framed as observations: cycles, density, species, weather context, listening points.
- Keep API docs precise and machine-readable.

### 8.5 Profiles (`resources/js/Pages/Profile/Show.vue`)

Profile should become a digital field journal.

Modules:

- Creator portrait and short field biography.
- Personal sound map.
- Collections by biome/species/season.
- Favorite listening points.
- Naturalist badges.
- Contributions and scientific usefulness.
- "Signature sonore": common environments, recording hours, explored regions.

### 8.6 Gamification

Adult, poetic, naturalist, never mobile-game childish.

Replace generic trophy framing with:

- **Carnets d'exploration**
- **Médailles naturalistes**
- **Collections d'espèces**
- **Quêtes saisonnières**
- **Points publics visités**

Examples:

- "Veilleur d'aube"
- "Archiviste des pluies"
- "Sentinelle des marais"
- "Cartographe des lisières"
- "Oreille des migrations"

Use `NaturalistBadge.vue` and `.badge-naturalist`.

## 9. Micro-Interactions

Required interaction language:

- Hover on sound card: subtle acoustic ripple from the play control.
- Map pin: slow breathing pulse, disabled in `prefers-reduced-motion`.
- Player: waveform breathes while active, calms when paused.
- Badge unlock: soft lichen glint, no confetti.
- Scientific graph reveal: progressive fade/slide, not bounce.
- Search/filter: quiet focus glow in `firefly`.
- Upload steps: field notebook progression with saved draft feedback.
- Empty state: quiet waveform loader and human copy.

Performance rule: no decorative animation should block map interaction, audio controls, upload, or mobile scrolling.

## 10. Mobile-First UX

Mobile priorities:

- Bottom navigation: `Carte`, `Sons`, `Publier`, `Profil`.
- Map opens full screen by default.
- Filters live in bottom sheet with large touch targets.
- Mini-player is persistent and never hides primary map controls.
- Sound page order: player, field context, species, place, comments.
- Upload should be 4 steps max: file, context, place, publish.
- Profile starts with map/collection, not a desktop-style grid.

Accessibility:

- Maintain 44px minimum interactive targets.
- Use `:focus-visible` states with `firefly`.
- Respect `prefers-reduced-motion`.
- Preserve map/list alternative for screen-reader and low-performance users.

## 11. Implementation Plan By Impact

### P0: Foundation

- Add extended Tailwind tokens. Done in `tailwind.config.js`.
- Add reusable CSS utilities. Done in `resources/css/app.css`.
- Keep legacy tokens to avoid breaking current Vue pages.

### P1: Identity Pass

- Update `GuestLayout.vue` navigation and footer copy.
- Replace global primary button feel with lichen/firefly action system.
- Update `Landing.vue` hero headline and sections.

### P2: Core Product

- Refactor `SoundCard.vue` into `SoundArchiveCard.vue`.
- Extract map sidebar to `ExplorationSidebar.vue`.
- Implement `LivingMapPin.vue` or equivalent Leaflet HTML marker using `.map-pin-living`.
- Improve mobile map bottom sheet.

### P3: Deep Pages

- Recompose `Sounds/Show.vue` as archive object + scientific evidence.
- Recompose `ScientificStats/Index.vue` with field-note explanations and scientific plates.
- Upgrade `Profile/Show.vue` into field journal layout.

### P4: Polish

- Naturalist badge system.
- Branded empty/loading states.
- Micro-interaction pass.
- Accessibility and mobile visual QA.

## 12. Concrete UI Examples

Hero copy:

```txt
Le vivant s'écoute.
Une carte mondiale de fragments sonores capturés par des naturalistes, artistes et field recorders.
```

Sound card metadata:

```txt
Archive vivante
Foret mixte, aube humide
03:42 · lieu approximatif · 4 especes probables
```

Scientific field note:

```txt
Note de methode
Les coordonnées publiques sont approximées. Les lieux fragiles sont floutés avant toute visualisation.
```

Empty state:

```txt
Aucune archive dans cette zone.
Essayez une autre saison, ou ouvrez la carte aux lieux voisins.
```

## 13. Prompt-To-Artifact Checklist

| Requirement | Artifact evidence |
|-------------|-------------------|
| Direction artistique | Design System 2.0 sections 1-4 |
| Palette | Tailwind config extended tokens + section 5 |
| Typographie | Section 6 |
| UI components | Section 7 + CSS utilities |
| Sound map | Section 8.2 |
| Home page | Section 8.1 |
| Sound page | Section 8.3 |
| Scientific page | Section 8.4 |
| Profiles | Section 8.5 |
| Gamification | Section 8.6 |
| Mobile | Section 10 |
| Micro-interactions | Section 9 + CSS pin/ripple utilities |
| Design system | Sections 5-7 and 11 |
| Avoid generic SaaS/AI look | Sections 2-4, 8, 9 |
| Directly usable by Laravel/Vue/Tailwind developer | File paths, component names, Tailwind tokens, CSS utilities, implementation phases |
