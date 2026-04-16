document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('groupSettingsForm');
    const coverInput = document.getElementById('groupCoverInput');
    const dpInput = document.getElementById('groupDpInput');
    const coverPreview = document.getElementById('groupCoverPreview');
    const dpPreview = document.getElementById('groupDpPreview');

    const privacyModal = document.getElementById('privacyVoteModal');
    const deleteModal = document.getElementById('deleteVoteModal');
    const privacyForm = document.getElementById('privacyVoteForm');
    const deleteForm = document.getElementById('deleteVoteForm');

    const openPrivacyVoteModalBtn = document.getElementById('openPrivacyVoteModalBtn');
    const closePrivacyVoteModalBtn = document.getElementById('closePrivacyVoteModalBtn');
    const cancelPrivacyVoteBtn = document.getElementById('cancelPrivacyVoteBtn');
    const submitPrivacyVoteBtn = document.getElementById('submitPrivacyVoteBtn');

    const openDeleteVoteModalBtn = document.getElementById('openDeleteVoteModalBtn');
    const closeDeleteVoteModalBtn = document.getElementById('closeDeleteVoteModalBtn');
    const cancelDeleteVoteBtn = document.getElementById('cancelDeleteVoteBtn');
    const submitDeleteVoteBtn = document.getElementById('submitDeleteVoteBtn');
    const privacySelect = document.getElementById('groupPrivacy');

    const initialVisibility = String(
        window.GROUP_PRIVACY_STATUS
        || privacySelect?.value
        || 'public'
    ).trim().toLowerCase();

    function resolveGroupId() {
        const fromWindow = Number(window.GROUP_ID || window.CURRENT_GROUP_ID || 0);
        if (fromWindow > 0) {
            return fromWindow;
        }

        const candidates = [
            form?.querySelector('input[name="group_id"]'),
            privacyForm?.querySelector('input[name="group_id"]'),
            deleteForm?.querySelector('input[name="group_id"]')
        ];

        for (const node of candidates) {
            const parsed = Number(node?.value || 0);
            if (parsed > 0) {
                return parsed;
            }
        }

        return 0;
    }

    function getCurrentVisibility() {
        return String(window.GROUP_PRIVACY_STATUS || initialVisibility || 'public').trim().toLowerCase();
    }

    function updateVisibilityVoteState() {
        if (!submitPrivacyVoteBtn || !privacySelect) {
            return;
        }

        const selected = String(privacySelect.value || '').trim().toLowerCase();
        const current = getCurrentVisibility();
        const blocked = !selected || selected === current;
        submitPrivacyVoteBtn.setAttribute('aria-disabled', 'false');
        submitPrivacyVoteBtn.setAttribute('data-same-visibility', blocked ? 'true' : 'false');
    }

    function notify(text, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(text, type);
        }
    }

    function previewImage(file, target) {
        if (!file || !target) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            target.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }

    async function startGovernanceVote(payload) {
        const formData = new FormData();
        Object.keys(payload).forEach((key) => {
            formData.append(key, String(payload[key]));
        });

        formData.append('sub_action', 'start_governance_vote');

        const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to start governance vote.');
        }

        return data;
    }

    if (coverInput && coverPreview) {
        coverInput.addEventListener('change', function () {
            const file = coverInput.files && coverInput.files[0] ? coverInput.files[0] : null;
            previewImage(file, coverPreview);
        });
    }

    if (dpInput && dpPreview) {
        dpInput.addEventListener('change', function () {
            const file = dpInput.files && dpInput.files[0] ? dpInput.files[0] : null;
            previewImage(file, dpPreview);
        });
    }

    if (!form) return;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

        try {
            const formData = new FormData(form);
            formData.append('sub_action', 'edit');

            const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                notify(data.message || 'Group settings updated successfully.', 'success');
            } else {
                notify(data.message || 'Failed to update group settings.', 'error');
            }
        } catch (error) {
            notify('Something went wrong while saving group settings.', 'error');
            console.error(error);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Group Settings';
            }
        }
    });

    openPrivacyVoteModalBtn?.addEventListener('click', function () {
        if (privacySelect) {
            privacySelect.value = getCurrentVisibility();
        }
        updateVisibilityVoteState();
        openModal(privacyModal);
    });

    privacySelect?.addEventListener('change', updateVisibilityVoteState);

    closePrivacyVoteModalBtn?.addEventListener('click', function () {
        closeModal(privacyModal);
    });

    cancelPrivacyVoteBtn?.addEventListener('click', function () {
        closeModal(privacyModal);
    });

    openDeleteVoteModalBtn?.addEventListener('click', function () {
        openModal(deleteModal);
    });

    closeDeleteVoteModalBtn?.addEventListener('click', function () {
        closeModal(deleteModal);
    });

    cancelDeleteVoteBtn?.addEventListener('click', function () {
        closeModal(deleteModal);
    });

    [privacyModal, deleteModal].forEach((modal) => {
        modal?.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    privacyForm?.addEventListener('submit', async function (event) {
        event.preventDefault();

        const groupId = resolveGroupId();
        const toVisibility = (privacyForm.querySelector('[name="to_visibility"]')?.value || '').trim().toLowerCase();
        const reason = (privacyForm.querySelector('[name="reason"]')?.value || '').trim();
        const fromVisibility = getCurrentVisibility();

        if (groupId <= 0) {
            notify('Group context is missing. Reload and try again.', 'error');
            return;
        }

        if (!toVisibility || !reason) {
            notify('Visibility and reason are required.', 'error');
            return;
        }

        if (fromVisibility === toVisibility) {
            notify('Choose a different visibility.', 'error');
            updateVisibilityVoteState();
            return;
        }

        if (submitPrivacyVoteBtn) {
            submitPrivacyVoteBtn.disabled = true;
            submitPrivacyVoteBtn.textContent = 'Starting...';
        }

        try {
            await startGovernanceVote({
                group_id: groupId,
                vote_type: 'group_visibility_change',
                target_type: 'group',
                target_id: groupId,
                reason,
                from_visibility: fromVisibility,
                to_visibility: toVisibility
            });

            notify('Visibility change vote started. Review it in Governance.', 'success');
            closeModal(privacyModal);
            privacyForm.reset();
            if (privacySelect) {
                privacySelect.value = getCurrentVisibility();
            }
            updateVisibilityVoteState();
        } catch (error) {
            notify(error.message || 'Failed to start visibility vote.', 'error');
        } finally {
            if (submitPrivacyVoteBtn) {
                submitPrivacyVoteBtn.disabled = false;
                submitPrivacyVoteBtn.textContent = 'Start Vote';
            }
            updateVisibilityVoteState();
        }
    });

    deleteForm?.addEventListener('submit', async function (event) {
        event.preventDefault();

        const groupId = resolveGroupId();
        const reason = (deleteForm.querySelector('[name="reason"]')?.value || '').trim();
        const confirmText = (deleteForm.querySelector('[name="confirm_text"]')?.value || '').trim();

        if (groupId <= 0) {
            notify('Group context is missing. Reload and try again.', 'error');
            return;
        }

        if (!reason) {
            notify('Deletion reason is required.', 'error');
            return;
        }

        if (confirmText.toLowerCase() !== 'delete') {
            notify('Type DELETE to confirm this proposal.', 'error');
            return;
        }

        if (submitDeleteVoteBtn) {
            submitDeleteVoteBtn.disabled = true;
            submitDeleteVoteBtn.textContent = 'Starting...';
        }

        try {
            await startGovernanceVote({
                group_id: groupId,
                vote_type: 'group_deletion',
                target_type: 'group',
                target_id: groupId,
                reason,
                confirm_text: confirmText
            });

            notify('Delete vote started. Admin approvals are now required.', 'success');
            closeModal(deleteModal);
            deleteForm.reset();
        } catch (error) {
            notify(error.message || 'Failed to start delete vote.', 'error');
        } finally {
            if (submitDeleteVoteBtn) {
                submitDeleteVoteBtn.disabled = false;
                submitDeleteVoteBtn.textContent = 'Start Delete Vote';
            }
        }
    });
});
