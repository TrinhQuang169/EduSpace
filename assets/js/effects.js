/* Progressive decoration: never changes data, links, form events or submissions. */
(() => {
    'use strict';
    const body = document.body;
    if (!body.matches('.edu-app, .edu-public')) return;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const precisePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    const toggles = [...document.querySelectorAll('.edu-motion-toggle')];
    const storageKey = 'eduspace-motion';
    let preferred = true;
    try { preferred = localStorage.getItem(storageKey) !== 'off'; } catch (_) { /* Private storage can be unavailable. */ }
    let enabled = false;
    let activeCard = null;
    let pendingFrame = 0;
    let pointer = null;
    const animations = new Set();
    const revealed = new WeakSet();
    let revealObserver;

    const ambient = document.createElement('div');
    ambient.className = 'edu-ambient';
    ambient.setAttribute('aria-hidden', 'true');
    for (let i = 0; i < 3; i++) ambient.appendChild(document.createElement('i'));
    body.prepend(ambient);
    document.querySelectorAll('.edu-welcome, .hero-visual').forEach(panel => {
        const glitter = document.createElement('span');
        glitter.className = 'edu-glitter';
        glitter.setAttribute('aria-hidden', 'true');
        [[8,15],[38,8],[65,19],[89,37],[18,81],[47,92],[78,87],[94,72]].forEach(([x,y], i) => {
            const star = document.createElement('i');
            star.style.setProperty('--sx', `${x}%`);
            star.style.setProperty('--sy', `${y}%`);
            star.style.setProperty('--ss', `${3 + i % 3}px`);
            star.style.setProperty('--sd', `${4 + i % 4}s`);
            star.style.setProperty('--delay', `${-i * .7}s`);
            glitter.appendChild(star);
        });
        panel.appendChild(glitter);
    });

    function resetPointer() {
        if (pendingFrame) cancelAnimationFrame(pendingFrame);
        pendingFrame = 0;
        if (activeCard) {
            activeCard.classList.remove('edu-has-spotlight');
            ['--spot-x', '--spot-y', '--tilt-x', '--tilt-y'].forEach(key => activeCard.style.removeProperty(key));
        }
        activeCard = null;
        pointer = null;
    }
    function stopAnimations() {
        animations.forEach(animation => animation.cancel());
        animations.clear();
        document.querySelectorAll('.edu-ripple').forEach(ripple => ripple.remove());
        resetPointer();
    }
    function reveal(element) {
        if (!enabled || revealed.has(element) || typeof element.animate !== 'function') return;
        revealed.add(element);
        const siblings = element.parentElement ? [...element.parentElement.children] : [];
        const delay = Math.min(Math.max(siblings.indexOf(element), 0) * 45, 180);
        const animation = element.animate([
            { opacity: .4, transform: 'translateY(16px)' },
            { opacity: 1, transform: 'translateY(0)' }
        ], { duration: 560, delay, easing: 'cubic-bezier(.22,1,.36,1)' });
        animations.add(animation);
        animation.finished.then(() => animations.delete(animation), () => animations.delete(animation));
    }
    function applyPreference() {
        enabled = preferred && !reduced.matches;
        body.classList.toggle('edu-motion-on', enabled);
        body.classList.toggle('edu-motion-off', !enabled);
        toggles.forEach(button => {
            button.hidden = false;
            button.disabled = reduced.matches;
            button.setAttribute('aria-pressed', String(enabled));
            const label = reduced.matches ? 'Thiết bị đang bật chế độ giảm chuyển động' : (enabled ? 'Tắt hiệu ứng chuyển động' : 'Bật hiệu ứng chuyển động');
            button.setAttribute('aria-label', label);
            button.title = label;
        });
        if (!enabled) stopAnimations();
    }
    toggles.forEach(button => button.addEventListener('click', () => {
        preferred = !enabled;
        try { localStorage.setItem(storageKey, preferred ? 'on' : 'off'); } catch (_) { /* The control still works for this page. */ }
        applyPreference();
    }));
    reduced.addEventListener('change', applyPreference);
    precisePointer.addEventListener('change', resetPointer);
    window.addEventListener('storage', event => {
        if (event.key === storageKey) { preferred = event.newValue !== 'off'; applyPreference(); }
    });
    applyPreference();

    // Nothing is hidden while waiting for JS or an observer callback.
    if ('IntersectionObserver' in window) {
        revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) { reveal(entry.target); revealObserver.unobserve(entry.target); }
            });
        }, { threshold: .08 });
        document.querySelectorAll('.edu-stat, .edu-action-card, .edu-course-card, .edu-empty, .edu-result, .course-card, .feature-card, .hero-text').forEach(element => revealObserver.observe(element));
        const visibilityObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => entry.target.classList.toggle('edu-effects-offscreen', !entry.isIntersecting));
        }, { rootMargin: '60px' });
        document.querySelectorAll('.edu-learning-art, .edu-course-cover, .edu-score').forEach(element => visibilityObserver.observe(element));
    }

    document.addEventListener('pointermove', event => {
        if (!enabled || !precisePointer.matches || event.pointerType === 'touch') return;
        const card = event.target.closest('.edu-welcome, .edu-course-card, .edu-action-card, .hero-visual');
        if (card !== activeCard) { resetPointer(); activeCard = card; }
        if (!card) return;
        pointer = { x: event.clientX, y: event.clientY };
        if (pendingFrame) return;
        pendingFrame = requestAnimationFrame(() => {
            pendingFrame = 0;
            if (!activeCard || !pointer) return;
            const rect = activeCard.getBoundingClientRect();
            const x = Math.max(0, Math.min(pointer.x - rect.left, rect.width));
            const y = Math.max(0, Math.min(pointer.y - rect.top, rect.height));
            activeCard.style.setProperty('--spot-x', `${x}px`);
            activeCard.style.setProperty('--spot-y', `${y}px`);
            activeCard.style.setProperty('--tilt-x', `${(0.5 - y / rect.height) * 3}deg`);
            activeCard.style.setProperty('--tilt-y', `${(x / rect.width - 0.5) * 3}deg`);
            activeCard.classList.add('edu-has-spotlight');
        });
    }, { passive: true });
    document.documentElement.addEventListener('pointerleave', resetPointer);
    window.addEventListener('blur', resetPointer);
    window.addEventListener('scroll', resetPointer, { passive: true });
    document.addEventListener('pointerdown', event => {
        if (!enabled || event.button !== 0) return;
        const button = event.target.closest('.btn');
        if (!button || button.matches(':disabled, [aria-disabled=true]')) return;
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height) * 2;
        const ripple = document.createElement('span');
        ripple.className = 'edu-ripple';
        ripple.setAttribute('aria-hidden', 'true');
        ripple.style.setProperty('--ripple-size', `${size}px`);
        ripple.style.setProperty('--ripple-x', `${event.clientX - rect.left - size / 2}px`);
        ripple.style.setProperty('--ripple-y', `${event.clientY - rect.top - size / 2}px`);
        button.querySelectorAll('.edu-ripple').forEach(old => old.remove());
        button.appendChild(ripple);
        setTimeout(() => ripple.remove(), 650);
    }, { passive: true });
    function pauseWhenHidden() {
        body.classList.toggle('edu-effects-paused', document.hidden);
        if (document.hidden) stopAnimations();
    }
    document.addEventListener('visibilitychange', pauseWhenHidden);
    pauseWhenHidden();
    window.addEventListener('pagehide', stopAnimations);
})();
