# TuServilleta - WordPress Plugin Specification

## Complete Requirements Document for AI Implementation

**Version:** 1.0  
**Date:** 2025-12-21  
**Purpose:** Complete technical specification for a WordPress product configurator plugin for napkins, coasters, and placemats.

---

## 1. OVERVIEW

### 1.1 Plugin Name
TuServilleta - Configurador de Productos

### 1.2 Target Audience
High-end businesses: yacht rentals, 5-star hotels, Michelin-star restaurants, celebrity events.

### 1.3 Design Requirements
- **ELEGANT and INTUITIVE** user interface
- **NO bright/loud colors** - use muted, luxury tones (grays, creams, golds)
- Tesla/Ryanair-style navigation: click on product image/name to proceed (no "Next" buttons)
- Do NOT display any reference to "Tesla" or "Ryanair" in the UI

---

## 2. FRONTEND - STEP-BY-STEP CONFIGURATOR

### 2.1 Total Steps: 6

| Step | Field Name | Spanish Label | Options Count |
|------|------------|---------------|---------------|
| 1 | size | Tamaño | 9 options |
| 2 | type | Calidad | 4 options |
| 3 | color | Color | 7 options |
| 4 | printing | Impresión | 6 options |
| 5 | quantity | Cantidad | 8 options (dropdown) |
| 6 | price | Precio/Presupuesto | Quote display |

### 2.2 Step 1: SIZE (Tamaño) - 9 Options
Display order (EXACT ORDER REQUIRED):
1. Servilletas 20x20 (10x10 plegada)
2. Servilletas 24x24 (12x12 plegada)
3. Servilletas 30x30 (15x15 plegada)
4. Servilletas 33x33 (16,5x16,5 plegada)
5. Servilletas 40x40 (20x20 plegada)
6. Servilletas 40x40 (10x20 plegada)
7. Posavasos 9 cm., redondo
8. Posavasos 9 cm. cuadrado
9. Mantelín 30x40 cm.

**Sorting Rule:** Servilletas (smallest to largest) → Posavasos → Mantelines

### 2.3 Step 2: TYPE (Calidad) - 4 Options
1. 2 Capas
2. 3 Capas
3. Doble Punto
4. Tisú Seco

### 2.4 Step 3: COLOR - 7 Options
1. Blanco
2. Negro
3. Beige
4. Burdeos
5. Verde Pino
6. Azul Marino
7. Reciclado

### 2.5 Step 4: PRINTING (Impresión) - 6 Options
1. Deluxe a 1 Tinta
2. Deluxe a 2 Tintas
3. Digital a Todo Color
4. Sin Personalizar
5. Estándar a 1 Tinta
6. Estándar a 2 Tintas

### 2.6 Step 5: QUANTITY (Cantidad) - 8 Options (DROPDOWN)
Display as dropdown, ordered numerically from lowest to highest:
1. 250 uds.
2. 500 uds.
3. 1.000 uds.
4. 2.000 uds.
5. 3.000 uds.
6. 5.000 uds.
7. 10.000 uds.
8. 20.000 uds.

### 2.7 Step 6: QUOTE (Presupuesto)
Display summary with:
- **Selected options summary** (all 5 previous selections)
- **Price without VAT** (Precio sin IVA)
- **Shipping: FREE** (Gastos de envío: GRATIS)
- **VAT amount** (IVA 21% calculated)
- **Total with VAT** (Precio IVA incluido)

Two action buttons:
1. **"Solicitar que nos contacten"** - Request contact form
2. **"Pagar ahora"** - Pay with card or PayPal

---

## 3. DYNAMIC OPTION FILTERING

### 3.1 Critical Requirement
**ONLY show options that exist in the imported CSV database.**

Example: If no product exists for "Servilletas 30x30 + Negro", the "Negro" option should NOT appear when Servilletas 30x30 is selected.

### 3.2 Implementation Logic
```
For each step N:
  - Query database for DISTINCT values of field N
  - WHERE all previous selections match (fields 1 to N-1)
  - Display ONLY the returned values
```

---

## 4. BACKEND - ADMIN PANEL

### 4.1 Tab Structure
1. **General** - Company name, email, basic settings
2. **Pagos (Payments)** - PayPal and Stripe configuration
3. **Imágenes (Images)** - Upload images for each option
4. **Productos CSV** - Import/export CSV products
5. **HubSpot** - CRM integration settings

### 4.2 Image Upload Requirements

| Step | Image Count |
|------|-------------|
| Size (Tamaño) | 9 images |
| Type (Calidad) | 4 images |
| Color | 7 images |
| Printing (Impresión) | 6 images |
| Quantity | No images (dropdown) |

**CRITICAL:** Images must be displayed in the frontend for each option. Image matching must be FLEXIBLE to handle slight differences between CSV values and predefined option names.

### 4.3 Payment Configuration
- **PayPal:** Client ID, Secret Key, Mode (Sandbox/Live)
- **Stripe:** Public Key, Secret Key, Mode (Sandbox/Live)
- Server-side price verification required (prevent manipulation)

---

## 5. CSV IMPORT SPECIFICATION

### 5.1 CSV Format
- **Delimiter:** Semicolon (;)
- **First row:** Column headers
- **Encoding:** UTF-8

### 5.2 Required Columns (EXACT names)
```
size;type;color;printing;quantity;price
```

