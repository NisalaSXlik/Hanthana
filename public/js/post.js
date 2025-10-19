// post sharing 

document.addEventListener('DOMContentLoaded', function() {
    // Post creation modal functionality
    const postModal = document.getElementById('postModal');
    const createBtn = document.querySelector('.btn-primary'); // Your create button
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.cancel-btn');
    const shareBtn = document.querySelector('.share-btn');
    const postTypeBtns = document.querySelectorAll('.post-type-btn');
    const eventDetails = document.getElementById('eventDetails');
    const imageUpload = document.querySelector('.image-upload');
    const postImageInput = document.getElementById('postImage');
    const postTagsInput = document.getElementById('postTags');
    const tagCount = document.querySelector('.tag-count');
    
    // Show modal when Create button is clicked
    createBtn.addEventListener('click', function() {
        resetForm(); 
        postModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
    
    // Close modal
    function closeModal() {
        postModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    let postType = 'general';
    let selectedFile = null;  // Add this: Variable to hold the selected file

    // Post type switching
    postTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            postTypeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            postType = this.dataset.type;
            eventDetails.style.display = postType === 'event' ? 'block' : 'none';
        });
    });
    
    // Image upload handling
    imageUpload.addEventListener('click', function() {
        postImageInput.click();
    });
    
    // Image upload handling (click)
    postImageInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            selectedFile = e.target.files[0];  // Store the file
            // Create image preview if it doesn't exist
            let preview = imageUpload.querySelector('.image-preview');
            if (!preview) {
                preview = document.createElement('img');
                preview.className = 'image-preview';
                imageUpload.innerHTML = '';
                imageUpload.appendChild(preview);
            }
            
            const reader = new FileReader();
            
            reader.onload = function(event) {
                preview.src = event.target.result;
                preview.style.display = 'block';
            };
            
            reader.readAsDataURL(e.target.files[0]);
        }
        // updateShareButton();  // Commented out: Remove call to update button state
    });
    
    // Tag validation
    postTagsInput.addEventListener('input', function() {
        const tags = this.value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        tagCount.textContent = `${tags.length}/5 tags`;
        
        // updateShareButton();  // Commented out: Remove call to update button state
    });
    
    // Share button functionality
    shareBtn.addEventListener('click', function() {
        console.log('Share button clicked!');  // Added: Confirm button triggers
        
        clearFormErrors();

        // Validation on click
        const caption = document.getElementById('postCaption').value.trim();
        const tagsInput = document.getElementById('postTags').value.trim();
        const tagArray = tagsInput.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        const hasImage = selectedFile !== null;
        
        let errors = [];
        if (!caption) errors.push({ field: 'caption', message: 'Caption is required.' });
        if (tagArray.length < 5) errors.push({ field: 'tags', message: 'At least 5 tags are required.' });
        if (postType === 'event') {
            const eventTitle = document.getElementById('eventTitle').value.trim();
            const eventDate = document.getElementById('eventDate').value.trim();
            const eventLocation = document.getElementById('eventLocation').value.trim();
            if (!eventTitle) errors.push({ field: 'eventTitle', message: 'Event title is required.' });
            if (!eventDate) errors.push({ field: 'eventDate', message: 'Event date is required.' });
            if (!eventLocation) errors.push({ field: 'eventLocation', message: 'Event location is required.' });
        }

        if (errors.length > 0) {
            showFormErrors(errors);
            return;  // Stop here, don't send request
        }

        // Get all form values
        const formData = new FormData();
        formData.append('caption', document.getElementById('postCaption').value || '');
        formData.append('tags', document.getElementById('postTags').value || '');
        formData.append('postType', postType);  // 'general' or 'event'
        if (postType === 'event') {
            formData.append('eventTitle', document.getElementById('eventTitle').value || '');
            formData.append('eventDate', document.getElementById('eventDate').value || '');
            formData.append('eventLocation', document.getElementById('eventLocation').value || '');
        }
        if (selectedFile) {  
            formData.append('image', selectedFile);
        }

        fetch(BASE_PATH + '/index.php?controller=Posts&action=create', {
            method: 'POST',
            body: formData,
            // headers: { 'Authorization': `Bearer ${getAuthToken()}` } // Commented out: If authenticated
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();  // Get as text first to debug
        })
        .then(text => {
            console.log('Raw response text:', text);  // Add this: Log the raw response
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showToast('Post shared successfully!', 'success');
                    closeModal();
                    resetForm();
                } else {
                    showToast('Error: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                showToast('Server error: Check console', 'error');
            }
        })
        .catch(err => {
            console.error('Fetch Error:', err);
            showToast('Failed to send post', 'error');
        });
    });
    
    // Add functions for inline errors
    function showFormErrors(errors) {
        errors.forEach(error => {
            const fieldElement = document.getElementById(error.field === 'caption' ? 'postCaption' : error.field === 'tags' ? 'postTags' : error.field);
            if (fieldElement) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.textContent = error.message;
                fieldElement.parentNode.insertBefore(errorDiv, fieldElement.nextSibling);
            }
        });
    }

    function clearFormErrors() {
        const errorElements = document.querySelectorAll('.form-error');
        errorElements.forEach(el => el.remove());
    }

    function resetForm() {
        document.getElementById('postCaption').value = '';
        document.getElementById('postTags').value = '';
        tagCount.textContent = '0/5 tags';
        postImageInput.value = '';
        document.querySelector('.image-upload').innerHTML = `
            <i class="uil uil-image-upload"></i>
            <p>Drag photos and videos here or click to browse</p>
        `;
        document.getElementById('eventTitle').value = '';
        document.getElementById('eventDate').value = '';
        document.getElementById('eventLocation').value = '';
        document.querySelector('.post-type-btn[data-type="general"]').click();
        selectedFile = null;  // Reset the file variable
        // updateShareButton();  // Commented out: Remove call to update button state
    }
    
    // Unified Toast Notification System
    function showToast(message, type = 'success') {
        // Create or find toast container
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Add appropriate icon
        const icons = {
            success: 'uil-check-circle',
            error: 'uil-times-circle',
            info: 'uil-info-circle'
        };
        toast.innerHTML = `<i class="uil ${icons[type] || icons.success}"></i> ${message}`;
        
        toastContainer.appendChild(toast);
        
        // Auto-remove after delay
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Drag and drop for image upload
    imageUpload.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#f0f0f0';
    });
    
    imageUpload.addEventListener('dragleave', function() {
        this.style.backgroundColor = '#fafafa';
    });
    
    imageUpload.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#fafafa';
        
        if (e.dataTransfer.files.length) {
            selectedFile = e.dataTransfer.files[0];  // Store the file here
            // Trigger preview manually
            const reader = new FileReader();
            reader.onload = function(event) {
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
            // updateShareButton();  // Commented out: Remove call to update button state
        }
    });
    
    // Commented out: Remove the entire updateShareButton function
    /*
    function updateShareButton() {
        const tags = postTagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        const hasImage = selectedFile !== null;  // Check the variable
        shareBtn.disabled = !(tags.length >= 5 && hasImage);
        console.log('Share button state:', shareBtn.disabled);
    }
    */
    // ---------------------------------------------
    // Per-post Edit/Delete menu + Edit modal logic
    // ---------------------------------------------
    const editModal = document.getElementById('editPostModal');
    const editTextarea = document.getElementById('editPostContent');
    const editClose = editModal ? editModal.querySelector('.edit-close') : null;
    const editCancel = editModal ? editModal.querySelector('.cancel-edit') : null;
    const editSave = editModal ? editModal.querySelector('.save-edit') : null;
    let editingPostId = null;

    // Toggle dropdown menu
    document.addEventListener('click', (e) => {
        console.log('Click detected on:', e.target);
        console.log('Target classes:', e.target.className);
        
        // Check if clicking on the button or icon inside
        const trigger = e.target.closest('.menu-trigger');
        console.log('Trigger element found:', trigger);
        
        if (trigger && trigger.closest('.post-menu')) {
            e.preventDefault();
            e.stopPropagation();
            const menu = trigger.closest('.post-menu');
            console.log('Post menu element:', menu);
            const alreadyOpen = menu.classList.contains('open');
            console.log('Already open:', alreadyOpen);
            
            // Close any other open menus
            document.querySelectorAll('.post-menu.open').forEach(m => {
                console.log('Closing menu:', m);
                m.classList.remove('open');
            });
            
            if (!alreadyOpen) {
                menu.classList.add('open');
                console.log('Menu opened, classes:', menu.className);
            }
            return;
        }

        // Close menus when clicking outside
        if (!e.target.closest('.post-menu')) {
            document.querySelectorAll('.post-menu.open').forEach(m => m.classList.remove('open'));
        }
    });

    // Edit click
    document.addEventListener('click', async (e) => {  // Add async
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
            editModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        // Remove the broken fetch here—move to editSave
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
            formData.append('post_id', postId);
            const res = await fetch(BASE_PATH + '/index.php?controller=Posts&action=delete', {  // Correct URL
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
        editModal.style.display = 'none';
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
                formData.append('post_id', editingPostId);
                formData.append('content', content);
                const res = await fetch(BASE_PATH + '/index.php?controller=Posts&action=update', {  // Correct URL
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    // Update UI
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
});