<?php
require_once __DIR__ . '/api/config.php';
$db = getDB();

// 0. Simple File Cache for DB results
$cacheFile = __DIR__ . '/uploads/settings_cache.json';
$cacheTime = 60; // 60 seconds (Safe for Shared Hosting)
$webData = [];
$contact = [];
$menuData = [];
$galleryData = [];
$testimonialsData = [];

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
   $cacheData = json_decode(file_get_contents($cacheFile), true);
   $webData = $cacheData['web'] ?? [];
   $contact = $cacheData['contact'] ?? [];
   $menuData = $cacheData['menu'] ?? [];
   $galleryData = $cacheData['gallery'] ?? [];
   $testimonialsData = $cacheData['testimonials'] ?? [];
} else {
   // Fetch Web Settings
   $webRes = $db->query("SELECT value FROM papwens_settings WHERE `key`='web_settings' LIMIT 1");
   $webData = $webRes ? json_decode($webRes->fetch_assoc()['value'], true) : [];

   // Fetch Contact Settings
   $contactRes = $db->query("SELECT * FROM papwens_contacts WHERE id=1 LIMIT 1");
   $contactRow = $contactRes ? $contactRes->fetch_assoc() : [];
   $contact = [];
   if ($contactRow) {
      $contact['address'] = $contactRow['address'] ?? '';
      $contact['whatsapp'] = $contactRow['whatsapp'] ?? '';
      $contact['email'] = $contactRow['email'] ?? '';
      $contact['mapsUrl'] = $contactRow['maps_url'] ?? '';
      $contact['mapsEmbed'] = $contactRow['maps_embed'] ?? '';
      $contact['socialMedia'] = !empty($contactRow['social_media']) ? json_decode($contactRow['social_media'], true) : [];

      if (isset($webData['contactSubtitle'])) {
         $addrShort = explode(',', $contactRow['address'])[0];
         $webData['contactSubtitle'] = "Pesan langsung via WhatsApp (" . $contactRow['whatsapp'] . ") atau kunjungi kami di " . $addrShort . ". Kami siap menyajikan yang terbaik untuk Anda.";
      }
      $webData['dynamic_contact'] = [
         'whatsapp' => $contactRow['whatsapp'] ?? '',
         'address' => $contactRow['address'] ?? '',
         'email' => $contactRow['email'] ?? '',
         'mapsUrl' => $contactRow['maps_url'] ?? ''
      ];
   }
   // Fetch Menu
   $menuRes = $db->query("SELECT * FROM papwens_menu_items ORDER BY category, id DESC");
   if ($menuRes)
      while ($r = $menuRes->fetch_assoc()) {
         $menuData[] = [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'description' => $r['description'],
            'price' => $r['price'],
            'numericPrice' => (int) $r['numeric_price'],
            'category' => $r['category'],
            'image' => $r['image'],
            'badge' => $r['badge'],
            'stock' => (int) $r['stock']
         ];
      }

   // Fetch Gallery
   $galleryRes = $db->query("SELECT * FROM papwens_gallery_images ORDER BY id ASC");
   if ($galleryRes)
      while ($r = $galleryRes->fetch_assoc()) {
         $r['id'] = (int) $r['id'];
         $galleryData[] = $r;
      }

   // Fetch Testimonials
   $testiRes = $db->query("SELECT * FROM papwens_testimonials ORDER BY id ASC");
   if ($testiRes)
      while ($r = $testiRes->fetch_assoc()) {
         $r['id'] = (int) $r['id'];
         $r['stars'] = (int) $r['stars'];
         $testimonialsData[] = $r;
      }

   // Save to cache
   file_put_contents($cacheFile, json_encode([
      'web' => $webData,
      'contact' => $contact,
      'menu' => $menuData,
      'gallery' => $galleryData,
      'testimonials' => $testimonialsData
   ]));
}

/**
 * Force WebP extension for optimized serving
 */
function webp_url($url)
{
   if (!$url)
      return '';
   return preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $url);
}

$siteLogoOptimized = webp_url($webData['siteLogo'] ?? '');
$heroImageOptimized = webp_url($webData['heroImage'] ?? '');

// 3. Prepare variables
$siteName = !empty($webData['siteName']) ? $webData['siteName'] : 'PAPWENS';
$siteLogo = !empty($webData['siteLogo']) ? $webData['siteLogo'] : '';
$whatsappFull = !empty($contact['whatsapp']) ? $contact['whatsapp'] : '6281323331212';
$whatsapp = preg_replace('/[^0-9]/', '', $whatsappFull);
$theme = !empty($webData['theme']) ? $webData['theme'] : 'yellow-black';

