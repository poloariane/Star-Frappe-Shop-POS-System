<style>
    .shop-logo-trigger { color: inherit; text-decoration: none; cursor: pointer; }
    .shop-logo-trigger:focus-visible { outline: 3px solid #ffd700; outline-offset: 4px; border-radius: 6px; }
    .shop-about-overlay {
        position: fixed;
        inset: 0;
        z-index: 4000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(9, 24, 14, .68);
    }
    .shop-about-overlay.is-open { display: flex; }
    .shop-about-dialog {
        position: relative;
        width: min(520px, calc(100vw - 40px));
        max-height: min(90vh, 760px);
        max-height: min(90dvh, 760px);
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 32px;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 24px 80px rgba(0,0,0,.28);
        color: #303830;
    }
    .shop-about-close {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 50%;
        background: #edf3ed;
        color: #1a472a;
        font-size: 18px;
        cursor: pointer;
    }
    .shop-about-brand { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
    .shop-about-brand img { width: 86px; height: 86px; object-fit: contain; }
    .shop-about-brand h2 { margin: 0; color: #1a472a; font-size: 23px; }
    .shop-about-brand p { margin: 5px 0 0; color: #727c72; font-size: 13px; }
    .shop-about-tagline { margin: 0 0 12px; color: #1a472a; font-size: 16px; font-weight: 700; }
    .shop-about-description { margin: 0; color: #5f695f; line-height: 1.7; }
    .shop-about-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 20px; color: #657065; font-size: 13px; }
    .shop-about-meta i { margin-right: 6px; color: #1a472a; }
    .shop-about-social { display: inline-flex; align-items: center; gap: 9px; margin-top: 24px; padding: 12px 17px; border-radius: 9px; background: #1a472a; color: #fff; text-decoration: none; font-weight: 700; }
    .shop-about-social:hover { background: #2d5a27; }
    @media (max-width: 600px) {
        .shop-about-overlay { align-items: flex-start; overflow-y: auto; padding: max(12px, env(safe-area-inset-top)) 12px max(12px, env(safe-area-inset-bottom)); }
        .shop-about-dialog { width: 100%; min-height: 0; max-height: calc(100vh - 24px); max-height: calc(100dvh - 24px); margin: auto 0; padding: 24px 20px; border-radius: 16px; -webkit-overflow-scrolling: touch; }
        .shop-about-brand { gap: 12px; padding-right: 36px; margin-bottom: 16px; }
        .shop-about-brand img { width: 64px; height: 64px; flex: 0 0 64px; }
        .shop-about-brand h2 { font-size: 20px; }
        .shop-about-meta { flex-direction: column; gap: 10px; }
        .shop-about-meta span { overflow-wrap: anywhere; line-height: 1.5; }
        .shop-about-social { max-width: 100%; padding: 11px 13px; font-size: 14px; }
    }
    @media (max-width: 360px) {
        .shop-about-overlay { padding-right: 8px; padding-left: 8px; }
        .shop-about-dialog { padding: 20px 16px; }
        .shop-about-brand img { width: 54px; height: 54px; flex-basis: 54px; }
        .shop-about-brand h2 { font-size: 18px; }
        .shop-about-tagline { font-size: 15px; }
        .shop-about-description { font-size: 14px; }
        .shop-about-social { gap: 7px; font-size: 13px; }
    }
</style>
<div class="shop-about-overlay" id="shop-about" role="dialog" aria-modal="true" aria-labelledby="shop-about-title" aria-hidden="true">
    <section class="shop-about-dialog">
        <button type="button" class="shop-about-close" aria-label="Close about the shop"><i class="fas fa-times"></i></button>
        <div class="shop-about-brand">
            <img src="assets/logo.png" alt="Star Frappe logo">
            <div>
                <h2 id="shop-about-title">Star Frappe</h2>
                <p>Catarman, Northern Samar</p>
            </div>
        </div>
        <p class="shop-about-tagline">Study. Chill. Kwento. Repeat.</p>
        <p class="shop-about-description">Your affordable tambayan in Catarman. Serving frappe, milk tea, iced coffee, and waffles for study sessions, barkada hangouts, and everyday cravings.</p>
        <div class="shop-about-meta">
            <span><i class="fas fa-location-dot"></i>135 Asinas Avenue, UEP 1, Catarman, Northern Samar</span>
            <span><i class="fas fa-users"></i>189 followers · 1 following</span>
        </div>
        <a class="shop-about-social" href="https://www.facebook.com/profile.php?id=61573377305822" target="_blank" rel="noopener noreferrer">
            <i class="fab fa-facebook"></i> Visit our Facebook page
        </a>
    </section>
</div>
<script>
    (function () {
        const dialog = document.getElementById('shop-about');
        if (!dialog) return;
        const closeButton = dialog.querySelector('.shop-about-close');
        const closeDialog = function () {
            dialog.classList.remove('is-open');
            dialog.setAttribute('aria-hidden', 'true');
        };
        document.querySelectorAll('.shop-logo-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                dialog.classList.add('is-open');
                dialog.setAttribute('aria-hidden', 'false');
                closeButton.focus();
            });
        });
        closeButton.addEventListener('click', closeDialog);
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) closeDialog();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && dialog.classList.contains('is-open')) closeDialog();
        });
    })();
</script>