### 5.3 Data Format Details
- **Decimals:** Comma (,) - Spanish format (e.g., 12,50)
- **Thousands:** Period (.) - Spanish format (e.g., 1.234,56)
- **Text fields:** May contain commas, periods, special characters

### 5.4 Example CSV Row
```
Servilletas 40x40 (20x20 plegada);2 Capas;Blanco;Deluxe a 1 Tinta;1.000 uds.;125,50
```

### 5.5 Import Flexibility Requirements
The importer MUST handle:
- Extra whitespace
- Slight variations in text (e.g., "Servilleta" vs "Servilletas")
- UTF-8 special characters (accents: á, é, í, ó, ú, ñ)
- Mixed punctuation in text fields

---

## 6. FRONTEND-IMAGE MATCHING ALGORITHM

### 6.1 Problem Statement
CSV values may differ slightly from predefined options. Example:
- Predefined: "Servilletas 20x20 (10x10 plegada)"
- CSV value: "Servilleta 20x20 (10x10 plegada)" (singular)

### 6.2 Required Matching Logic

```javascript
// Matching priority (highest to lowest):
1. Exact string match
2. Normalized match (lowercase, no punctuation)
3. Key identifier match:
   - For SIZE: Match dimension (e.g., "20x20") + product type (servilleta/posavasos/mantelín)
   - For TYPE: Match keyword (2 capas, 3 capas, doble punto, tisú seco)
   - For COLOR: Match color name
   - For PRINTING: Match type (deluxe/estándar/digital/sin personalizar) + ink count (1 tinta/2 tintas)
```

### 6.3 Normalization Function
```javascript
function normalizeText(text) {
    return text.toLowerCase()
        .trim()
        .replace(/[.,;:()]/g, '')  // Remove punctuation
        .replace(/\s+/g, ' ')       // Normalize spaces
        .trim();
}
```

---

## 7. SORTING REQUIREMENTS

### 7.1 Step 1 (Size) Sorting
Order by predefined list position:
1. Match CSV values against predefined options using flexible matching
2. Sort by predefined index position
3. Unknown values go to end (alphabetically)

### 7.2 Step 5 (Quantity) Sorting
Numeric sort from lowest to highest:
```javascript
function sortQuantities(options) {
    return options.sort(function(a, b) {
        var aNum = parseInt(a.replace(/\./g, '').replace(/[^\d]/g, ''));
        var bNum = parseInt(b.replace(/\./g, '').replace(/[^\d]/g, ''));
        return aNum - bNum;
    });
}
```

---

## 8. PAYMENT INTEGRATION

### 8.1 PayPal Integration
- PayPal JavaScript SDK
- Server-side verification of payment
- Capture payment via PayPal API

### 8.2 Stripe Integration
- Stripe.js + Elements
- Payment Intents API
- Server-side confirmation

### 8.3 Security Requirements
- **NEVER trust client-side price**
- Recalculate price server-side before processing payment
- Verify payment with provider API before marking order complete

---

## 9. HUBSPOT INTEGRATION

### 9.1 Form Submission
- Portal ID and Form ID configuration in admin
- Submit form data to HubSpot on contact request
- Include all product selections + customer data

---

## 10. TECHNICAL REQUIREMENTS

### 10.1 WordPress Hooks
- `init` - Register shortcode
- `wp_enqueue_scripts` - Load frontend assets
- `admin_menu` - Add admin page
- `admin_enqueue_scripts` - Load admin assets
- `wp_ajax_*` - AJAX handlers

### 10.2 Database Tables
1. `{prefix}_tuservilleta_products` - Imported products
2. `{prefix}_tuservilleta_orders` - Customer orders

### 10.3 Shortcode
```
[tuservilleta_configurador]
```

### 10.4 Security
- Nonce verification on all AJAX calls
- `sanitize_text_field()` on all inputs
- `$wpdb->prepare()` for all SQL queries
- Whitelist validation for step names

---

## 11. KNOWN ISSUES AND SOLUTIONS

### 11.1 Issue: Only one product showing in first step
**Cause:** Database query or option matching is too strict
**Solution:** 
- Use `SELECT DISTINCT size FROM products` for step 1
- Do NOT filter by predefined options - show ALL unique values from database
- Apply sorting and image matching AFTER retrieving data

### 11.2 Issue: Images not displaying
**Cause:** Image key doesn't match CSV value
**Solution:**
- Images are stored keyed by predefined option name
- When rendering, match CSV value to predefined option using flexible matching
- Return the image URL for the matching predefined option

### 11.3 Issue: Options not in correct order
**Cause:** Database returns alphabetically sorted
**Solution:**
- After getting options from database, apply custom sorting
- Match each option against predefined order list
- Use matched index for sorting

---

## 12. FILE STRUCTURE

```
tuservilleta-plugin/
├── tuservilleta.php              # Main plugin file
├── admin/
│   ├── class-tuservilleta-admin.php  # Admin panel
│   ├── css/admin.css
│   └── js/admin.js
├── includes/
│   └── class-tuservilleta-database.php  # Database operations
├── public/
│   ├── class-tuservilleta-public.php    # Frontend + AJAX
│   ├── css/public.css
│   └── js/public.js
└── sample-products.csv
```

---

## 13. DELIVERABLE

A single ZIP file (`tuservilleta-plugin.zip`) that can be uploaded directly to WordPress via:
**Plugins → Add New → Upload Plugin**

---

## END OF SPECIFICATION