// Helper for SEO Title
$seoTitle = $siteName . " - " . ($webData['heroTitleMain'] ?? 'Artisan Bakery & Specialty Coffee');
?>
<!doctype html>
<html lang="en">

<head>
   <meta charset="UTF-8" />
   <link rel="icon" type="image/png" href="assets/favicon.png" />
   <link rel="apple-touch-icon" href="assets/favicon.png" />
   <meta name="viewport"
      content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover, shrink-to-fit=no" />

   <!-- LCP Preload (Aggressive Optimization) -->
   <?php if ($heroImageOptimized): ?>
      <link rel="preload" as="image" href="<?php echo htmlspecialchars($heroImageOptimized); ?>" fetchpriority="high" />
   <?php endif; ?>
   <?php if ($siteLogoOptimized): ?>
      <link rel="preload" as="image" href="<?php echo htmlspecialchars($siteLogoOptimized); ?>" />
   <?php endif; ?>
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link rel="preload" as="style"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap">
   <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
      rel="stylesheet" media="print" onload="this.media='all'">
   <noscript>
      <link
         href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
         rel="stylesheet">
   </noscript>
   <title><?php echo htmlspecialchars($seoTitle); ?></title>

   <?php
   $metaDesc = !empty($webData['metaDescription']) ? $webData['metaDescription'] : "Artisan Bakery & Specialty Coffee di Bandung. Nikmati Freshly Baked Bread, Pastry, dan Kopi Pilihan setiap hari di Papwens.";
   $metaKeys = !empty($webData['metaKeywords']) ? $webData['metaKeywords'] : "bakery bandung, cafe bandung, artisan bakery, specialty coffee, papwens bandung, pastry bandung";
   $currentUrl = "https://papwens.com" . $_SERVER['REQUEST_URI'];
   $ogImageStatic = "/assets/og.png"; // User specified OG image
   $ogImage = !empty($siteLogo) ? $siteLogo : $ogImageStatic;
   ?>
   <meta name="description" content="<?php echo htmlspecialchars($metaDesc); ?>" />
   <meta name="keywords" content="<?php echo htmlspecialchars($metaKeys); ?>" />
   <meta name="google-site-verification" content="jp92cHisC0JxqHHo8OOEcQ92WE17SRNDWCU7rZi1AtQ" />

   <!-- Critical CSS (Above the Fold & Atomic Mobile Fix) -->
   <style>
      *,
      ::before,
      ::after {
         box-sizing: border-box !important;
      }

      :root {
         --warm-white: #fafaf7;
         --espresso: #4a3b32;
         --caramel: #c68b59;
      }

      html,
      body {
         margin: 0;
         padding: 0;
         background: var(--warm-white);
         color: var(--espresso);
         font-family: 'Inter', sans-serif;
         overflow-x: hidden !important;
         width: 100% !important;
         position: relative !important;
         max-width: 100vw !important;
         -webkit-overflow-scrolling: touch;
      }

      #root,
      main,
      section,
      footer,
      .app-container {
         overflow-x: hidden !important;
         width: 100% !important;
         max-width: 100% !important;
         position: relative !important;
         box-sizing: border-box !important;
      }

      #hero-skeleton {
         position: relative;
         height: 100vh;
         background-color: #111;
         overflow: hidden;
         width: 100%;
         border: 0 !important;
      }

      header {
         transition: background 0.3s;
         position: absolute;
         top: 0;
         left: 0;
         right: 0;
         width: auto !important;
         overflow: hidden !important;
      }

      .site-logo {
         height: 40px;
         width: auto;
         object-fit: contain;
      }

      h1,
      h2,
      h3,
      p,
      div,
      span,
      a {
         overflow-wrap: break-word !important;
         word-wrap: break-word !important;
         word-break: break-word !important;
      }

      iframe {
         max-width: 100% !important;
         border: 0 !important;
         width: 100% !important;
      }

      /* Eliminate any potential "floating" overflow from badges/animations */
      [class*="natural"],
      [class*="badge"],
      [class*="rotating"],
      .badge-animate,
      .fixed-element {
         max-width: 100% !important;
         overflow: hidden !important;
         pointer-events: none;
      }


      /* Category Pills Styles */
      .papwens-pill {
         padding: 8px 20px;
         font-size: 11px;
         font-weight: 600;
         text-transform: uppercase;
         border-radius: 9999px;
         transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
         cursor: pointer;
         background: #fdfaf7;
         color: #8c7365;
         border: 1px solid rgba(140, 115, 101, 0.1);
         white-space: nowrap;
         display: inline-flex;
         align-items: center;
         justify-content: center;
      }

      .papwens-pill.active {
         background: #4a3b32;
         color: white;
         box-shadow: 0 4px 12px -2px rgba(74, 59, 50, 0.3);
         border-color: #4a3b32;
      }

      .papwens-pill:hover:not(.active) {
         background: #f5eee6;
         color: #4a3b32;
         transform: translateY(-1px);
      }

      @media (min-width: 768px) {
         .papwens-pill {
            padding: 12px 32px;
            font-size: 13px;
         }
      }

      @media (max-width: 768px) {
         #hero-title {
            font-size: 24px !important;
            line-height: 1.2;
            padding: 0 15px;
         }

         header {
            padding: 10px 15px !important;
         }

         #skeleton-burger {
            width: 34px !important;
            height: 34px !important;
            margin-right: 5px;
         }
      }

      /* Admin Dashboard Mobile Overrides */
      @media (max-width: 1024px) {
         .admin-sidebar-hidden {
            display: none !important;
         }

         .admin-main-px {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            padding-bottom: 5rem !important;
         }

         .glass-card {
            background: rgba(255, 255, 255, 0.7) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important;
         }

         .admin-mobile-nav {
            position: fixed;
            bottom: 16px;
            left: 16px;
            right: 16px;
            height: 64px;
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 9999;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
         }

         .admin-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 10px;
            font-weight: 500;
            text-decoration: none;
            gap: 4px;
         }

         .admin-nav-item.active {
            color: #c68b59;
         }

         .admin-nav-item svg {
            width: 20px;
            height: 20px;
         }

         .admin-top-bar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: rgba(250, 250, 247, 0.8);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            padding: 0 16px;
            z-index: 50;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
         }
      }
   </style>
   <link rel="canonical" href="<?php echo htmlspecialchars($currentUrl); ?>" />

   <!-- Open Graph / Facebook -->
   <meta property="og:type" content="website" />
   <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>" />
   <meta property="og:title" content="<?php echo htmlspecialchars($seoTitle); ?>" />
   <meta property="og:description" content="<?php echo htmlspecialchars($metaDesc); ?>" />
   <meta property="og:image" content="<?php echo htmlspecialchars($ogImageStatic); ?>" />

   <!-- Twitter -->
   <meta property="twitter:card" content="summary_large_image" />
   <meta property="twitter:url" content="<?php echo htmlspecialchars($currentUrl); ?>" />
   <meta property="twitter:title" content="<?php echo htmlspecialchars($seoTitle); ?>" />
   <meta property="twitter:description" content="<?php echo htmlspecialchars($metaDesc); ?>" />
   <meta property="twitter:image" content="<?php echo htmlspecialchars($ogImageStatic); ?>" />

   <!-- Local Business Structured Data (JSON-LD) -->
   <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": ["Bakery", "Cafe", "FoodEstablishment"],
      "name": "<?php echo addslashes($siteName); ?>",
      "image": "<?php echo htmlspecialchars($ogImage); ?>",
      "@id": "https://papwens.com",
      "url": "https://papwens.com",
      "telephone": "<?php echo $whatsappFull; ?>",
      "priceRange": "$$",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Jl. Brigadir Jend. Katamso No.31, Cihaur Geulis",
        "addressLocality": "Bandung",
        "postalCode": "40122",
        "addressCountry": "ID"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": -6.9042219,
        "longitude": 107.6316419
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": [
          "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"
        ],
        "opens": "07:00",
        "closes": "21:00"
      },
      "sameAs": [
        "https://www.instagram.com/papwens/",
        "https://www.facebook.com/papwens"
      ]
    }
    </script>

    <!-- Global UI & Interaction Patch -->
    <script src="/assets/admin-mobile-patch.js?v=<?php echo time(); ?>"></script>

   <script>
      /**
       * ZERO-DELAY HYDRATION ENGINE
       * Extremely fast preloaded cache + fetch interceptor
       */
      window.PAPWENS_DB_CACHE = {
         "/api/menu": <?php echo json_encode($menuData); ?>,
         "/api/gallery": <?php echo json_encode($galleryData); ?>,
         "/api/testimonials": <?php echo json_encode($testimonialsData); ?>,
         "/api/settings/web": <?php echo json_encode($webData); ?>,
         "/api/settings/contact": <?php echo json_encode($contact); ?>
      };

      // Intercept fetch API to instantly fulfill React's data requests without network delay
      const originalFetch = window.fetch;
      window.fetch = async function (url, options) {
         if (!options || !options.method || options.method === 'GET') {
            const apiPath = typeof url === 'string' ? url.split('?')[0] : '';
            if (window.PAPWENS_DB_CACHE[apiPath]) {
               return new Response(JSON.stringify(window.PAPWENS_DB_CACHE[apiPath]), {
                  status: 200,
                  headers: { 'Content-Type': 'application/json' }
               });
            }
         }
         return originalFetch.apply(this, arguments);
      };

      // 17. GLOBAL WHATSAPP INTERCEPTOR (For dynamic buttons using window.open)
      const originalOpen = window.open;
      window.open = function (url, target, features) {
         if (typeof url === 'string' && (url.includes('wa.me') || url.includes('whatsapp.com'))) {
            const phoneMatch = url.match(/(?:wa\.me\/|phone=)([0-9]+)/);
            if (phoneMatch) {
               const rawNum = phoneMatch[1];
               // Reuse our global formatter (defined below, moving it up for scope or keeping it global)
               if (rawNum.startsWith('0')) {
                  const formattedNum = '62' + rawNum.substring(1);
                  url = url.replace(rawNum, formattedNum);
               }
            }
         }
         return originalOpen.call(window, url, target, features);
      };

      window.PAPWENS_CONFIG = {
         whatsapp: '<?php echo $whatsapp; ?>',
         siteName: '<?php echo addslashes($siteName); ?>',
         logo: '<?php echo htmlspecialchars($siteLogo); ?>',
         theme: '<?php echo $theme; ?>',
         settings: <?php echo json_encode($webData); ?>,
         contact: <?php echo json_encode($contact); ?>
      };

      window.PAPWENS_MENU_DATA = window.PAPWENS_DB_CACHE["/api/menu"];
      window.PAPWENS_GALLERY_DATA = window.PAPWENS_DB_CACHE["/api/gallery"];


      // Utility for global phone formatting (Indonesian standards: 08... -> 628...)
      function papwensFormatWA(phone) {
         if (!phone) return "";
         let clean = phone.toString().replace(/[^0-9]/g, "");
         if (clean.startsWith("0")) {
            clean = "62" + clean.substring(1);
         }
         return clean;
      }

      // Surgical Hydration (overwriting hardcoded DOM elements)
      function hydrateDynamicData() {
         const config = window.PAPWENS_CONFIG;
         if (!config) return;
         const settings = config.settings || {};
         const contact = config.contact || {};


         // Helper to force WebP in JS
         const toWebp = (url) => url ? url.replace(/\.(png|jpg|jpeg)$/i, '.webp') : '';

         // 0. Global Perf: Lazy Load Images (except Hero)
         document.querySelectorAll('img:not([loading]):not([data-hydrated])').forEach(img => {
            if (!img.src.includes('hero') && !img.className.includes('logo')) {
               img.setAttribute('loading', 'lazy');
               img.dataset.hydrated = "true";
            }
         });

         // 1. WhatsApp Links (Global Formatter: 08xxx -> 628xxx)
         document.querySelectorAll('a[href*="wa.me"]:not([data-hydrated]), a[href*="whatsapp.com"]:not([data-hydrated])').forEach(a => {
            let url = a.href;
            // Catch numbers in wa.me/NUMBER or ?phone=NUMBER
            const phoneMatch = url.match(/(?:wa\.me\/|phone=)([0-9]+)/);
            if (phoneMatch) {
               const rawNum = phoneMatch[1];
               const formattedNum = papwensFormatWA(rawNum);
               // Only replace if it actually starts with 0 (avoid double formatting)
               if (rawNum.startsWith("0")) {
                  a.href = url.replace(rawNum, formattedNum);
               }
            } else if (config.whatsapp && (url.includes('wa.me/') && !url.includes('?'))) {
               // Final fallback for empty store links
               const pathParts = url.split('wa.me/');
               if (pathParts[1] === '') {
                  a.href = "https://wa.me/" + papwensFormatWA(config.whatsapp);
               }
            }
            a.dataset.hydrated = "true";
         });

         // 2. Logo Replacement (Site-wide)
         if (config.logo) {
            const optLogo = toWebp(config.logo);
            document.querySelectorAll('nav img:not([data-hydrated]), footer img:not([data-hydrated]), .logo img:not([data-hydrated]), [class*="footer"] img:not([data-hydrated]), img[alt*="logo"]:not([data-hydrated]), img[alt*="Papwens"]:not([data-hydrated])').forEach(img => {
               img.src = optLogo;
               img.dataset.hydrated = "true";
            });
         }

         // 3. Navigation & Title Highlights
         if (config.siteName) {
            document.querySelectorAll('.site-name:not([data-hydrated]), .brand-name:not([data-hydrated])').forEach(el => {
               if (el.textContent !== config.siteName) {
                  el.textContent = config.siteName;
                  el.dataset.hydrated = "true";
               }
            });
         }

         // 4. Hero Section Sync
         if (settings.heroTitleMain) {
            document.querySelectorAll('h1:not([data-hydrated])').forEach(h1 => {
               if (h1.id === 'hero-title' || h1.textContent.includes('Bakery')) {
                  h1.textContent = settings.heroTitleMain;
                  h1.dataset.hydrated = "true";
               }
            });
         }

         // 6. Social Media Sync
         if (contact.social_media) {
            try {
               const social = JSON.parse(contact.social_media);
               social.forEach(item => {
                  document.querySelectorAll(`a[href*="${item.platform.toLowerCase()}"]:not([data-hydrated])`).forEach(a => {
                     if (a.href !== item.url) {
                        a.href = item.url;
                        a.dataset.hydrated = "true";
                     }
                  });
               });
            } catch (e) { }
         }

         // 7. Dynamic Images (Hero, About, etc)
         if (settings.heroImage) {
            const optHero = toWebp(settings.heroImage);
            document.querySelectorAll('.hero-bg, .hero-image, #hero-skeleton').forEach(el => {
               if (el.dataset.hydrated_img) return;
               if (el.tagName === 'IMG') el.src = optHero;
               else el.style.backgroundImage = `url("${optHero}")`;
               el.dataset.hydrated_img = "true";
            });
         }
         if (settings.aboutImage) {
            const optAbout = toWebp(settings.aboutImage);
            document.querySelectorAll('.about-image, img[alt*="About"]').forEach(img => {
               if (!img.dataset.hydrated_img) {
                  img.src = optAbout;
                  img.dataset.hydrated_img = "true";
               }
            });
         }

         // 8. Map Sanitizer & Badge Shield (Preventing Mobile Gap)
         document.querySelectorAll('iframe[src*="google.com/maps"]:not([data-sanitized])').forEach(iframe => {
            iframe.style.width = '100%';
            iframe.style.maxWidth = '100vw';
            iframe.removeAttribute('width');
            iframe.dataset.sanitized = "true";
         });

         // 16. Real-time Phone Sanitizer (Standardizes 08... -> 628...)
         document.querySelectorAll('input[type="tel"], input[name*="phone"], input[name*="whatsapp"]').forEach(input => {
            if (!input.dataset.hydrated_sanitizer) {
               input.addEventListener('blur', (e) => {
                  const blurredVal = e.target.value;
                  if (!blurredVal) return;
                  const formatted = papwensFormatWA(blurredVal);
                  if (blurredVal !== formatted) {
                     e.target.value = formatted;
                     // Trigger events so React/State managers pick up the change
                     e.target.dispatchEvent(new Event('input', { bubbles: true }));
                     e.target.dispatchEvent(new Event('change', { bubbles: true }));
                  }
               });
               input.dataset.hydrated_sanitizer = "true";
            }
         });

         // Badge Shield: Ensure rotating elements don't overflow
         document.querySelectorAll('[class*="natural"]:not([data-shielded]), [class*="badge"]:not([data-shielded]), [class*="animate"]:not([data-shielded])').forEach(el => {
            if (el.offsetWidth > window.innerWidth) {
               el.style.maxWidth = '100vw';
               el.style.overflow = 'hidden';
            }
            el.dataset.shielded = "true";
         });

         // 8. Admin Dashboard Patch: Hapus Logo (Only on /admin)
         if (window.location.pathname.includes('/admin')) {
            const webSettingsForm = document.querySelector('form') || document.querySelector('#root');
            if (webSettingsForm && (document.body.innerText.includes('Web Settings') || document.body.innerText.includes('Identitas'))) {
               const logoSection = Array.from(document.querySelectorAll('label')).find(l => l.innerText.includes('Logo'));
               if (logoSection && !document.getElementById('papwens-del-logo-btn')) {
                  const btn = document.createElement('button');
                  btn.id = 'papwens-del-logo-btn';
                  btn.type = 'button';
                  btn.innerText = 'X Hapus Logo';
                  btn.style = 'margin-left:10px; padding:4px 8px; background:#ff4444; color:white; border:none; border-radius:4px; cursor:pointer; font-size:12px;';
                  btn.onclick = async () => {
                     if (!confirm('Hapus logo website?')) return;
                     try {
                        // 1. Fetch latest settings FIRST so we don't overwrite with stale data
                        const currentResp = await fetch('/api/settings/web');
                        const currentSettings = await currentResp.json();

                        // 2. Clear logo
                        const newSettings = { ...currentSettings, siteLogo: "" };
                        delete newSettings.dynamic_contact; // Remove injected field before saving

                        // 3. Save back
                        const resp = await fetch('/api/settings/web', {
                           method: 'POST',
                           headers: { 'Content-Type': 'application/json' },
                           body: JSON.stringify(newSettings)
                        });

                        if (resp.ok) {
                           alert('Logo berhasil dihapus! Halaman akan dimuat ulang untuk sinkronisasi.');
                           window.location.reload();
                        } else {
                           const err = await resp.json();
                           alert('Gagal menghapus logo: ' + (err.error || 'Terjadi kesalahan pada server.'));
                        }
                     } catch (e) {
                        console.error('Papwens Delete Logo Error:', e);
                        alert('Gagal menghapus logo: Koneksi bermasalah atau API tidak merespon.');
                     }
                  };
                  logoSection.appendChild(btn);
               }
            }
         }

         // 9. Footer Text-to-Logo Replacement & Fallback
         const footer = document.querySelector('footer') || document.querySelector('[class*="footer"]');
         if (footer) {
            const siteNameDisplay = config.siteName || 'PAPWENS';
            const brandArea = Array.from(footer.querySelectorAll('h1, h2, h3, h4, h5, div, span, a, p'))
               .find(el => {
                  const txt = el.textContent.trim().toLowerCase();
                  const siteName = (config.siteName || '').toLowerCase();
                  // Ensure we only match the main logo area, NOT the copyright text
                  return el.children.length <= 1 && (txt === siteName || txt === 'papwens' || el.querySelector('img[alt*="logo"]'));
               });

            if (brandArea && !brandArea.dataset.hydrated) {
               if (config.logo && config.logo !== '') {
                  if (!brandArea.querySelector('img')) {
                     const img = document.createElement('img');
                     img.src = config.logo;
                     img.alt = siteNameDisplay;
                     img.style = 'height: 48px; width: auto; margin-bottom: 20px; display: block; object-fit: contain;';
                     brandArea.innerHTML = '';
                     brandArea.appendChild(img);
                  }
               } else {
                  brandArea.innerHTML = '';
                  brandArea.textContent = siteNameDisplay;
                  brandArea.style = 'font-family: "Playfair Display", serif; font-size: 24px; font-weight: 700; color: white; display: block; margin-bottom: 20px;';
               }
               brandArea.dataset.hydrated = "true";
            }
         }

         // 10. Image SEO (ALT tags)
         document.querySelectorAll('img:not([data-seo])').forEach(img => {
            const src = img.src.toLowerCase();
            if (!img.alt || img.alt === '' || img.alt.includes('dummy') || img.alt.includes('Placeholder')) {
               if (src.includes('logo')) img.alt = config.siteName + ' Logo';
               else if (img.closest('nav')) img.alt = config.siteName + ' Navigation Icon';
               else if (img.closest('footer')) img.alt = config.siteName + ' Footer Branding';
               else if (src.includes('hero')) img.alt = 'Artisan Bakery & Specialty Coffee at ' + config.siteName;
               else if (src.includes('about')) img.alt = 'Our Story - ' + config.siteName;
               else img.alt = 'Freshly Baked ' + config.siteName + ' Product';
            }
            img.dataset.seo = "true";
         });

         // 11. Branding Update: "2021 Established" -> "25+ experience"
         if (!document.body.dataset.brandUpdated) {
            const findAndReplaceText = (root) => {
               const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
               let node;
               while (node = walker.nextNode()) {
                  const text = node.nodeValue;
                  if (text && text.trim() === '2021') node.nodeValue = '25+';
                  if (text && text.trim().toUpperCase() === 'ESTABLISHED') node.nodeValue = 'EXPERIENCE';
               }
            };
            findAndReplaceText(document.body);
            document.body.dataset.brandUpdated = "true";
         }

         // 12. Dynamic Admin Categories Sync (MySQL Source)
         if (window.location.pathname.includes('/admin')) {
            const catSelect = Array.from(document.querySelectorAll('select')).find(el => {
               const label = el.closest('div')?.querySelector('label');
               return label && label.innerText.toLowerCase().includes('kategori');
            });

            if (catSelect && !catSelect.dataset.synced) {
               catSelect.dataset.synced = "true";
               const currentVal = catSelect.value;
               fetch('/api/menu.php?t=' + Date.now())
                  .then(r => r.json())
                  .then(items => {
                     const dbCats = [...new Set(items.map(i => i.category))].filter(Boolean);
                     const defaults = ['Bakery', 'Sourdough', 'Pastry', 'Coffee', 'Atmosphere'];
                     const allCats = [...new Set([...defaults, ...dbCats])].sort();

                     catSelect.innerHTML = '';
                     allCats.forEach(cat => {
                        const opt = document.createElement('option');
                        opt.value = cat;
                        opt.textContent = cat;
                        if (cat === currentVal) opt.selected = true;
                        catSelect.appendChild(opt);
                     });
                  }).catch(e => { catSelect.dataset.synced = ""; });
            }
         }


         // 13. Menu Category Pills Injection & Filtering
         const menuTitle = Array.from(document.querySelectorAll('h1, h2, h3, h4, p, span'))
            .find(el => el.textContent.includes('Freshly Made, Just for You'));

         if (menuTitle && !document.getElementById('papwens-menu-pills')) {
            const container = document.createElement('div');
            container.id = 'papwens-menu-pills';
            // Specific styling: Center, Wrap (No Scroll), Gap 12px
            container.style = 'display:flex; justify-content:center; flex-wrap:wrap; gap:12px; margin: 30px auto; max-width: 900px; padding: 0 20px;';

            const cats = ['ALL', 'BAKERY', 'COFFEE', 'NON-COFFEE', 'PASTRY', 'SOURDOUGH'];
            cats.forEach(cat => {
               const btn = document.createElement('button');
               btn.className = 'papwens-pill' + (cat === 'ALL' ? ' active' : '');
               btn.textContent = cat;
               btn.onclick = (e) => {
                  e.preventDefault();
                  e.stopPropagation();

                  // Active state toggle
                  container.querySelectorAll('.papwens-pill').forEach(b => b.classList.remove('active'));
                  btn.classList.add('active');

                  // Filtering logic
                  const categoryToFilter = cat.toLowerCase();
                  const menuData = window.PAPWENS_MENU_DATA || [];

                  // Selected all cards (including hidden ones)
                  const cards = Array.from(document.querySelectorAll('#menu .group, #menu [class*="card"], [id*="menu"] .group, .menu-item'));

                  cards.forEach(card => {
                     if (categoryToFilter === 'all') {
                        card.style.display = '';
                     } else {
                        const cardText = card.innerText.toLowerCase();
                        // Heuristic: try to find matching item by name
                        const matchingItem = menuData.find(m => cardText.includes(m.name.toLowerCase()) || m.name.toLowerCase().includes(cardText));

                        if (matchingItem) {
                           const itemCategory = (matchingItem.category || "").toLowerCase();
                           const itemName = (matchingItem.name || "").toLowerCase();

                           // Logic: Match category, or name, or specific mappings
                           const isBakeryMatch = categoryToFilter === 'bakery' && (itemCategory === 'sourdough' || itemCategory === 'pastry');
                           const isCateMatch = itemCategory.includes(categoryToFilter);
                           const isNameMatch = itemName.includes(categoryToFilter);

                           if (isCateMatch || isBakeryMatch || isNameMatch) {
                              card.style.display = '';
                           } else {
                              card.style.display = 'none';
                           }
                        } else {
                           // Fallback for items not found in DB data
                           if (cardText.includes(categoryToFilter)) {
                              card.style.display = '';
                           } else {
                              card.style.display = 'none';
                           }
                        }
                     }
                  });
               };
               container.appendChild(btn);
            });
            menuTitle.after(container);
         }

         // 14. Operating Hours Patch (Robust Version)
         document.querySelectorAll('span, p, div, h3, h4').forEach(el => {
            if (el.children.length === 0 && !el.dataset.hoursHydrated) {
               const original = el.textContent;
               const updated = original
                  .replace(/08\.00\s*[–-]\s*21\.00/g, '07.00 – 21.00')
                  .replace(/07\.00\s*[–-]\s*22\.00/g, '07.00 – 21.00');

               if (original !== updated) {
                  el.textContent = updated;
                  el.dataset.hoursHydrated = "true";
               }
            }
         });

         // Cleanup OLD pills from any section (Menu/Gallery) if they reappear from React
         document.querySelectorAll('[class*="scrollbar-hide"][class*="snap-x"]').forEach(el => {
            // Only remove if it's the old version (not our new papwens-menu-pills)
            if (el.id !== 'papwens-menu-pills') {
               el.remove();
            }
         });
      }

      // HIGH PERFORMANCE DEBOUNCED OBSERVER
      // Instead of running every mutation, we wait 100ms for React to settle
      let hydrationTimeout = null;
      const observer = new MutationObserver((mutations) => {
         if (hydrationTimeout) clearTimeout(hydrationTimeout);
         hydrationTimeout = setTimeout(() => {
            hydrateDynamicData();
         }, 100);
      });

      window.addEventListener('DOMContentLoaded', () => {
         hydrateDynamicData();
         observer.observe(document.body, { childList: true, subtree: true });
      });
   </script>

   <script type="module" crossorigin src="/assets/index-DU-yLjgB.js?v=BUILD_2026_04_18_V15" defer></script>
   <link rel="stylesheet" crossorigin href="/assets/index-fjww86zz.css?v=BUILD_2026_04_18_V15">
   <style>
      #hero-skeleton {
         aspect-ratio: 16/9;
      }

      @media (max-width: 768px) {
         #hero-skeleton {
            aspect-ratio: 9/16;
         }
      }
   </style>
