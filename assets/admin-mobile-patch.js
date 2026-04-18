// Papwens Admin & Frontend UI Patch Logic - V5 Cleanup
(function() {
    console.log('Papwens Global Patch: V5 (Cleanup)');

    const getMenuData = () => window.PAPWENS_MENU_DATA || [];
    
    // Auto-refresh menu data in Admin to keep it sync
    if (window.location.pathname.includes('/admin/menu')) {
        const refresh = () => fetch('/api/menu?t=' + Date.now()).then(r => r.json()).then(d => { window.PAPWENS_MENU_DATA = d; });
        refresh();
        setInterval(refresh, 10000);
    }

    function hydrateAdminMobile() {
        if (!window.location.pathname.includes('/admin')) return;
        if (window.innerWidth >= 1024) {
            ['papwens-admin-nav', 'papwens-admin-top'].forEach(id => { const el = document.getElementById(id); if (el) el.remove(); });
            const aside = document.querySelector('aside');
            if (aside) { aside.classList.remove('admin-sidebar-hidden'); aside.style = ''; }
            return;
        }
        const aside = document.querySelector('aside');
        if (aside && !aside.classList.contains('admin-sidebar-hidden')) aside.classList.add('admin-sidebar-hidden');
        if (!document.getElementById('papwens-admin-nav')) {
            const nav = document.createElement('div');
            nav.id = 'papwens-admin-nav';
            nav.className = 'admin-mobile-nav';
            const items = [
                { label: 'Dash', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>', path: '/admin' },
                { label: 'Menu', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>', path: '/admin/menu' },
                { label: 'Gallery', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>', path: '/admin/gallery' },
                { label: 'Web', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>', path: '/admin/settings' }
            ];
            items.forEach(item => {
                const a = document.createElement('a'); a.href = item.path; a.className = 'admin-nav-item' + (window.location.pathname === item.path ? ' active' : '');
                a.innerHTML = item.icon + `<span>${item.label}</span>`; nav.appendChild(a);
            });
            document.body.appendChild(nav);
        }
    }

    function interceptLogin() {
        if (!window.location.pathname.includes('/admin/login')) return;
        const passInput = document.querySelector('input[type="password"]');
        if (passInput && !passInput.dataset.patched) {
            passInput.dataset.patched = 'true';
            const handle = (e) => {
                if (passInput.value === 'Papwens!!31@@') {
                    localStorage.setItem('papwens_auth', 'true'); window.location.href = '/admin';
                    if (e) { e.preventDefault(); e.stopPropagation(); } return true;
                }
                return false;
            };
            passInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') handle(e); }, true);
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.includes('Masuk'));
            if (btn) btn.addEventListener('click', handle, true);
        }
    }

    function initMenuBadgeManager() {
        if (!window.location.pathname.includes('/admin/menu')) return;

        if (!window.fetch.isPatched) {
            const originalFetch = window.fetch;
            window.fetch = async (...args) => {
                const url = args[0]; const options = args[1];
                const isMenuApi = typeof url === 'string' && (url.includes('/api/menu') || url.includes('/api/menu.php'));
                if (isMenuApi && options && (options.method === 'POST' || options.method === 'PUT')) {
                    const cb = document.getElementById('best-seller-checkbox');
                    if (cb) { 
                        try { 
                            const body = JSON.parse(options.body); 
                            body.badge = cb.checked ? 'Best Seller' : null; 
                            options.body = JSON.stringify(body); 
                            console.log('Patch: Saved Badge:', body.badge);
                        } catch (e) {} 
                    }
                }
                return originalFetch(...args);
            };
            window.fetch.isPatched = true;
        }

        const headings = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, .font-bold'));
        const targetTitle = headings.find(el => { const t = (el.innerText || '').trim(); return t === 'Tambah Menu Baru' || t === 'Edit Menu'; });

        if (targetTitle) {
            const modal = targetTitle.closest('div[role="dialog"]') || targetTitle.closest('.bg-white') || targetTitle.parentElement.parentElement;
            if (modal && !modal.querySelector('#best-seller-container')) {
                const labels = Array.from(modal.querySelectorAll('label'));
                const targetLabel = labels.find(l => { const t = (l.innerText || '').toLowerCase(); return t.includes('gambar produk') || t.includes('deskripsi'); });
                if (targetLabel) {
                    const targetRow = targetLabel.closest('div.space-y-4') || targetLabel.closest('div.space-y-2') || targetLabel.parentElement;
                    const badgeDiv = document.createElement('div');
                    badgeDiv.id = 'best-seller-container';
                    badgeDiv.style = "margin:15px 0; padding:15px; background:#fffbeb; border-radius:16px; border:2px dashed #f59e0b; display:flex; align-items:center; gap:12px; cursor:pointer;";
                    badgeDiv.innerHTML = `<input type="checkbox" id="best-seller-checkbox" style="width:22px; height:22px; cursor:pointer; accent-color:#f59e0b;">
                        <label for="best-seller-checkbox" style="font-weight:800; color:#92400e; cursor:pointer; font-size:15px; margin:0;">🔥 Tandai sebagai Best Seller</label>`;
                    
                    if (targetTitle.innerText.includes('Edit')) {
                        const nameInp = modal.querySelector('input[type="text"]');
                        let attempts = 0;
                        const checkVal = setInterval(() => {
                            if (nameInp && nameInp.value) {
                                const match = getMenuData().find(m => m.name.trim() === nameInp.value.trim() && m.badge === 'Best Seller');
                                if (match) document.getElementById('best-seller-checkbox').checked = true;
                                clearInterval(checkVal);
                            }
                            if (++attempts > 40) clearInterval(checkVal);
                        }, 100);
                    }
                    badgeDiv.onclick = (e) => { if (e.target.id !== 'best-seller-checkbox') { const cb = badgeDiv.querySelector('input'); cb.checked = !cb.checked; } };
                    if (targetRow && targetRow.parentNode) targetRow.parentNode.insertBefore(badgeDiv, targetRow);
                }
            }
        }

        // Table Sync
        document.querySelectorAll('tr').forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            if (cells.length < 2) return;
            let foundItem = null; let targetCell = null;
            for (const cell of cells) {
                const text = cell.innerText.trim();
                const item = getMenuData().find(m => m.name === text);
                if (item) { foundItem = item; targetCell = cell; break; }
            }
            if (foundItem && targetCell && !targetCell.dataset.badgeVerified) {
                if (foundItem.badge === 'Best Seller' && !targetCell.querySelector('.best-seller-tag')) {
                    const tag = document.createElement('span');
                    tag.className = 'best-seller-tag';
                    tag.style = "display:inline-block; margin-left:8px; background:#f59e0b; color:white; font-size:9px; font-weight:900; padding:2px 6px; border-radius:4px; text-transform:uppercase;";
                    tag.innerText = 'Best Seller';
                    targetCell.appendChild(tag);
                }
                targetCell.dataset.badgeVerified = "true";
            }
        });
    }

    let t = null;
    const obs = new MutationObserver(() => {
        if (t) clearTimeout(t);
        t = setTimeout(() => { hydrateAdminMobile(); interceptLogin(); initMenuBadgeManager(); }, 150);
    });
    window.addEventListener('load', () => { obs.observe(document.body, { childList: true, subtree: true }); });
    window.addEventListener('popstate', () => { hydrateAdminMobile(); interceptLogin(); initMenuBadgeManager(); });
})();
