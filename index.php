<?php
require_once __DIR__ . '/api/config.php';
$db = getDB();

// 0. Simple File Cache for DB results
$cacheFile = __DIR__ . '/uploads/settings_cache.json';
$cacheTime = 300; // 5 minutes
$webData = []; $contact = [];

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    $webData = $cacheData['web'] ?? [];
    $contact = $cacheData['contact'] ?? [];
} else {
    // Fetch Web Settings
    $webRes = $db->query("SELECT value FROM papwens_settings WHERE `key`='web_settings' LIMIT 1");
    $webData = $webRes ? json_decode($webRes->fetch_assoc()['value'], true) : [];

    // Fetch Contact Settings
    $contactRes = $db->query("SELECT * FROM papwens_contacts WHERE id=1 LIMIT 1");
    $contact = $contactRes ? $contactRes->fetch_assoc() : [];
    
    // Save to cache
    file_put_contents($cacheFile, json_encode(['web' => $webData, 'contact' => $contact]));
}

/**
 * Force WebP extension for optimized serving
 */
function webp_url($url) {
    if (!$url) return '';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover, shrink-to-fit=no" />
    
    <!-- LCP Preload (Aggressive Optimization) -->
    <?php if ($heroImageOptimized): ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($heroImageOptimized); ?>" fetchpriority="high" />
    <?php endif; ?>
    <?php if ($siteLogoOptimized): ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($siteLogoOptimized); ?>" />
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700;900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet"></noscript>
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
      *, ::before, ::after { box-sizing: border-box !important; }
      :root { --warm-white: #fafaf7; --espresso: #4a3b32; --caramel: #c68b59; }
      html, body { 
        margin: 0; padding: 0; background: var(--warm-white); color: var(--espresso); 
        font-family: 'Inter', sans-serif; overflow-x: hidden !important; width: 100% !important; 
        position: relative !important; max-width: 100vw !important; -webkit-overflow-scrolling: touch;
      }
      #root, main, section, footer, .app-container { 
        overflow-x: hidden !important; width: 100% !important; max-width: 100% !important; 
        position: relative !important; box-sizing: border-box !important;
      }
      #hero-skeleton { position: relative; height: 100vh; background-color: #111; overflow: hidden; width: 100%; border: 0 !important; }
      header { transition: background 0.3s; position: absolute; top: 0; left: 0; right: 0; width: auto !important; overflow: hidden !important; }
      .site-logo { height: 40px; width: auto; object-fit: contain; }
      h1, h2, h3, p, div, span, a { overflow-wrap: break-word !important; word-wrap: break-word !important; word-break: break-word !important; }
      iframe { max-width: 100% !important; border: 0 !important; width: 100% !important; }
      
      /* Eliminate any potential "floating" overflow from badges/animations */
      [class*="natural"], [class*="badge"], [class*="rotating"], .badge-animate, .fixed-element { 
        max-width: 100% !important; 
        overflow: hidden !important; 
        pointer-events: none;
      }
      
      @media (max-width: 768px) { 
        #hero-title { font-size: 24px !important; line-height: 1.2; padding: 0 15px; } 
        header { padding: 10px 15px !important; }
        #skeleton-burger { width: 34px !important; height: 34px !important; margin-right: 5px; }
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
        "opens": "08:00",
        "closes": "21:00"
      },
      "sameAs": [
        "https://www.instagram.com/papwens/",
        "https://www.facebook.com/papwens"
      ]
    }
    </script>
    
    <script>
      /**
       * PAPWENS TOTAL SYNC ENGINE
       * This object is consumed by the React app and used for surgical hydration
       */
      window.PAPWENS_CONFIG = { 
        whatsapp: '<?php echo $whatsapp; ?>',
        siteName: '<?php echo addslashes($siteName); ?>',
        logo: '<?php echo htmlspecialchars($siteLogo); ?>',
        theme: '<?php echo $theme; ?>',
        settings: <?php echo json_encode($webData); ?>,
        contact: <?php echo json_encode($contact); ?>
      };

      // Surgical Hydration (overwriting hardcoded DOM elements)
      function hydrateDynamicData() {
        const config = window.PAPWENS_CONFIG;
        if (!config) return;
        const settings = config.settings || {};
        const contact = config.contact || {};
        
        // Helper to force WebP in JS
        const toWebp = (url) => url ? url.replace(/\.(png|jpg|jpeg)$/i, '.webp') : '';

        // 0. Global Perf: Lazy Load Images (except Hero)
        document.querySelectorAll('img:not([loading])').forEach(img => {
           if (!img.src.includes('hero') && !img.className.includes('logo')) {
              img.setAttribute('loading', 'lazy');
           }
        });

        // 1. WhatsApp Links
        document.querySelectorAll('a[href*="wa.me"], a[href*="whatsapp.com"]').forEach(a => {
           if (config.whatsapp && !a.dataset.hydrated) {
              const cleanWa = config.whatsapp.replace(/[^0-9]/g, '');
              a.href = "https://wa.me/" + cleanWa;
              a.dataset.hydrated = "true";
           }
        });

        // 2. Logo Replacement (Site-wide)
        if (config.logo) {
          const optLogo = toWebp(config.logo);
          document.querySelectorAll('nav img, footer img, .logo img, [class*="footer"] img, img[alt*="logo"], img[alt*="Papwens"]').forEach(img => {
             if (!img.dataset.hydrated) {
                img.src = optLogo;
                img.dataset.hydrated = "true";
             }
          });
        }

        // 3. Navigation & Title Highlights
        if (config.siteName) {
           document.querySelectorAll('.site-name, .brand-name').forEach(el => {
             if (el.textContent !== config.siteName) el.textContent = config.siteName;
           });
        }

        // 4. Hero Section Sync
        if (settings.heroTitleMain) {
           document.querySelectorAll('h1').forEach(h1 => {
              if (h1.id === 'hero-title' || (h1.textContent.includes('Bakery') && !h1.dataset.hydrated)) {
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
                 document.querySelectorAll(`a[href*="${item.platform.toLowerCase()}"]`).forEach(a => {
                    if (a.href !== item.url) a.href = item.url;
                 });
              });
           } catch(e) {}
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
        document.querySelectorAll('iframe[src*="google.com/maps"]').forEach(iframe => {
           if (!iframe.dataset.sanitized) {
              iframe.style.width = '100%';
              iframe.style.maxWidth = '100vw';
              iframe.removeAttribute('width');
              iframe.dataset.sanitized = "true";
           }
        });

        // Badge Shield: Ensure rotating elements don't overflow
        document.querySelectorAll('[class*="natural"], [class*="badge"], [class*="animate"]').forEach(el => {
           if (el.offsetWidth > window.innerWidth) {
              el.style.maxWidth = '100vw';
              el.style.overflow = 'hidden';
           }
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
                   const newSettings = {...config.settings, siteLogo: ""};
                   try {
                     const resp = await fetch('/api/settings/web', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(newSettings)
                     });
                     if (resp.ok) {
                        alert('Logo berhasil dihapus! Halaman akan dimuat ulang.');
                        window.location.reload();
                     }
                   } catch(e) { alert('Gagal menghapus logo: ' + e); }
                };
                logoSection.appendChild(btn);
             }
          }
        }

        // 9. Footer Text-to-Logo Replacement & Fallback
        const footer = document.querySelector('footer') || document.querySelector('[class*="footer"]');
        if (footer) {
          const siteNameDisplay = config.siteName || 'PAPWENS';
          const brandArea = Array.from(footer.querySelectorAll('h1, h2, h3, h4, h5, div, span, a'))
             .find(el => {
                const txt = el.textContent.trim().toLowerCase();
                return (txt === siteNameDisplay.toLowerCase() || txt === 'papwens' || el.querySelector('img[alt*="logo"]')) && el.children.length <= 1;
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
        document.querySelectorAll('img').forEach(img => {
           const src = img.src.toLowerCase();
           if (!img.alt || img.alt === '' || img.alt.includes('dummy') || img.alt.includes('Placeholder')) {
              if (src.includes('logo')) img.alt = config.siteName + ' Logo';
              else if (img.closest('nav')) img.alt = config.siteName + ' Navigation Icon';
              else if (img.closest('footer')) img.alt = config.siteName + ' Footer Branding';
              else if (src.includes('hero')) img.alt = 'Artisan Bakery & Specialty Coffee at ' + config.siteName;
              else if (src.includes('about')) img.alt = 'Our Story - ' + config.siteName;
              else img.alt = 'Freshly Baked ' + config.siteName + ' Product';
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

    <script type="module" crossorigin src="/assets/index-DU-yLjgB.js" defer></script>
    <link rel="stylesheet" crossorigin href="/assets/index-fjww86zz.css">
    <style>
      #hero-skeleton { aspect-ratio: 16/9; }
      @media (max-width: 768px) { #hero-skeleton { aspect-ratio: 9/16; } }
    </style>
  </head>
  <body>
    <!-- Pre-rendered Content for SEO and LCP Performance (Optimized for Mobile) -->
    <div id="root">
        <header style="padding: 20px; background: transparent; position: absolute; top: 0; left: 0; right: 0; z-index: 10; display: flex; justify-content: space-between; align-items: center;">
          <img src="<?php echo htmlspecialchars($siteLogoOptimized); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" class="site-logo" height="40">
          <div id="skeleton-burger" style="width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);"></div>
       </header>
        <main>
          <section id="hero-skeleton" style="height: 100vh; display: flex; align-items: center; justify-content: center; background: #111 url('<?php echo htmlspecialchars($heroImageOptimized); ?>') center/cover no-repeat; color: white; text-align: center; padding: 20px; position: relative;">
             <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.4); z-index: 1;"></div>
             <div style="position: relative; z-index: 2;">
                <h1 id="hero-title" style="font-family: 'Playfair Display', serif; font-size: 48px; margin-bottom: 15px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);"><?php echo htmlspecialchars($webData['heroTitleMain'] ?? 'Artisan Bakery & Specialty Coffee'); ?></h1>
                <p style="font-family: 'Inter', sans-serif; font-size: 18px; max-width: 600px; margin: 0 auto; opacity: 0.9; text-shadow: 0 1px 5px rgba(0,0,0,0.5);"><?php echo htmlspecialchars($webData['heroTitleSub'] ?? 'Nikmati kesegaran roti dan kopi terbaik di Bandung setiap hari.'); ?></p>
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
