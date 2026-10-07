// BankVWA UI behaviour: mobile menu, error focus, double-submit guard.
// Pure progressive enhancement: every page still works with JavaScript off.
document.addEventListener('DOMContentLoaded', () => {
    // --- Mobile menu (slide-in nav below 960px) ---
    const nav = document.getElementById('site-nav');
    const scrim = document.querySelector('.nav-scrim');
    const openBtn = document.querySelector('.topbar > .menu-toggle');
    const closeBtn = nav && nav.querySelector('.nav-close');

    const setOpen = (open) => {
        nav.classList.toggle('is-open', open);
        scrim.classList.toggle('is-open', open);
        openBtn.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
        (open ? nav.querySelector('.nav-links a') : openBtn).focus();
    };

    if (nav && openBtn) {
        openBtn.addEventListener('click', () => setOpen(true));
        closeBtn.addEventListener('click', () => setOpen(false));
        scrim.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) setOpen(false);
        });
        // Back to desktop width with the menu open: reset it.
        matchMedia('(min-width: 961px)').addEventListener('change', (mq) => {
            if (mq.matches && nav.classList.contains('is-open')) setOpen(false);
        });
    }

    // --- Errors: move focus to the first error so keyboard and screen-reader users land on it ---
    const firstError = document.querySelector('.alert[role="alert"]');
    if (firstError) {
        firstError.setAttribute('tabindex', '-1');
        firstError.focus();
    }

    // Coming back with the Back button restores the page from cache: re-enable its buttons.
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-submitting]').forEach((f) => {
            delete f.dataset.submitting;
            f.querySelectorAll('[aria-busy]').forEach((b) => { b.disabled = false; b.removeAttribute('aria-busy'); });
        });
    });

    // --- Double-submit guard: one click, one request ---
    document.querySelectorAll('form[method="post"]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;              // e.g. a confirm() the user cancelled
            const button = e.submitter || form.querySelector('[type="submit"]');
            if (!button) return;
            if (form.dataset.submitting) { e.preventDefault(); return; }
            form.dataset.submitting = '1';
            // Disable after the browser has captured the form data (a disabled button isn't submitted).
            setTimeout(() => { button.disabled = true; button.setAttribute('aria-busy', 'true'); }, 0);
        });
    });
});
