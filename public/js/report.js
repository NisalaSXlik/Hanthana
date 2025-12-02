(function() {
    const modal = document.getElementById('reportModal');
    if (!modal) {
        return;
    }

    const form = document.getElementById('reportForm');
    const targetTypeInput = document.getElementById('reportTargetType');
    const targetIdInput = document.getElementById('reportTargetId');
    const reasonSelect = document.getElementById('reportReason');
    const descriptionField = document.getElementById('reportDescription');
    const targetLabelEl = document.getElementById('reportTargetLabel');
    const feedbackEl = document.getElementById('reportFeedback');
    const submitBtn = document.getElementById('submitReportBtn');
    const cancelBtn = modal.querySelector('[data-report-cancel]');
    const closeBtn = modal.querySelector('[data-report-close]');

    const resolveBasePath = () => {
        const base = (typeof BASE_PATH !== 'undefined' && BASE_PATH) ? BASE_PATH : '/';
        return base.endsWith('/') ? base : base + '/';
    };

    const resetFeedback = () => {
        feedbackEl.hidden = true;
        feedbackEl.textContent = '';
        feedbackEl.className = 'report-feedback';
    };

    const showFeedback = (message, type) => {
        feedbackEl.hidden = false;
        feedbackEl.textContent = message;
        feedbackEl.className = `report-feedback ${type}`;
    };

    const openModal = (targetType, targetId, label) => {
        targetTypeInput.value = targetType;
        targetIdInput.value = String(targetId);
        reasonSelect.value = 'spam';
        descriptionField.value = '';
        resetFeedback();
        targetLabelEl.textContent = label || 'this content';
        modal.classList.add('is-open');
        document.body.classList.add('report-modal-open');
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        document.body.classList.remove('report-modal-open');
        form.reset();
        resetFeedback();
    };

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-report-type]');
        if (!trigger) {
            return;
        }

        const type = trigger.getAttribute('data-report-type');
        const id = parseInt(trigger.getAttribute('data-target-id'), 10);
        if (!type || Number.isNaN(id) || id <= 0) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        const label = trigger.getAttribute('data-target-label') || '';
        openModal(type, id, label);
    });

    const handleSubmit = async event => {
        event.preventDefault();
        resetFeedback();

        if (!targetTypeInput.value || !targetIdInput.value) {
            showFeedback('Missing report target.', 'error');
            return;
        }

        const payload = {
            target_type: targetTypeInput.value,
            target_id: Number(targetIdInput.value),
            report_type: reasonSelect.value,
            description: descriptionField.value.trim()
        };

        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending…';

        try {
            const response = await fetch(`${resolveBasePath()}index.php?controller=Report&action=submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json().catch(() => ({}));
            if (response.ok && data.success) {
                showFeedback(data.message || 'Report submitted successfully.', 'success');
                setTimeout(() => {
                    closeModal();
                }, 1200);
            } else {
                const errorMessage = data.message || 'We could not file the report. Please try again.';
                showFeedback(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Report submission failed', error);
            showFeedback('Network error. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit report';
        }
    };

    form.addEventListener('submit', handleSubmit);

    if (cancelBtn) {
        cancelBtn.addEventListener('click', event => {
            event.preventDefault();
            closeModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', event => {
            event.preventDefault();
            closeModal();
        });
    }

    modal.addEventListener('click', event => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
})();
