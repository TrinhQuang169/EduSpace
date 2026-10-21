/* Navigation only: no changes to forms, submissions or course data. */
(() => {
    const body = document.body;
    const sidebar = document.getElementById('edu-sidebar');
    const opener = document.querySelector('.edu-menu-open');
    const closer = document.querySelector('.edu-menu-close');
    const backdrop = document.querySelector('.edu-backdrop');
    if (!sidebar || !opener || !closer || !backdrop) return;
    body.classList.add('edu-js');
    const mobile = window.matchMedia('(max-width: 900px)');
    const workarea = document.querySelector('.edu-workarea');
    const setOpen = (open, restoreFocus = true) => {
        body.classList.toggle('edu-nav-open', open);
        opener.setAttribute('aria-expanded', String(open));
        backdrop.hidden = !open;
        if (workarea) workarea.inert = open;
        if (open) closer.focus();
        else if (restoreFocus) opener.focus();
    };
    opener.addEventListener('click', () => setOpen(true));
    closer.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (!body.classList.contains('edu-nav-open')) return;
        if (event.key === 'Escape') setOpen(false);
        if (event.key === 'Tab') {
            const items = [...sidebar.querySelectorAll('a, button, input:not([type=hidden])')].filter(el => el.getClientRects().length);
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    mobile.addEventListener('change', () => setOpen(false, false));
})();
