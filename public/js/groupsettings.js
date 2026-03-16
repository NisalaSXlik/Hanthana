document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('groupSettingsForm');
    const coverInput = document.getElementById('groupCoverInput');
    const dpInput = document.getElementById('groupDpInput');
    const coverPreview = document.getElementById('groupCoverPreview');
    const dpPreview = document.getElementById('groupDpPreview');

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
});
