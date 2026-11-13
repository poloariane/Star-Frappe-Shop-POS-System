<button type="button" class="mobile-nav-toggle" aria-label="Open navigation menu" aria-controls="app-sidebar" aria-expanded="false">
    <i class="fas fa-bars" aria-hidden="true"></i>
</button>
<div class="mobile-nav-overlay" aria-hidden="true"></div>
<style>
    .mobile-nav-toggle, .mobile-nav-overlay { display: none; }
    @media (max-width: 768px) {
        body { overflow-x: hidden; }
        .sidebar {
            z-index: 1001 !important;
            width: min(280px, calc(100vw - 56px)) !important;
            transform: translateX(-105%);
            transition: transform .24s ease;
            padding-top: 68px !important;
        }
        .sidebar.mobile-nav-open { transform: translateX(0); }
        .sidebar-brand span, .sidebar-nav a span { display: inline !important; }
        .sidebar-nav a i { margin-right: 12px !important; font-size: 18px !important; }
        .sidebar-account { width: min(280px, calc(100vw - 56px)) !important; padding: 12px 18px !important; text-align: left !important; }
        .sidebar-account-label { display: block !important; }
        .sidebar-account-role { justify-content: flex-start !important; font-size: 13px !important; }
        .sidebar-account-role i { display: inline !important; }
        .mobile-nav-toggle {
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 1003;
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #1a472a;
            box-shadow: 0 3px 12px rgba(0,0,0,.2);
            font-size: 18px;
            cursor: pointer;
        }
        .mobile-nav-toggle:focus-visible { outline: 3px solid #ffd700; outline-offset: 2px; }
        .mobile-nav-overlay.is-visible {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: block;
            background: rgba(0,0,0,.45);
        }
        body.mobile-nav-active { overflow: hidden; }
        .main-wrapper { margin-left: 0 !important; padding: 68px 14px 20px !important; min-width: 0; }
        .main-wrapper > * { min-width: 0; }
        .header, .dashboard-header { flex-wrap: wrap; gap: 12px; padding: 16px !important; }
        .grid-2, .stats-grid, .stats-row, .bottom-grid, .charts-grid { grid-template-columns: minmax(0, 1fr) !important; }
        .card, .list-card, .form-card { min-width: 0; overflow-x: auto; }
        .data-table { min-width: 600px; }
        .pos-container { margin-left: 0 !important; height: auto !important; min-height: 100vh; padding-top: 54px; flex-direction: column; }
        .menu-section { min-width: 0; padding: 12px; }
        .pos-header { flex-wrap: wrap; gap: 12px; padding: 14px !important; }
        .pos-header h1 { font-size: 18px !important; }
        .pos-title-link { gap: 7px; }
        .pos-header .brand-logo { width: 36px !important; height: 36px !important; }
        .products-grid { grid-template-columns: repeat(auto-fill, minmax(135px, 1fr)); gap: 10px; }
        .product-card { padding: 14px !important; }
        .cart-section { width: 100% !important; height: auto !important; min-height: 390px; max-height: 75vh; }
    }
    @media (max-width: 420px) {
        .mobile-nav-toggle { top: 9px; left: 9px; }
        .main-wrapper { padding-right: 10px !important; padding-left: 10px !important; }
        .pos-header h1 { font-size: 16px !important; }
        .products-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .category-tab { padding: 9px 14px !important; }
        .payment-container { border-radius: 14px !important; }
    }
</style>
<script>
    (function () {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.mobile-nav-toggle');
        const overlay = document.querySelector('.mobile-nav-overlay');
        if (!sidebar || !toggle || !overlay) return;
        sidebar.id = 'app-sidebar';

        function setOpen(open) {
            sidebar.classList.toggle('mobile-nav-open', open);
            overlay.classList.toggle('is-visible', open);
            document.body.classList.toggle('mobile-nav-active', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
            toggle.innerHTML = open
                ? '<i class="fas fa-times" aria-hidden="true"></i>'
                : '<i class="fas fa-bars" aria-hidden="true"></i>';
        }

        toggle.addEventListener('click', function () {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });
        overlay.addEventListener('click', function () { setOpen(false); });
        sidebar.querySelectorAll('.sidebar-nav a').forEach(function (link) {
            link.addEventListener('click', function () { setOpen(false); });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setOpen(false);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) setOpen(false);
        });
    })();
</script>
