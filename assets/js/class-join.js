/* Progressive enhancement: the same POST form works without JavaScript. */
(() => {
    const panel = document.getElementById('class-join-panel');
    if (!panel || !window.HTMLDialogElement || !HTMLDialogElement.prototype.showModal) return;
    const form = document.getElementById('class-join-form');
    const error = document.getElementById('class-join-error');
    const code = document.getElementById('class-code');
    const className = document.getElementById('class-join-name');
    const close = panel.querySelector('.edu-join-close');
    const submit = form.querySelector('[type=submit]');
    const autoOpen = !panel.hidden;
    const dialog = document.createElement('dialog');
    dialog.className = 'edu-content edu-join-dialog';
    dialog.setAttribute('aria-labelledby', 'class-join-title');
    dialog.setAttribute('aria-describedby', 'class-join-name');
    dialog.append(panel);
    document.body.append(dialog);
    panel.hidden = false;
    close.hidden = false;
    let opener = null;
    let pending = false;
    function open(trigger) {
        opener = trigger;
        if (trigger) {
            form.elements.course_id.value = trigger.dataset.courseId;
            className.textContent = trigger.dataset.classTitle;
        }
        form.elements.class_code.value = '';
        error.hidden = true;
        code.removeAttribute('aria-invalid');
        dialog.showModal();
        code.focus();
    }
    document.querySelectorAll('.edu-join-trigger').forEach(trigger => {
        trigger.addEventListener('click', event => { event.preventDefault(); open(trigger); });
    });
    close.addEventListener('click', () => { if (!pending) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (pending) event.preventDefault(); });
    dialog.addEventListener('click', event => {
        const rect = dialog.getBoundingClientRect();
        if (!pending && event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => {
        code.value = '';
        if (opener) opener.focus();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (pending) return;
        pending = true;
        submit.disabled = true;
        close.disabled = true;
        form.setAttribute('aria-busy', 'true');
        error.hidden = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('session');
            const result = await response.json();
            if (response.ok && result.ok) { window.location.assign(result.redirect); return; }
            error.textContent = result.message || 'Không thể tham gia lớp. Vui lòng thử lại.';
            error.hidden = false;
            code.setAttribute('aria-invalid', 'true');
            code.focus();
        } catch (exception) {
            error.textContent = 'Không thể xác nhận yêu cầu. Hãy tải lại trang để kiểm tra phiên đăng nhập và trạng thái tham gia.';
            error.hidden = false;
        } finally {
            pending = false;
            submit.disabled = false;
            close.disabled = false;
            form.removeAttribute('aria-busy');
        }
    });
    if (autoOpen) open(null);
})();
