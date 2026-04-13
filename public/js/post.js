// post sharing 

// Post creation modal with 3 types: general, event, question
document.addEventListener('DOMContentLoaded', function() {
    const postModal = document.getElementById('postModal');
    const createBtn = document.getElementById('openPostModal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.cancel-btn');
    const shareBtn = document.querySelector('.share-btn');
    const postTypeBtns = document.querySelectorAll('.post-type-btn');
    
    // Field containers
    const generalFields = document.getElementById('generalFields');
    const eventFields = document.getElementById('eventFields');
    const questionFields = document.getElementById('questionFields');
    
    // General fields
    const imageUpload = document.querySelector('.image-upload');
    const postImageInput = document.getElementById('postImage');
    const eventImageUpload = document.getElementById('eventImageUpload');
    const eventPostImageInput = document.getElementById('eventPostImage');
    const eventImageLabel = document.getElementById('eventImageLabel');
    const postTagsInput = document.getElementById('postTags');
    const tagCount = document.querySelector('.tag-count');
    
    // Question fields
    const questionTemplateChips = questionFields ? questionFields.querySelectorAll('.template-chip') : [];
    const questionTextareas = questionFields ? questionFields.querySelectorAll('textarea[name]') : [];
    
    let currentPostType = 'general';
    let selectedFile = null;
    let selectedEventFile = null;

    // Show modal
    if (createBtn && postModal) {
        createBtn.addEventListener('click', function() {
            resetForm();
            postModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    // Close modal
    function closeModal() {
        postModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Tab switching
    postTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            postTypeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPostType = this.dataset.type;

            // Show/hide fields based on type
            if (currentPostType === 'general') {
                generalFields.style.display = 'block';
                eventFields.style.display = 'none';
                questionFields.style.display = 'none';
            } else if (currentPostType === 'event') {
                generalFields.style.display = 'none';
                eventFields.style.display = 'block';
                questionFields.style.display = 'none';
            } else if (currentPostType === 'question') {
                generalFields.style.display = 'none';
                eventFields.style.display = 'none';
                questionFields.style.display = 'block';
                // Reset question template on open
                if (questionTemplateChips.length) {
                    applyTemplateChip(questionTemplateChips[0], true);
                }
            }
        });
    });

    // ===== QUESTION TEMPLATE LOGIC =====
    const questionTitleInput = document.getElementById('questionTitleInput');
    
    const applyTemplateChip = (chip, forceValue = false) => {
        if (!chip || !questionTitleInput) return;
        questionTemplateChips.forEach(btn => btn.classList.remove('active'));
        chip.classList.add('active');
        const prefix = chip.dataset.templatePrefix || '';
        questionTitleInput.setAttribute('placeholder', prefix ? `${prefix} ...` : 'Summarize your question');
        const shouldPrefill = forceValue || !questionTitleInput.value.trim() || questionTitleInput.dataset.prefilled === 'true';
        if (shouldPrefill && prefix) {
            questionTitleInput.value = `${prefix} `;
            questionTitleInput.dataset.prefilled = 'true';
        }
        if (!prefix) {
            questionTitleInput.dataset.prefilled = 'false';
        }
    };

    questionTemplateChips.forEach((chip, index) => {
        chip.addEventListener('click', () => applyTemplateChip(chip, true));
    });

    questionTitleInput?.addEventListener('input', () => {
        if (questionTitleInput.value.trim().length) {
            questionTitleInput.dataset.prefilled = 'false';
        }
    });

    // Character count for question textareas
    const updateCharCount = (field) => {
        if (!field) return;
        const maxlength = parseInt(field.getAttribute('maxlength') || '0');
        const length = field.value.trim().length;
        const countEl = document.querySelector(`.char-count[data-for="${field.name}"]`);
        if (countEl) {
            countEl.textContent = maxlength ? `${length} / ${maxlength}` : `${length} chars`;
        }
    };

    questionTextareas.forEach(textarea => {
        updateCharCount(textarea);
        textarea.addEventListener('input', () => updateCharCount(textarea));
    });

    // Image upload
    imageUpload.addEventListener('click', function() {
        postImageInput.click();
    });

    postImageInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            selectedFile = e.target.files[0];
            const isVideo = selectedFile.type && selectedFile.type.startsWith('video/');

            imageUpload.innerHTML = '';

            if (isVideo) {
                const preview = document.createElement('video');
                preview.className = 'image-preview';
                preview.controls = true;
                preview.muted = true;
                preview.playsInline = true;
                preview.style.display = 'block';
                imageUpload.appendChild(preview);

                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                };
                reader.readAsDataURL(selectedFile);
            } else {
                const preview = document.createElement('img');
                preview.className = 'image-preview';
                imageUpload.appendChild(preview);

                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(selectedFile);
            }
        }
    });

    if (eventImageUpload && eventPostImageInput) {
        eventImageUpload.addEventListener('click', function() {
            eventPostImageInput.click();
        });

        eventPostImageInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                selectedEventFile = e.target.files[0];
                if (eventImageLabel) {
                    eventImageLabel.textContent = selectedEventFile.name;
                }
                eventImageUpload.classList.add('has-file');

                let preview = eventImageUpload.querySelector('.event-image-preview');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.className = 'event-image-preview';
                    eventImageUpload.appendChild(preview);
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(selectedEventFile);
            }
        });
    }

    // Tag validation
    postTagsInput.addEventListener('input', function() {
        const tags = this.value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        tagCount.textContent = `${tags.length} tags`;
    });

    // Share/Submit
    shareBtn.addEventListener('click', function() {
        clearFormErrors();

        if (currentPostType === 'question') {
            submitQuestion();
        } else if (currentPostType === 'event') {
            submitEvent();
        } else {
            submitGeneral();
        }
    });

    // Submit general post
    function submitGeneral() {
        const caption = document.getElementById('postCaption').value.trim();
        const tagsInput = document.getElementById('postTags').value.trim();
        const tagArray = tagsInput.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);

        let errors = [];
        if (!caption) errors.push({ field: 'postCaption', message: 'Caption is required.' });

        if (errors.length > 0) {
            showFormErrors(errors);
            return;
        }

        const formData = new FormData();
        formData.append('caption', caption);
        formData.append('tags', tagsInput);
        formData.append('postType', 'general');

        if (selectedFile) {
            formData.append('image', selectedFile);
        }

        const groupId = new URLSearchParams(window.location.search).get('group_id');
        formData.append('sub_action', 'create');
        formData.append('is_group_post', groupId ? '1' : '0');
        if (groupId) formData.append('group_id', groupId);

        fetch(BASE_PATH + 'index.php?controller=Posts&action=handleAjax', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Post shared successfully!', 'success');
                closeModal();
                resetForm();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Failed to share post', 'error');
        });
    }

    // Submit event post
    function submitEvent() {
        const eventTitle = document.getElementById('createEventTitle').value.trim();
        const eventDate = document.getElementById('createEventDate').value.trim();
        const eventLocation = document.getElementById('createEventLocation').value.trim();
        const eventDescription = document.getElementById('createEventDescription').value.trim();
        const eventTime = document.getElementById('createEventTime').value.trim();

        let errors = [];
        if (!eventTitle) errors.push({ field: 'createEventTitle', message: 'Event title is required.' });
        if (!eventDate) errors.push({ field: 'createEventDate', message: 'Event date is required.' });

        if (errors.length > 0) {
            showFormErrors(errors);
            return;
        }

        const formData = new FormData();
        formData.append('sub_action', 'create');
        formData.append('postType', 'event');
        formData.append('caption', eventDescription);
        formData.append('tags', 'event,upcoming,community,social,announcement');
        formData.append('eventTitle', eventTitle);
        formData.append('eventDate', eventDate);
        formData.append('eventLocation', eventLocation);
        formData.append('eventTime', eventTime);
        if (selectedEventFile) {
            formData.append('image', selectedEventFile);
        }

        const groupId = new URLSearchParams(window.location.search).get('group_id');
        formData.append('is_group_post', groupId ? '1' : '0');
        if (groupId) formData.append('group_id', groupId);

        fetch(BASE_PATH + 'index.php?controller=Posts&action=handleAjax', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Event created successfully!', 'success');
                closeModal();
                resetForm();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Failed to create event', 'error');
        });
    }

    // Submit question
    function submitQuestion() {
        const title = document.getElementById('questionTitleInput').value.trim();
        const problemStatement = document.getElementById('problemStatement').value.trim();

        let errors = [];
        if (!title) errors.push({ field: 'questionTitleInput', message: 'Question title is required.' });
        if (!problemStatement) errors.push({ field: 'problemStatement', message: 'Problem description is required.' });

        if (errors.length > 0) {
            showFormErrors(errors);
            return;
        }

        const formData = new FormData();
        formData.append('sub_action', 'createQuestion');
        formData.append('title', title);
        formData.append('category', document.getElementById('questionCategory').value || 'General');
        formData.append('content', problemStatement);
        formData.append('topics', document.getElementById('questionTopics').value.trim());

        fetch(BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Question posted successfully!', 'success');
                closeModal();
                resetForm();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Failed to post question', 'error');
        });
    }

    function resetForm() {
        // General
        document.getElementById('postCaption').value = '';
        document.getElementById('postTags').value = '';
        tagCount.textContent = '0 tags';
        postImageInput.value = '';
        imageUpload.innerHTML = `
            <i class="uil uil-image-upload"></i>
            <p>Drag photos and videos here or click to browse</p>
        `;

        // Event
        document.getElementById('createEventTitle').value = '';
        document.getElementById('createEventDescription').value = '';
        document.getElementById('createEventDate').value = '';
        document.getElementById('createEventTime').value = '';
        document.getElementById('createEventLocation').value = '';
        selectedEventFile = null;
        if (eventPostImageInput) eventPostImageInput.value = '';
        if (eventImageLabel) eventImageLabel.textContent = 'Click to add event image';
        if (eventImageUpload) eventImageUpload.classList.remove('has-file');
        if (eventImageUpload) {
            const preview = eventImageUpload.querySelector('.event-image-preview');
            if (preview) {
                preview.remove();
            }
        }

        // Question
        document.getElementById('questionTitleInput').value = '';
        document.getElementById('questionCategory').value = 'General';
        document.getElementById('problemStatement').value = '';
        document.getElementById('questionTopics').value = '';

        // Reset char counts
        questionTextareas.forEach(textarea => updateCharCount(textarea));

        currentPostType = 'general';
        selectedFile = null;
        document.querySelector('.post-type-btn[data-type="general"]').click();
    }

    function showFormErrors(errors) {
        errors.forEach(error => {
            const fieldElement = document.getElementById(error.field);
            if (fieldElement) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.style.color = 'red';
                errorDiv.style.fontSize = '0.85rem';
                errorDiv.style.marginTop = '0.25rem';
                errorDiv.textContent = error.message;
                fieldElement.parentNode.insertBefore(errorDiv, fieldElement.nextSibling);
            }
        });
    }

    function clearFormErrors() {
        document.querySelectorAll('.form-error').forEach(el => el.remove());
    }

    function showToast(message, type = 'success') {
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        const icons = {
            success: 'uil-check-circle',
            error: 'uil-times-circle',
            info: 'uil-info-circle'
        };
        toast.innerHTML = `<i class="uil ${icons[type] || icons.success}"></i> ${message}`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Drag and drop
    imageUpload.addEventListener('dragover', e => {
        e.preventDefault();
        imageUpload.style.backgroundColor = '#f0f0f0';
    });

    imageUpload.addEventListener('dragleave', () => {
        imageUpload.style.backgroundColor = '#fafafa';
    });

    imageUpload.addEventListener('drop', e => {
        e.preventDefault();
        imageUpload.style.backgroundColor = '#fafafa';
        if (e.dataTransfer.files.length) {
            selectedFile = e.dataTransfer.files[0];
            const reader = new FileReader();
            reader.onload = event => {
                let preview = imageUpload.querySelector('.image-preview');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.className = 'image-preview';
                    imageUpload.innerHTML = '';
                    imageUpload.appendChild(preview);
                }
                preview.src = event.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(selectedFile);
        }
    });

    // ===== Edit/Delete Menu Logic =====
    const editModal = document.getElementById('editPostModal');
    const editTextarea = document.getElementById('editPostContent');
    const editClose = editModal ? editModal.querySelector('.edit-close') : null;
    const editCancel = editModal ? editModal.querySelector('.cancel-edit') : null;
    const editSave = editModal ? editModal.querySelector('.save-edit') : null;
    let editingPostId = null;

    // Toggle dropdown menu
    document.addEventListener('click', (e) => {
        // Skip comment system clicks
        if (e.target.closest('.comment-section') || 
            e.target.closest('.load-comments-btn') ||
            e.target.classList.contains('comment-input') ||
            e.target.classList.contains('comment-submit-btn') ||
            e.target.classList.contains('reply-submit-btn') ||
            e.target.closest('.comment-action')) {
            return;
        }

        // Toggle menu when clicking the menu-trigger button
        const menuTrigger = e.target.closest('.menu-trigger');
        if (menuTrigger) {
            e.preventDefault();
            e.stopPropagation();
            const postMenu = menuTrigger.closest('.post-menu');
            if (postMenu) {
                const isOpen = postMenu.classList.contains('open');
                document.querySelectorAll('.post-menu.open').forEach(m => {
                    if (m !== postMenu) m.classList.remove('open');
                });
                if (isOpen) {
                    postMenu.classList.remove('open');
                } else {
                    postMenu.classList.add('open');
                }
            }
            return;
        }

        const menuItem = e.target.closest('.post-menu .menu-item');
        if (menuItem) {
            const postMenu = menuItem.closest('.post-menu');
            if (postMenu) {
                postMenu.classList.remove('open');
            }
        }

        // Close menus when clicking outside
        if (!e.target.closest('.post-menu')) {
            document.querySelectorAll('.post-menu.open').forEach(m => m.classList.remove('open'));
        }
    });

    // Edit click
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.menu-item.edit-post');
        if (!btn) return;
        const postId = btn.dataset.postId;
        const feedEl = document.querySelector(`.feed[data-post-id="${postId}"]`);
        if (!feedEl) return;

        const content = feedEl.getAttribute('data-post-content') || '';
        editingPostId = postId;
        if (editTextarea) {
            editTextarea.value = content;
            editSave.disabled = content.trim().length === 0;
        }
        if (editModal) {
            editModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });

    // Delete click
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.menu-item.delete-post');
        if (!btn) return;
        const postId = btn.dataset.postId;
        const confirmDel = confirm('Delete this post? This cannot be undone.');
        if (!confirmDel) return;
        try {
            const formData = new FormData();
            formData.append('sub_action', 'delete');
            formData.append('post_id', postId);
            const res = await fetch(BASE_PATH + 'index.php?controller=Posts&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                const feedEl = document.querySelector(`.feed[data-post-id="${postId}"]`);
                if (feedEl) feedEl.remove();
                showToast('Post deleted', 'success');
            } else {
                showToast(data.message || 'Delete failed', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Server error', 'error');
        }
    });

    // Edit modal interactions
    if (editTextarea) {
        editTextarea.addEventListener('input', () => {
            editSave.disabled = editTextarea.value.trim().length === 0;
        });
    }

    const closeEditModal = () => {
        if (!editModal) return;
        editModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        editingPostId = null;
    };

    if (editClose) editClose.addEventListener('click', closeEditModal);
    if (editCancel) editCancel.addEventListener('click', closeEditModal);

    if (editSave) {
        editSave.addEventListener('click', async () => {
            const content = (editTextarea?.value || '').trim();
            if (!editingPostId || !content) return;
            try {
                const formData = new FormData();
                formData.append('sub_action', 'update');
                formData.append('post_id', editingPostId);
                formData.append('content', content);
                const res = await fetch(BASE_PATH + 'index.php?controller=Posts&action=handleAjax', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    const feedEl = document.querySelector(`.feed[data-post-id="${editingPostId}"]`);
                    if (feedEl) {
                        feedEl.setAttribute('data-post-content', content);
                        const captionP = feedEl.querySelector('.caption p');
                        if (captionP) {
                            const username = captionP.querySelector('b')?.textContent || '';
                            captionP.innerHTML = `<b>${username}</b> ${escapeHtml(content)}`;
                        }
                    }
                    showToast('Post updated', 'success');
                    closeEditModal();
                } else {
                    showToast(data.message || 'Update failed', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Server error', 'error');
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

}); // ← CLOSING the DOMContentLoaded callback