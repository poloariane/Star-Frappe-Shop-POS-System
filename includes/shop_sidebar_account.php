<style>
    .sidebar-account {
        position: fixed;
        z-index: 1001;
        left: 0;
        bottom: 0;
        width: 260px;
        padding: 14px 22px;
        color: rgba(255,255,255,.72);
        background: #0d2b18;
        border-top: 1px solid rgba(255,255,255,.14);
        font-size: 11px;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .sidebar-account-role {
        display: flex;
        align-items: center;
        gap: 9px;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .04em;
    }
    .sidebar-account-role i { color: #ffd700; }
    @media (max-width: 992px) {
        .sidebar-account { width: 70px; padding: 12px 4px; text-align: center; }
        .sidebar-account-label { display: none; }
        .sidebar-account-role { justify-content: center; font-size: 10px; }
        .sidebar-account-role i { display: none; }
    }
</style>
<div class="sidebar-account" aria-label="Current account role">
    <span class="sidebar-account-label">Signed in as</span>
    <span class="sidebar-account-role"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['role'] ?? 'Account', ENT_QUOTES, 'UTF-8') ?></span>
</div>
