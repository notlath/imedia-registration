---
name: Vibrant Professionalism
colors:
  surface: '#f8f9fa'
  surface-dim: '#d9dadb'
  surface-bright: '#f8f9fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f5'
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#5a3f47'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#8e6f77'
  outline-variant: '#e2bdc7'
  surface-tint: '#b90064'
  primary: '#b90064'
  on-primary: '#ffffff'
  primary-container: '#e6007e'
  on-primary-container: '#ffffff'
  inverse-primary: '#ffb0c9'
  secondary: '#00658d'
  on-secondary: '#ffffff'
  secondary-container: '#2dbcfe'
  on-secondary-container: '#004866'
  tertiary: '#3e5f7f'
  on-tertiary: '#ffffff'
  tertiary-container: '#577799'
  on-tertiary-container: '#fdfcff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffd9e2'
  primary-fixed-dim: '#ffb0c9'
  on-primary-fixed: '#3e001e'
  on-primary-fixed-variant: '#8e004b'
  secondary-fixed: '#c6e7ff'
  secondary-fixed-dim: '#82cfff'
  on-secondary-fixed: '#001e2d'
  on-secondary-fixed-variant: '#004c6b'
  tertiary-fixed: '#cfe5ff'
  tertiary-fixed-dim: '#a8caef'
  on-tertiary-fixed: '#001d34'
  on-tertiary-fixed-variant: '#274969'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
typography:
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  label-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.05em
  nav-link:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: '500'
    lineHeight: normal
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  container-max: 1200px
  gutter: 1.5rem
  margin-x: 2rem
  stack-sm: 0.75rem
  stack-md: 1.5rem
  stack-lg: 3rem
---

## Brand & Style

This design system is built on a foundation of energetic educational empowerment. It balances a corporate, trustworthy structure with high-energy accents to appeal to career-driven individuals looking for modern skills.

The visual style is **Corporate / Modern** with a touch of **High-Contrast** flair. It utilizes a clean white background to let content breathe, while employing a "vibrant-on-dark" navigation scheme to establish authority. The emotional goal is to feel accessible, efficient, and results-oriented.

## Colors

The palette is driven by high-saturation "action" colors against a deep enterprise foundation.

- **Primary (Magenta):** Used for high-priority calls to action like "Promos" and highlighting key value propositions.
- **Secondary (Cyan):** Used for secondary actions like "Schedule" and to denote technical or digital categories.
- **Tertiary (Deep Navy):** Reserved for the global navigation bar and footer to provide a solid, professional frame for the content.
- **Neutral:** A spectrum of cool grays and off-whites are used for section backgrounds to maintain high legibility and contrast for the vibrant course cards.

## Typography

The typography system uses **Plus Jakarta Sans** for headlines to inject a modern, friendly personality into the brand. **Inter** is used for body text and functional labels to ensure maximum clarity and a "tech-first" aesthetic.

Hierarchies are strictly enforced through weight changes rather than just size. Navigation items in the dark header utilize medium weights for legibility against the navy background.

## Layout & Spacing

The design system utilizes a **Fixed Grid** approach for large screens, centering content within a 1200px max-width container. 

- **Grid:** A 12-column system is used for course listings. On desktop, cards typically span 4 columns (3-up) or 6 columns (2-up) depending on the featured status.
- **Rhythm:** A vertical 8px spacing scale (0.5rem units) ensures consistent gaps between text elements and card components.
- **Responsive Behavior:** On mobile devices, the margins shrink to 1rem and the grid collapses to a single column (12-span) to ensure readability of the detailed course card imagery.

## Elevation & Depth

This system favors **Low-Contrast Outlines** and **Tonal Layers** over heavy shadows. 

- **Surface Tiers:** The main background is white, while section dividers or utility bars use a very light gray (#F8F9FA).
- **Interactive Depth:** Cards use a subtle, 1px light border. Upon hover, a soft, diffused ambient shadow (0px 4px 20px rgba(0,0,0,0.08)) is applied to lift the card, indicating interactivity.
- **Glassmorphism (Specialized):** The "Chat Now" widget and certain overlay badges use a light backdrop blur with semi-transparent gradients to stand out from the busy graphical backgrounds of the course cards.

## Shapes

The shape language is consistently **Rounded**. This softens the technical nature of the content and makes the interface feel more approachable.

- **Standard Elements:** Buttons and small cards use a 0.5rem radius.
- **Large Cards:** Course feature cards use a more pronounced 1rem radius to frame their rich imagery.
- **Pill Shapes:** Primary action buttons in the header ("Promos", "Schedule") utilize a full pill-shape (100px radius) to differentiate them from standard secondary UI elements.

## Components

### Buttons
- **Primary:** Pill-shaped, magenta background, white text. No border.
- **Secondary:** Pill-shaped, cyan background, white text. 
- **Ghost:** White text with no background, used for secondary navigation items.

### Course Cards
- **Structure:** A container with 1rem rounding, containing high-impact photography/graphics.
- **Overlays:** Information like "Save up to P1,500" should be displayed on a dark, high-contrast badge within the card to ensure legibility over varying images.

### Navigation
- **Top Bar:** White background with logo and primary CTA buttons.
- **Main Nav:** Tertiary (Navy) background with white Inter-based text. Active states are indicated by a change to the secondary cyan color.

### Inputs & Fields
- Should use a 0.5rem radius with a subtle 1px gray border, focusing on a clean, professional appearance.