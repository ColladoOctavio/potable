<style>
:root {
    --potable-ink: #17212b;
    --potable-muted: #64748b;
    --potable-panel: #ffffff;
    --potable-bg: #f4f7f9;
    --potable-line: #d9e2ea;
}

body {
    background: var(--potable-bg);
    color: var(--potable-ink);
    font-family: "Instrument Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.auth-shell {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 2rem;
    background:
        linear-gradient(rgba(10, 36, 54, .72), rgba(10, 36, 54, .72)),
        url("https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1600&q=80") center / cover;
}

.auth-card {
    width: min(100%, 420px);
    border: 0;
    border-radius: .5rem;
    box-shadow: 0 1rem 3rem rgba(15, 23, 42, .22);
}

.auth-logo {
    display: block;
    width: min(260px, 100%);
    height: auto;
}

.app-shell {
    min-height: 100vh;
    display: flex;
}

.sidebar {
    width: 272px;
    flex: 0 0 272px;
    background: #10202d;
    color: #d7e1e9;
    min-height: 100vh;
    position: sticky;
    top: 0;
}

.brand-lockup {
    display: flex;
    align-items: center;
    gap: .75rem;
    color: #fff;
    text-decoration: none;
}

.brand-lockup:hover {
    color: #fff;
}

.brand-mark {
    display: inline-grid;
    place-items: center;
    width: 46px;
    height: 46px;
    border-radius: .5rem;
    background: #fff;
}

.brand-mark img {
    width: 38px;
    height: 38px;
}

.brand-name {
    display: block;
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: 0;
}

.brand-name span {
    color: #e4a52b;
}

.brand-subtitle {
    display: block;
    margin-top: .25rem;
    color: rgba(255, 255, 255, .62);
    font-size: .78rem;
}

.sidebar .nav-link {
    color: #c7d4df;
    border-radius: .375rem;
    padding: .65rem .8rem;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    background: rgba(255, 255, 255, .1);
    color: #fff;
}

.sidebar-group {
    border-top: 1px solid rgba(255, 255, 255, .08);
    padding-top: .5rem;
}

.sidebar-group + .sidebar-group {
    margin-top: .5rem;
}

.sidebar-group-toggle {
    align-items: center;
    border-radius: .375rem;
    color: rgba(255, 255, 255, .72);
    cursor: pointer;
    display: flex;
    font-size: .78rem;
    font-weight: 700;
    justify-content: space-between;
    letter-spacing: .04em;
    list-style: none;
    margin-bottom: .25rem;
    padding: .55rem .8rem;
    text-transform: uppercase;
}

.sidebar-group-toggle:hover {
    background: rgba(255, 255, 255, .08);
    color: #fff;
}

.sidebar-group-toggle::-webkit-details-marker {
    display: none;
}

.sidebar-group-chevron {
    font-size: 1.15rem;
    line-height: 1;
    transform: rotate(0deg);
    transition: transform .15s ease;
}

.sidebar-group[open] .sidebar-group-chevron {
    transform: rotate(90deg);
}

.sidebar-group-items {
    display: grid;
    gap: .25rem;
    padding-bottom: .35rem;
}

.main-area {
    flex: 1;
    min-width: 0;
}

.topbar {
    background: #fff;
    border-bottom: 1px solid var(--potable-line);
}

.empresa-switcher {
    min-width: min(260px, 45vw);
}

.content-wrap {
    padding: 1.5rem;
}

.metric-card,
.panel {
    background: var(--potable-panel);
    border: 1px solid var(--potable-line);
    border-radius: .5rem;
}

.metric-label {
    color: var(--potable-muted);
    font-size: .82rem;
    font-weight: 700;
    text-transform: uppercase;
}

.metric-value {
    font-size: clamp(1.25rem, 2vw, 1.8rem);
    font-weight: 800;
}

.table > :not(caption) > * > * {
    vertical-align: middle;
}

.pagination {
    align-items: center;
    gap: .25rem;
    margin-bottom: 0;
}

.pagination svg {
    width: 1rem;
    height: 1rem;
}

.pagination .page-link {
    min-width: 2.35rem;
    text-align: center;
}

.empty-state {
    border: 1px dashed var(--potable-line);
    border-radius: .5rem;
    padding: 2rem;
    text-align: center;
    color: var(--potable-muted);
    background: #fff;
}

.status-badge {
    text-transform: capitalize;
}

@media (max-width: 991.98px) {
    .app-shell {
        display: block;
    }

    .sidebar {
        width: 100%;
        min-height: auto;
        position: static;
    }

    .content-wrap {
        padding: 1rem;
    }
}
</style>
