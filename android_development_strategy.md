# Android Interface Development Strategy

## Where You Stand Right Now

| Layer | What's Built | What's Missing |
|-------|-------------|----------------|
| **Backend** | Foundation (auth, roles, middleware), Products module, Inventory module | Ordering, Billing, Feedbacks, Scheduling, Messaging, AR |
| **Android** | Login screen, Product list screen, Hilt DI, Retrofit + Sanctum token auth, MVVM pattern, Navigation Component | Everything beyond login → product list |
| **Wireframes** | Full wireframes for all modules (blue theme) | — |

Your Android project already has a **solid architecture foundation**: Hilt for DI, Retrofit with auth interceptor, sealed [Resource](file:///c:/Users/ironm/Windows%20Repos/eyecare/android/app/src/main/java/com/example/opticalsystem/util/Resource.kt#3-8) class, ViewBinding, and Navigation. The key question is: **what order should you build the Android screens in?**

---

## Recommended Approach: Mirror the Backend, Module-by-Module

Build Android screens **in lockstep with the backend modules**, one module at a time. For each module, the pattern is:

```
Backend API ready → Android data layer → Android UI → Integration test → Next module
```

This way you always have a working backend to test against, and each module is end-to-end complete before moving on.

---

## Build Order & Priority

### 🔵 Phase A: Core Navigation Shell (Do This First)

Before building any more modules, set up the **app-wide navigation structure** so every subsequent module just plugs into it:

1. **Bottom Navigation Bar** — Home, Shop, Appointments, Messages, Profile
2. **Main container with `NavHostFragment`** — swap login flow for main flow after auth
3. **Home/Dashboard screen** — even if placeholder at first
4. **Registration screen** — backend already supports `POST /register`
5. **Auto-login check** — if token exists in DataStore, skip login and go to main

> [!IMPORTANT]
> This is the **highest priority** because every subsequent module needs somewhere to live in the navigation hierarchy. Without this, each new feature requires reworking navigation.

**Files to create:**
```
ui/
├── MainFragment.kt                   # Container with BottomNav + nested NavHost
├── home/
│   ├── HomeFragment.kt
│   └── HomeViewModel.kt
├── auth/
│   ├── RegisterFragment.kt           # New
│   └── RegisterViewModel.kt          # New
```

**Nav graph changes:** Split into `nav_graph_auth.xml` (login, register) and `nav_graph_main.xml` (bottom nav destinations).

---

### 🟢 Phase B: Complete Products UI (Backend Already Done)

Your product list screen exists but is basic. Flesh it out:

1. **Product Detail screen** — API endpoint `GET /products/{id}` already exists
2. **Category filter chips** — API endpoint `GET /product-categories` already exists
3. **Search bar** — `?search=` query param already supported
4. **Product images carousel** — [ProductImage](file:///c:/Users/ironm/Windows%20Repos/eyecare/android/app/src/main/java/com/example/opticalsystem/data/model/Product.kt#41-50) model already defined
5. **Add to Cart button** (client-side cart, no backend needed)

**Files to create:**
```
ui/products/
├── ProductDetailFragment.kt
├── ProductDetailViewModel.kt
├── ProductImageAdapter.kt            # ViewPager2 for image gallery
data/local/
├── CartManager.kt                    # DataStore-based local cart
├── CartItem.kt
```

---

### 🟡 Phase C: Inventory Android View (Backend Already Done)

Only relevant for **admin/staff** users. Simpler screen:

1. **Inventory list** — `GET /inventory` (admin/staff only)
2. **Stock update** — `PUT /inventory/{product}` (admin only)
3. **Low stock alerts** — filter by `quantity <= reorder_level`

**Files to create:**
```
data/api/InventoryApi.kt
data/model/Inventory.kt
data/repository/InventoryRepository.kt
ui/inventory/
├── InventoryListFragment.kt
├── InventoryListViewModel.kt
├── InventoryAdapter.kt
```

---

### 🟠 Phases D–H: Following the Backend Timeline

Build each Android module **after its backend is complete**:

| Phase | Module | Key Android Screens | Depends On |
|-------|--------|--------------------|----|
| **D** | Ordering | Cart screen, Checkout, Order history, Order detail | Products + Inventory backend |
| **E** | Billing | Bills list, Bill detail, Payment status | Ordering backend |
| **F** | Feedbacks | Product reviews list, Write review form, Rating stars | Products backend |
| **G** | Scheduling | Service types list, Calendar/time slot picker, My appointments | Scheduling backend |
| **H** | Messaging | Conversations list, Chat screen (WebSocket) | Messaging backend |
| **I** | Virtual Try-On | AR camera view with 3D model overlay | AR backend (model URLs) |

---

## Per-Module Development Pattern (Android)

Every Android module follows the **same layered pattern**, mirroring the backend:

```
1. data/model/       → Data classes matching API Resource JSON
2. data/api/         → Retrofit interface for the module's endpoints
3. data/repository/  → Repository wrapping API calls with Resource<T>
4. di/NetworkModule   → Add @Provides for the new API interface
5. ui/{module}/      → Fragment + ViewModel + Adapter (if list)
6. res/layout/       → XML layouts with ViewBinding
7. res/navigation/   → Add fragment + actions to nav graph
```

**The existing code already demonstrates this pattern perfectly** with Auth and Products. Every new module is copy-paste-adapt.

---

## What to Start Coding This Week

Given that the inventory backend is already done, here's a concrete week-by-week plan:

### This Week: Navigation Shell + Register + Product Detail
- [ ] Set up `BottomNavigationView` with 5 tabs
- [ ] Create `MainFragment` as the post-login container
- [ ] Build `RegisterFragment` + `RegisterViewModel`
- [ ] Add auto-login token check in [MainActivity](file:///c:/Users/ironm/Windows%20Repos/eyecare/android/app/src/main/java/com/example/opticalsystem/MainActivity.kt#10-24)
- [ ] Build `ProductDetailFragment` with image carousel
- [ ] Add category filter chips to `ProductListFragment`

### Next Week: Cart + Inventory
- [ ] Implement `CartManager` with DataStore persistence
- [ ] Add cart UI (cart icon badge, cart screen, quantity controls)
- [ ] Build Inventory list for admin/staff (all 3 layers)
- [ ] Add role-based menu visibility (admin/staff see Inventory tab)

### Following Weeks: Match Backend Progress
- [ ] As each backend module ships, build the corresponding Android screens

---

## Architecture Decisions to Lock In Now

| Decision | Recommendation | Rationale |
|----------|---------------|-----------|
| **Navigation** | Single Activity + nested nav graphs | Already using this — keep it. Add bottom nav with nested graphs |
| **Cart storage** | DataStore (local) | Backend plan says client-side cart. No cart table on backend |
| **Role-based UI** | Check `user.role` from stored profile to show/hide admin features | Already have `ProfileResponse` and `TokenManager` |
| **Image loading** | Glide (already in deps) | Already imported — use for product images, user avatars |
| **WebSocket (future)** | OkHttp WebSocket client | Already have OkHttp as a dependency. Will use for messaging module |
| **Offline support** | Not now | Keep it API-first for capstone. Can add Room caching later |

---

## Key Takeaway

**Start with the navigation shell.** Everything else is just plugging new fragments into the existing architecture. Your Auth → Products pattern already establishes the full data flow (`Api → Repository → ViewModel → Fragment`). Every new module is the same pattern, different data.
