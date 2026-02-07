
// --- AJAX Form Handler ---

document.addEventListener('DOMContentLoaded', function () {
    // Intercept forms with data-ajax="true"
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');

    ajaxForms.forEach(form => {
        form.addEventListener('submit', async function (e) {
            // data-confirm check
            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg) {
                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                    return;
                }
            }

            e.preventDefault();

            // Comment blocked removed as it is no longer relevant


            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

            // Visual Feedback - Loading
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span> Processando...';
            }

            const formData = new FormData(form);
            formData.append('ajax', '1'); // Force AJAX mode

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Check if response is JSON (it might be HTML error if something fatal happens)
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new Error("Resposta inválida do servidor (não é JSON).");
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
                // Restore Button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        });
    });
});

function handleAjaxSuccess(form, response) {
    // Generic Toast/Alert
    // For now, let's just use a simple alert if no specific UI update is needed, 
    // or use a custom toast if we want to be fancy. Let's stick to simple first.

    // Check specific actions based on hidden input 'ccs_action'
    const actionInput = form.querySelector('input[name="ccs_action"]');
    const action = actionInput ? actionInput.value : '';
    const payload = response.data || {};
    const resultData = payload.data || {};

    if (action === 'add_checkup') {
        // Clear textarea
        form.reset();

        // Append history item
        if (resultData.history_html) {
            const historyContainer = document.querySelector('.lg\\:col-span-2 .space-y-6');
            if (historyContainer) {
                // Remove "No history" message if exists
                const emptyMsg = historyContainer.querySelector('p.text-slate-400.italic');
                if (emptyMsg) emptyMsg.remove();

                // Insert after the vertical line div (which is pseudo-element actually) 
                // We just prepend to the container
                historyContainer.insertAdjacentHTML('afterbegin', resultData.history_html);
            }
        }
        showToast(payload.message || 'Checkup registrado!', 'success');
    }
    else if (action === 'quick_windows_update') {
        if (resultData.last_windows_update) {
            // Update the display text
            // Need to find the exact DOM element. It's in the Info Card.
            // Search for "Windows Update" label and update next sibling or similar.
            // Since we don't have IDs, we might need to rely on structure or add IDs in template.
            // For now, let's try to reload. But wait, user wanted NO reload.
            // Let's reload page for this one OR add ID to the span in template (better).

            const dateSpan = document.getElementById('windows-update-display');
            if (dateSpan) {
                dateSpan.className = 'text-emerald-500 font-medium';
                dateSpan.textContent = resultData.last_windows_update + ' (0d)'; // Approx
            } else {
                // Fallback if we haven't added ID yet
                window.location.reload();
            }
        }
        showToast(payload.message || 'Atualizado!', 'success');
    }
    else if (action === 'upload_photo') {
        // Reload page to show photos for now, implementing gallery update is complex 
        // without strict structure.
        window.location.reload();
    }
    else if (action === 'trash_computer' || action === 'restore_computer') {
        // Usually leads to redirect or list update.
        // For trash in details view, we probably want to redirect to list.
        if (payload.redirect_url) {
            window.location.href = payload.redirect_url;
        } else {
            window.location.reload();
        }
    }
    else if (action === 'delete_history') {
        // Remove the history item from DOM
        if (resultData.deleted_id) {
            // Find the form that was submitted (form is passed to this function)
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
        // Redirect is usually expected here
        if (payload.redirect_url) {
            window.location.href = payload.redirect_url;
        }
    }
    else {
        // Default fallback
        showToast(payload.message || 'Ação realizada com sucesso!', 'success');
    }
}

// Simple Toast Notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-y-full opacity-0 z-50 ${type === 'success' ? 'bg-emerald-500' : 'bg-red-500'
        }`;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-y-full', 'opacity-0');
    }, 10);

    // Remove after 3s
    setTimeout(() => {
        toast.classList.add('translate-y-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

