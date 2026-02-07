// --- AJAX Form Handler ---

function bindAjaxForm(form) {
    if (!form || form.dataset.ajaxBound === '1') {
        return;
    }

    form.dataset.ajaxBound = '1';

    form.addEventListener('submit', async function (e) {
        const confirmMsg = form.getAttribute('data-confirm');
        if (confirmMsg && !confirm(confirmMsg)) {
            e.preventDefault();
            return;
        }

        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span> Processando...';
        }

        const formData = new FormData(form);
        formData.append('ajax', '1');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta inválida do servidor (não é JSON).');
            }

            const data = await response.json();

            if (data.success) {
                handleAjaxSuccess(form, data);
            } else {
                alert('Erro: ' + (data.data?.message || data.data || 'Erro desconhecido.'));
            }
        } catch (error) {
            console.error('Erro AJAX:', error);
            alert('Ocorreu um erro na requisição. Verifique o console.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    ajaxForms.forEach(bindAjaxForm);
});

function prependHistoryItem(historyHtml) {
    if (!historyHtml) {
        return;
    }

    const historyContainer = document.querySelector('.lg\\:col-span-2 .space-y-6');
    if (!historyContainer) {
        return;
    }

    const emptyMsg = historyContainer.querySelector('p.text-slate-400.italic');
    if (emptyMsg) {
        emptyMsg.remove();
    }

    historyContainer.insertAdjacentHTML('afterbegin', historyHtml);

    const newHistoryItem = historyContainer.firstElementChild;
    if (newHistoryItem) {
        const newAjaxForms = newHistoryItem.querySelectorAll('form[data-ajax="true"]');
        newAjaxForms.forEach(bindAjaxForm);
    }
}

function handleAjaxSuccess(form, response) {
    const actionInput = form.querySelector('input[name="ccs_action"]');
    const action = actionInput ? actionInput.value : '';
    const payload = response.data || {};
    const resultData = payload.data || {};

    if (action === 'add_checkup') {
        form.reset();
        prependHistoryItem(resultData.history_html);

        showToast(payload.message || 'Checkup registrado!', 'success');
    }
    else if (action === 'quick_windows_update') {
        prependHistoryItem(resultData.history_html);
        showToast(payload.message || 'Atualizado!', 'success');
    }
    else if (action === 'upload_photo') {
        window.location.reload();
    }
    else if (action === 'trash_computer' || action === 'restore_computer') {
        if (payload.redirect_url) {
            window.location.href = payload.redirect_url;
        } else {
            window.location.reload();
        }
    }
    else if (action === 'delete_history') {
        if (resultData.deleted_id) {
            const historyItem = form.closest('.relative.flex.gap-4');
            if (historyItem) {
                historyItem.style.transition = 'opacity 0.3s, transform 0.3s';
                historyItem.style.opacity = '0';
                historyItem.style.transform = 'translateX(-20px)';
                setTimeout(() => historyItem.remove(), 300);
            }
        }

        showToast(payload.message || 'Item excluído!', 'success');
    }
    else if (action === 'add_computer' || action === 'update_computer') {
        if (payload.redirect_url) {
            window.location.href = payload.redirect_url;
        }
    }
    else {
        showToast(payload.message || 'Ação realizada com sucesso!', 'success');
    }
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-y-full opacity-0 z-50 ${type === 'success' ? 'bg-emerald-500' : 'bg-red-500'}`;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-y-full', 'opacity-0');
    }, 10);

    setTimeout(() => {
        toast.classList.add('translate-y-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