</head>

<body>
   <!-- Pre-rendered Content for SEO and LCP Performance (Optimized for Mobile) -->
   <div id="root">
      <header
         style="padding: 20px; background: transparent; position: absolute; top: 0; left: 0; right: 0; z-index: 10; display: flex; justify-content: space-between; align-items: center;">
         <img src="<?php echo htmlspecialchars($siteLogoOptimized); ?>" alt="<?php echo htmlspecialchars($siteName); ?>"
            class="site-logo" height="40">
         <div id="skeleton-burger"
            style="width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
         </div>
      </header>
      <main>
         <section id="hero-skeleton"
            style="height: 100vh; display: flex; align-items: center; justify-content: center; background: #111 url('<?php echo htmlspecialchars($heroImageOptimized); ?>') center/cover no-repeat; color: white; text-align: center; padding: 20px; position: relative;">
            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.4); z-index: 1;"></div>
            <div style="position: relative; z-index: 2;">
               <h1 id="hero-title"
                  style="font-family: 'Playfair Display', serif; font-size: 48px; margin-bottom: 15px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                  <?php echo htmlspecialchars($webData['heroTitleMain'] ?? 'Artisan Bakery & Specialty Coffee'); ?></h1>
               <p
                  style="font-family: 'Inter', sans-serif; font-size: 18px; max-width: 600px; margin: 0 auto; opacity: 0.9; text-shadow: 0 1px 5px rgba(0,0,0,0.5);">
                  <?php echo htmlspecialchars($webData['heroTitleSub'] ?? 'Nikmati kesegaran roti dan kopi terbaik di Bandung setiap hari.'); ?>
               </p>
            </div>
         </section>
         <section id="about-skeleton" style="padding: 80px 20px; background: white; color: #111;">
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
               <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; margin-bottom: 20px;">Our Story</h2>
               <div style="font-family: 'Inter', sans-serif; line-height: 1.6; opacity: 0.9;">
                  <?php echo nl2br(htmlspecialchars($webData['aboutDescription'] ?? 'Papwens lahir dari kecintaan kami pada roti artisan dan kopi berkualitas tinggi. Kami percaya bahwa setiap gigitan dan seruputan harus memberikan pengalaman yang tak terlupakan.')); ?>
               </div>
            </div>
         </section>
      </main>
   </div>
</body>

</html>