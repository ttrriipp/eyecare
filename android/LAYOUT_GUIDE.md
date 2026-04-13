# EyeCare Android — Layout & Design Guide

> **Purpose:** A single-file reference for the design system, theme, color palette, typography, spacing, component patterns, navigation architecture, and drawable conventions used across the EyeCare Android app. Read this instead of scanning dozens of XML files.

---

## Table of Contents

1. [Technology & Dependencies](#1-technology--dependencies)
2. [Theme & Base Style](#2-theme--base-style)
3. [Color Palette](#3-color-palette)
4. [Typography Scale](#4-typography-scale)
5. [Spacing & Dimension Tokens](#5-spacing--dimension-tokens)
6. [Navigation Architecture](#6-navigation-architecture)
7. [Screen Layout Patterns](#7-screen-layout-patterns)
8. [Component Catalogue](#8-component-catalogue)
9. [Drawable & Shape Library](#9-drawable--shape-library)
10. [Animations](#10-animations)
11. [Icon Library](#11-icon-library)
12. [Color State Lists](#12-color-state-lists)
13. [String Conventions](#13-string-conventions)
14. [Quick-Reference: Layout File Map](#14-quick-reference-layout-file-map)

---

## 1. Technology & Dependencies

| Area | Library / Tool |
|---|---|
| Language | Kotlin (JVM 17) |
| Min SDK / Target | 24 / 36 |
| Design System | **Material 3** (`Theme.Material3.DayNight.NoActionBar`) |
| View Binding | Enabled via `buildFeatures.viewBinding = true` |
| DI | Hilt (KSP) |
| Navigation | Jetpack Navigation Component (fragment-based, XML nav-graphs) |
| Networking | Retrofit + OkHttp + Gson |
| Images | Glide |
| Async | Kotlin Coroutines |
| Lists | RecyclerView (from Material) |
| Image Carousel | ViewPager2 1.1.0 |
| Pull-to-Refresh | SwipeRefreshLayout 1.1.0 |
| Persistence | DataStore Preferences |

---

## 2. Theme & Base Style

### Light Theme (`values/themes.xml`)

```xml
<style name="Base.Theme.OpticalSystem" parent="Theme.Material3.DayNight.NoActionBar">
    <item name="colorControlActivated">@color/login_primary</item>  <!-- #0284C7 -->
</style>
<style name="Theme.OpticalSystem" parent="Base.Theme.OpticalSystem" />
```

### Dark / Night Theme (`values-night/themes.xml`)

Identical parent and overrides — currently mirrors the light theme with the same `colorControlActivated`.

### Key Implications

- **No ActionBar** — all toolbar/header UI is custom per screen.
- Material 3 default colors apply unless overridden explicitly in layouts.
- `colorControlActivated` is Sky-600 (`#0284C7`) — used for text cursor, checkbox/switch active states.

---

## 3. Color Palette

### 3.1 Core Palette

| Token | Hex | Usage |
|---|---|---|
| `primary` | `#3D6EA1` | App-wide primary (headers, buttons, badges, price) |
| `primary_dark` | `#2F5E8C` | Pressed/dark variant |
| `primary_light` | `#5B8FBF` | Light accent variant |
| `on_primary` | `#FFFFFF` | Text/icons on primary-colored surfaces |
| `surface` | `#FFFFFF` | Card backgrounds, input fields |
| `background` | `#F5F7FA` | Page/screen background (cool gray) |
| `text_primary` | `#1F2A37` | Headings, body text |
| `text_secondary` | `#7A8A9A` | Hints, subtitles, timestamps |
| `divider` | `#E5E9EF` | Separators, card strokes |

### 3.2 Login / Auth Screen

| Token | Hex | Notes |
|---|---|---|
| `login_primary` | `#0284C7` | Sky-600, CTA buttons, links |
| `login_primary_dark` | `#0369A1` | Pressed state |
| `login_link` | `#0284C7` | "Sign Up", "Forgot password?" |
| `login_hint` | `#7A8A9A` | Hint text, subtitles |
| `login_surface` | `#FFFFFF` | Card background |
| `login_outline` | `#D7DDE4` | TextInputLayout stroke |
| `login_title` | `#1F2A37` | Heading text |

### 3.3 Bottom Navigation

| Token | Hex |
|---|---|
| `bottom_nav_bg` | `#FFFFFF` |
| `bottom_nav_border` | `#E0E0E0` |
| `bottom_nav_active` | `#1976D2` |
| `bottom_nav_inactive` | `#757575` |
| `nav_badge_background` | `#D32F2F` |

### 3.4 Product UI

| Token | Hex | Usage |
|---|---|---|
| `star_color` | `#F59E0B` | Amber — rating stars |
| `ar_badge_bg` | `#3D6EA1` | AR Try-On badge background |
| `spec_row_alt` | `#F8F9FA` | Alternating spec row |
| `price_color` | `#3D6EA1` | Product prices |
| `category_label_color` | `#3D6EA1` | Category text labels |

### 3.5 Order Status Colors

Each status has a **foreground** + **background** (10% opacity) pair.

| Status | Foreground | Background (10% α) |
|---|---|---|
| Pending | `#F59E0B` | `#1AF59E0B` |
| Confirmed | `#3B82F6` | `#1A3B82F6` |
| Ready | `#14B8A6` | `#1A14B8A6` |
| Completed | `#22C55E` | `#1A22C55E` |
| Cancelled | `#EF4444` | `#1AEF4444` |

### 3.6 Payment Status Colors

| Status | Foreground | Background (10% α) |
|---|---|---|
| Unpaid | `#F59E0B` | `#1AF59E0B` |
| Paid | `#22C55E` | `#1A22C55E` |
| Refunded | `#F97316` | `#1AF97316` |
| Voided | `#9CA3AF` | `#1A9CA3AF` |

---

## 4. Typography Scale

No custom fonts are declared — the app uses the **Material 3 / system default** typeface. Text styling is applied inline per layout.

### Recurring Patterns

| Context | Size | Weight | Color Token |
|---|---|---|---|
| Screen title (header) | `22–26sp` | Bold | `on_primary` or `text_primary` |
| Section title | `15–17sp` | Bold | `text_primary` |
| Body / description | `13–14sp` | Normal | `text_secondary` |
| Label (brand, category) | `9–12sp` | Bold, `ALL_CAPS`, letterSpacing `0.05–0.08` | `text_secondary` or `category_label_color` |
| Price | `14–26sp` | Bold | `price_color` (#3D6EA1) |
| Hint / subtitle | `12–14sp` | Normal or Italic | `login_hint` / `text_secondary` |
| Button text | `15sp` | Default | `on_primary` (filled) or `primary` (outlined) |
| Chip text | System default | — | Stateful via `chip_text_color` selector |

---

## 5. Spacing & Dimension Tokens

### Defined in `dimens.xml`

| Token | Value | Usage |
|---|---|---|
| `dot_size` | `8dp` | Carousel dot indicators |
| `dot_margin` | `5dp` | Space between dots |
| `chip_stroke_width_dp` | `1dp` | Category chip border |
| `category_chip_height` | `36dp` | Filter chip min height |
| `category_chip_horizontal_padding` | `12dp` | Chip start/end padding |

### Common Inline Spacing Conventions

| Pattern | Value | Where Used |
|---|---|---|
| Page horizontal padding | `16–20dp` | Most fragments |
| Card corner radius (standard) | `12–16dp` | MaterialCardView throughout |
| Card corner radius (login) | `18dp` | Login card |
| Card corner radius (summary panel) | `20dp` | Cart/Checkout summary |
| Card elevation | `1–3dp` (subtle) | Product cards, spec cards |
| Card elevation (floating panel) | `12dp` | Cart/Checkout summary bottom sheet |
| Button corner radius | `14dp` (filled), `26dp` (pill) | Buttons |
| Header vertical padding | `12–16dp` | Blue header bars |
| Item margin bottom | `10–12dp` | List items (cart, orders) |
| Line spacing multiplier | `1.1–1.5` | Product name, description |

---

## 6. Navigation Architecture

### Dual Nav-Graph Structure

```
nav_graph.xml (root)
  ├── loginFragment        ← start destination
  ├── registerFragment
  └── mainFragment
        └── nav_graph_main.xml (nested, in MainFragment's own NavHostFragment)
              ├── nav_home (HomeFragment)       ← start destination
              ├── nav_explore (ProductListFragment)
              │     ├─→ productDetailFragment
              │     └─→ cartFragment
              ├── nav_schedule (AppointmentsFragment)
              ├── nav_orders (MessagesFragment — labeled "Chat")
              ├── nav_profile (ProfileFragment)
              │     ├─→ ordersFragment
              │     └─→ billsFragment
              ├── productDetailFragment ─→ cartFragment
              ├── cartFragment ─→ checkoutFragment
              ├── checkoutFragment ─→ orderConfirmFragment
              ├── orderConfirmFragment ─→ orderPlacedFragment
              ├── orderPlacedFragment ─→ orderDetailFragment
              ├── ordersFragment ─→ orderDetailFragment
              ├── orderDetailFragment ─→ billDetailFragment
              └── billDetailFragment ─→ orderDetailFragment
```

### Navigation Arguments

| Destination | Argument | Type | Default |
|---|---|---|---|
| `productDetailFragment` | `productId` | `integer` | `-1` |
| `orderDetailFragment` | `orderId` | `integer` | `-1` |
| `billDetailFragment` | `billId` | `integer` | `-1` |

### Bottom Navigation (5 tabs)

| Tab | ID | Icon | Label | Fragment |
|---|---|---|---|---|
| Home | `nav_home` | `ic_nav_home_outlined` | Home | `HomeFragment` |
| Catalog | `nav_explore` | `ic_nav_shop_outlined` | Catalog | `ProductListFragment` |
| Book | `nav_schedule` | `ic_nav_calendar_outlined` | Book | `AppointmentsFragment` |
| Chat | `nav_orders` | `ic_nav_chat_outlined` | Chat | `MessagesFragment` |
| Profile | `nav_profile` | `ic_nav_person_outlined` | Profile | `ProfileFragment` |

**BottomNavigationView config:** White background, 0dp elevation, always-visible labels, separated by a `1dp` border line. Active = `#1976D2`, Inactive = `#757575`.

---

## 7. Screen Layout Patterns

### 7.1 General Structure

Every screen uses `ConstraintLayout` as root with `android:background="@color/background"`.

### 7.2 Blue Header Bar (Catalog / Cart / Product Detail / Checkout)

```
┌─────────────────────────────────────┐
│  [←]  Title                   [🛒] │  ← @color/primary background
│  All text @color/on_primary        │
└─────────────────────────────────────┘
```

- `LinearLayout` (horizontal), `background="@color/primary"`
- Back button: 40×40dp `ImageButton`, `?attr/selectableItemBackgroundBorderless`
- Title: weight=1, bold, 17–26sp
- Optional right icons: cart, wishlist (40×40dp)

### 7.3 Content-then-Bottom-Bar Pattern

Used in: Product Detail, Cart, Checkout, Order Confirm.

```
┌──────────────────────┐
│  Header / Toolbar    │
├──────────────────────┤
│                      │
│  NestedScrollView    │ ← constrainted between header and bottom bar
│  or RecyclerView     │
│                      │
├──────────────────────┤
│  Bottom Bar (pinned) │ ← surface bg, elevated, buttons
└──────────────────────┘
```

Bottom bar: `LinearLayout` with `background=@color/surface`, `elevation=8dp`, `padding=16dp`, constrained to bottom of parent.

### 7.4 Card-based Sections

Repeating pattern for content sections inside `NestedScrollView`:

```xml
<MaterialCardView
    android:layout_marginHorizontal="16dp"
    android:layout_marginTop="16dp"
    app:cardBackgroundColor="@color/surface"       ← white
    app:cardCornerRadius="12dp"                     ← 12–16dp
    app:cardElevation="2dp"                         ← subtle shadow
    app:strokeColor="@color/divider"                ← thin border
    app:strokeWidth="1dp">

    <LinearLayout padding="16dp" orientation="vertical">
        <!-- Section title (bold, 15sp) -->
        <!-- Section content -->
    </LinearLayout>
</MaterialCardView>
```

### 7.5 Empty State Pattern

Centered vertically, used in Cart, Orders, Bills:

```
┌──────────────────────┐
│                      │
│     [Large icon]     │  ← 80dp, tinted @color/divider
│                      │
│   Title (18sp bold)  │  ← @color/text_primary
│  Subtitle (14sp)     │  ← @color/text_secondary, centered
│                      │
└──────────────────────┘
```

### 7.6 Tab Toggle Pattern (Orders)

Pill-shaped toggle between "Active" / "Past":

```
┌───────────────────────────┐  ← bg_tab_container (#EDF0F4, radius 32dp)
│ ┌─────────┐ ┌───────────┐ │
│ │ Active  │ │   Past    │ │  ← Active tab: bg_tab_button_active (white, radius 28dp)
│ └─────────┘ └───────────┘ │     Inactive tab: transparent
└───────────────────────────┘
```

Active tab text: `text_primary`, bold, 14sp. Inactive: `text_secondary`, 14sp.

---

## 8. Component Catalogue

### 8.1 Product Card (`item_product.xml`)

MaterialCardView — grid item (2-column).

```
┌──────────────────────┐
│  [Image 130dp]       │  ← bg_product_placeholder (#EEF2F7), centerCrop
│            [AR badge]│  ← pill, primary bg, "● AR", visibility=gone
├──────────────────────┤
│  BRAND (9sp, caps)   │  ← text_secondary, letterSpacing 0.08
│  Product Name        │  ← 13sp bold, max 2 lines, ellipsize
│  ★★★★☆ (10)         │  ← RatingBar small, star_color #F59E0B
│  ₱1,200.00          │  ← 14sp bold, price_color
└──────────────────────┘
```

Card: `cornerRadius=14dp`, `elevation=2dp`, `strokeColor=divider`, `strokeWidth=1dp`, `margin=6dp`.

### 8.2 Cart Item (`item_cart.xml`)

Horizontal layout inside CardView.

```
┌──────────────────────────────────────┐
│ ┌──────┐                        [🗑]│
│ │ Img  │  Brand (11sp, bold)        │
│ │80×80 │  Product Name (14sp, bold) │
│ │      │  Variant                    │
│ └──────┘  ₱Price (14sp bold)        │
│           Line total                 │
│           [−] 2 [+]                  │  ← bg_quantity_btn (rounded rect)
└──────────────────────────────────────┘
```

Card: `cornerRadius=14dp`, `elevation=2dp`, `marginHorizontal=16dp`, `marginBottom=10dp`.

### 8.3 Order Card (`item_order.xml`)

```
┌──────────────────────────────────────┐
│  Order #ABC-123          [Pending]   │  ← status badge (bg_status_badge, 12dp radius)
│  Apr 10, 2026                        │  ← primary color text
│  [○][○] 3 item(s)                   │  ← overlapping thumbnails (40dp circles)
│  ₱2,400.00                      [>] │  ← price_color, chevron
└──────────────────────────────────────┘
```

Card: `cornerRadius=16dp`, `elevation=1dp`, `marginBottom=12dp`, `foreground=selectableItemBackground`.

### 8.4 Bill Card (`item_bill.xml`)

Similar card structure to order card — invoice number, amount, payment status badge.

### 8.5 Feedback / Review Item (`item_feedback.xml`)

Small card with user name, rating bar, comment text.

### 8.6 Order-Detail Order Item (`item_order_item.xml`)

Row showing item thumbnail, name, quantity badge, and line price.

### 8.7 Specification Row (`item_spec_row.xml`)

Label-value pair, alternating background using `spec_row_alt` (`#F8F9FA`).

### 8.8 Category Chips

Material3 `Chip` with `Filter` style. State-driven colors:

| State | Background | Text | Stroke |
|---|---|---|---|
| Checked (selected) | `primary` (#3D6EA1) | `on_primary` (white) | `primary` |
| Unchecked (default) | `surface` (white) | `text_primary` (#1F2A37) | `divider` (#E5E9EF) |

Height: `36dp`, horizontal padding: `12dp`, `checkedIconVisible=false`.

### 8.9 Buttons

#### Primary (Filled)

```xml
<MaterialButton
    android:layout_height="48–52dp"
    app:backgroundTint="@color/primary"       <!-- or login_primary for auth -->
    android:textColor="@color/on_primary"
    app:cornerRadius="14dp" />
```

#### Outlined

```xml
<MaterialButton
    style="@style/Widget.Material3.Button.OutlinedButton"
    app:strokeColor="@color/primary"
    app:strokeWidth="1.5dp"
    android:textColor="@color/primary"
    app:cornerRadius="14–26dp" />
```

#### Danger (Logout)

```xml
<MaterialButton
    style="@style/Widget.Material3.Button.OutlinedButton"
    android:textColor="@color/status_cancelled"    <!-- #EF4444 -->
    app:strokeColor="@color/status_cancelled"
    app:cornerRadius="12dp" />
```

### 8.10 Text Input Fields

Material `TextInputLayout` with `boxBackgroundMode="outline"`:

```xml
<TextInputLayout
    app:boxCornerRadiusBottomEnd="10dp"
    app:boxCornerRadiusBottomStart="10dp"
    app:boxCornerRadiusTopEnd="10dp"
    app:boxCornerRadiusTopStart="10dp"
    app:boxStrokeColor="@color/login_outline"      <!-- or divider -->
    app:hintTextColor="@color/login_hint"
    app:startIconDrawable="@drawable/ic_*_24"
    app:startIconTint="@color/login_hint">
```

### 8.11 Search Bar

Custom-built inside a `MaterialCardView`:

```
┌──────────────────────────────────────┐
│ 🔍 Search frames, lenses, access… │
└──────────────────────────────────────┘
```

Card: `46dp` height, `cornerRadius=12dp`, `elevation=2dp`, `strokeColor=divider`.
EditText: `background=@null`, `13sp`, `singleLine=true`, `imeOptions=actionSearch`.

### 8.12 Order Summary Panel (Cart / Checkout)

Floating card pinned to bottom:

```
┌──────────────────────────────────────┐
│  Subtotal             ₱1,200.00     │
│  ─────────────────────────────────── │
│  Discount                    —      │
│  Total                ₱1,200.00     │  ← price_color, 18sp bold
│                                      │
│  Payment note (12sp, centered)       │
│  ┌──────────────────────────────────┐│
│  │      Continue / Place Order      ││  ← primary filled, 52dp
│  └──────────────────────────────────┘│
│  ┌──────────────────────────────────┐│
│  │      Add more items (outlined)   ││  ← outlined, 52dp
│  └──────────────────────────────────┘│
└──────────────────────────────────────┘
```

Card: `cornerRadius=20dp`, `elevation=12dp`.

### 8.13 Image Carousel (Product Detail)

- `ViewPager2` inside a `MaterialCardView` (220dp × match_parent).
- Dot indicators below: `dot_active` (primary, 8dp oval) / `dot_inactive` (`#C0D4EA`, 8dp oval).
- Dot spacing: `5dp` margin.

### 8.14 Order Progress Stepper

Horizontal stepper built dynamically with:
- **Active step:** `bg_step_circle_active` — oval, `#3D6EA1`
- **Inactive step:** `bg_step_circle_inactive` — oval, `#E5E9EF`
- Connected by colored lines.

---

## 9. Drawable & Shape Library

### Background Shapes

| Drawable | Shape | Color | Corner | Stroke | Usage |
|---|---|---|---|---|---|
| `bg_login_gradient` | Rectangle | Gradient `#DCEEFF` → `#FFFFFF` (270°) | — | — | Login screen background |
| `bg_product_placeholder` | Rectangle | `#EEF2F7` | `8dp` | — | Product image placeholder |
| `bg_profile_menu_item` | Rectangle | `@color/surface` | `12dp` | `1dp @color/divider` | Profile menu rows |
| `bg_quantity_btn` | Rectangle | `@color/surface` | `8dp` | `1dp @color/divider` | +/− quantity buttons |
| `bg_status_badge` | Rectangle | `@color/background` | `12dp` | — | Status pill badges |
| `bg_chip_outlined` | Rectangle | `@color/surface` | `50dp` (full pill) | `1dp @color/divider` | Outlined chips |
| `bg_tab_container` | Rectangle | `#EDF0F4` | `32dp` | — | Tab toggle background |
| `bg_tab_button_active` | Rectangle | `#FFFFFF` | `28dp` | — | Active tab pill |
| `bg_thumbnail_circle` | Oval | `#EDF0F4` | — | `1.5dp #FFFFFF` | Order thumbnail circles |

### Indicators

| Drawable | Shape | Color | Size |
|---|---|---|---|
| `dot_active` | Oval | `@color/primary` | `8×8dp` |
| `dot_inactive` | Oval | `#C0D4EA` | `8×8dp` |
| `bg_step_circle_active` | Oval | `#3D6EA1` | Dynamic |
| `bg_step_circle_inactive` | Oval | `#E5E9EF` | Dynamic |

---

## 10. Animations

All transition animations are in `res/anim/` and used for Navigation Component fragment transitions.

| File | Type | Delta | Duration |
|---|---|---|---|
| `slide_in_right` | translateX | `100% → 0%` | `280ms` |
| `slide_out_left` | translateX | `0% → −100%` | `280ms` |
| `slide_in_left` | translateX | `−100% → 0%` | `280ms` |
| `slide_out_right` | translateX | `0% → 100%` | `280ms` |

**Usage:** Applied on all non-tab navigation actions:
```xml
app:enterAnim="@anim/slide_in_right"
app:exitAnim="@anim/slide_out_left"
app:popEnterAnim="@anim/slide_in_left"
app:popExitAnim="@anim/slide_out_right"
```

---

## 11. Icon Library

All icons are vector drawables in `res/drawable/`, 24dp or 16dp, using viewport 24×24.

### Navigation Icons (outlined, bottom nav)

| Icon | File |
|---|---|
| Home | `ic_nav_home_outlined` |
| Shop/Catalog | `ic_nav_shop_outlined` |
| Calendar | `ic_nav_calendar_outlined` |
| Chat | `ic_nav_chat_outlined` |
| Person | `ic_nav_person_outlined` |

### Action Icons

| Icon | File | Size |
|---|---|---|
| Back arrow | `ic_back_24` | 24dp |
| Cart | `ic_cart_24` | 24dp |
| Search | `ic_search_20` | 20dp |
| Filter | `ic_filter_24` | 24dp |
| Heart (outline) | `ic_heart_24` / `ic_heart_outline_24` | 24dp |
| Heart (filled) | `ic_heart_filled_24` | 24dp |
| Trash / Delete | `ic_trash_24` | 24dp |
| Chevron right | `ic_chevron_right_24` | 24dp |
| Plus | `ic_plus_16` | 16dp |
| Minus | `ic_minus_16` | 16dp |
| Check | `ic_check_16` | 16dp |

### Informational Icons

| Icon | File |
|---|---|
| Email | `ic_email_24` |
| Lock | `ic_lock_24` |
| Person | `ic_person_24` |
| Calendar | `ic_calendar_24` |
| Chat | `ic_chat_24` |
| Orders | `ic_orders_24` |
| Shop | `ic_shop_24` |
| Explore | `ic_explore_24` |

---

## 12. Color State Lists

Located in `res/color/`.

### `bottom_nav_color.xml`

| State | Color |
|---|---|
| `state_checked=true` | `@color/bottom_nav_active` (#1976D2) |
| Default | `@color/bottom_nav_inactive` (#757575) |

### `chip_background_color.xml`

| State | Color |
|---|---|
| `state_checked=true` | `@color/primary` (#3D6EA1) |
| Default | `@color/surface` (#FFFFFF) |

### `chip_text_color.xml`

| State | Color |
|---|---|
| `state_checked=true` | `@color/on_primary` (#FFFFFF) |
| Default | `@color/text_primary` (#1F2A37) |

### `chip_stroke_color.xml`

| State | Color |
|---|---|
| `state_checked=true` | `@color/primary` (#3D6EA1) |
| Default | `@color/divider` (#E5E9EF) |

---

## 13. String Conventions

- **Currency:** Philippine Peso (₱). Formatted as `₱%1$s` with comma grouping.
- **String formatting:** `%d` for counts, `%1$s` for named values.
- **Empty states:** Always have a `*_empty_title` (bold) + `*_empty_subtitle` (descriptive) pair.
- **Category namespacing:** Strings are prefixed by feature: `cart_*`, `order_*`, `bill_*`, `checkout_*`, `login_*`, `profile_*`.

---

## 14. Quick-Reference: Layout File Map

### Activities

| File | Description |
|---|---|
| `activity_main.xml` | Single `FragmentContainerView` as nav host, full-screen |

### Fragments — Full Screens

| File | Description |
|---|---|
| `fragment_main.xml` | Tab shell: nested NavHost + BottomNavigationView |
| `fragment_login.xml` | Gradient background, logo, MaterialCardView form |
| `fragment_register.xml` | Similar to login, additional fields |
| `fragment_home.xml` | Greeting header, minimal content |
| `fragment_product_list.xml` | Blue header + search + chips + grid RecyclerView |
| `fragment_product_detail.xml` | Toolbar + scrollable content (gallery, info, specs, reviews) + bottom bar |
| `fragment_cart.xml` | Blue header + RecyclerView + Order Summary panel |
| `fragment_checkout.xml` | 3-step checkout — order details entry |
| `fragment_order_confirm.xml` | Review & confirm order |
| `fragment_order_placed.xml` | Success state with timeline |
| `fragment_orders.xml` | Header + Active/Past toggle + SwipeRefreshLayout + RecyclerView |
| `fragment_order_detail.xml` | Full order detail with progress stepper, items, summary |
| `fragment_bills.xml` | Tab-filtered bill list |
| `fragment_bill_detail.xml` | Invoice detail |
| `fragment_profile.xml` | Avatar + menu items + logout |
| `fragment_appointments.xml` | Placeholder "Coming soon" |
| `fragment_messages.xml` | Placeholder "Coming soon" |

### Item Layouts (RecyclerView)

| File | Used In |
|---|---|
| `item_product.xml` | Product grid (2-column) |
| `item_product_row.xml` | Product row variant |
| `item_product_image.xml` | ViewPager2 image slide |
| `item_product_section_header.xml` | Section headers in product list |
| `item_cart.xml` | Cart item list |
| `item_order.xml` | Order list |
| `item_order_item.xml` | Items inside order detail |
| `item_bill.xml` | Bill list |
| `item_feedback.xml` | Review/feedback item |
| `item_spec_row.xml` | Specification key-value row |
| `item_appointment_option.xml` | Appointment picker option |

---

## Design Principles Summary

1. **Clean & Professional** — Cool gray (#F5F7FA) backgrounds, white cards with subtle 1dp borders and 2dp shadows.
2. **Blue-dominant palette** — Primary #3D6EA1 for headers, prices, badges; Sky-600 #0284C7 for auth CTAs.
3. **Card-first layout** — Every content section lives inside a `MaterialCardView` with rounded corners.
4. **Consistent header pattern** — Blue toolbar bar across all sub-screens, white toolbar only on top-level tab screens (Profile).
5. **Bottom-anchored actions** — CTAs (Add to Cart, Checkout, Place Order) are always pinned to bottom in elevated panels.
6. **Status badge system** — Pill-shaped badges with semantic foreground + 10% opacity background pairs.
7. **Slide transitions** — All non-tab navigation uses 280ms horizontal slide animations.
