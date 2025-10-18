
// post sharing 

console.log('post.js loaded!');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired in post.js');
    
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
    
    // Post type switching
    postTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            postTypeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            if (this.dataset.type === 'event') {
                eventDetails.style.display = 'block';
            } else {
                eventDetails.style.display = 'none';
            }
        });
    });
    
    // Image upload handling
    imageUpload.addEventListener('click', function() {
        postImageInput.click();
    });
    
    postImageInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(event) {
                // Create image preview if it doesn't exist
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
            
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    
    // Tag validation
    postTagsInput.addEventListener('input', function() {
        const tags = this.value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        tagCount.textContent = `${tags.length}/5 tags`;
        
        // Enable share button only if there are at least 5 tags and an image is selected
        const hasImage = postImageInput.files && postImageInput.files.length > 0;
        shareBtn.disabled = !(tags.length >= 5 && hasImage);
    });
    
    // Share button functionality
    shareBtn.addEventListener('click', function() {
        // Get all form values
        const postType = document.querySelector('.post-type-btn.active').dataset.type;
        const caption = document.getElementById('postCaption').value;
        const tags = document.getElementById('postTags').value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        const imageFile = postImageInput.files[0];
        
        let eventData = null;
        if (postType === 'event') {
            eventData = {
                title: document.getElementById('eventTitle').value,
                date: document.getElementById('eventDate').value,
                location: document.getElementById('eventLocation').value
            };
        }
        
        // Here you would normally send this data to your backend
        // For this example, we'll just show a success message
        showToast('Post shared successfully!', 'success');
        
        // Close the modal and reset form
        closeModal();
        resetForm();
        
        // In a real app, you would add the new post to the feed here
    });
    
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
        shareBtn.disabled = true;
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
            postImageInput.files = e.dataTransfer.files;
            const event = new Event('change');
            postImageInput.dispatchEvent(event);
        }
    });
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
    document.addEventListener('click', (e) => {
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
    });

    // Delete click
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.menu-item.delete-post');
        if (!btn) return;
        const postId = btn.dataset.postId;
        const confirmDel = confirm('Delete this post? This cannot be undone.');
        if (!confirmDel) return;
        try {
            const res = await fetch('../../app/controllers/PostController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_post&post_id=${encodeURIComponent(postId)}`
            });
            const data = await res.json();
            if (data.success) {
                const feedEl = document.querySelector(`.feed[data-post-id="${postId}"]`);
                if (feedEl) feedEl.remove();
                if (typeof showToast === 'function') showToast('Post deleted', 'success');
            } else {
                if (typeof showToast === 'function') showToast(data.message || 'Delete failed', 'error');
            }
        } catch (err) {
            console.error(err);
            if (typeof showToast === 'function') showToast('Server error', 'error');
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
                const res = await fetch('../../app/controllers/PostController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update_post&post_id=${encodeURIComponent(editingPostId)}&content=${encodeURIComponent(content)}`
                });
                const data = await res.json();
                if (data.success) {
                    const feedEl = document.querySelector(`.feed[data-post-id=\"${editingPostId}\"]`);
                    if (feedEl) {
                        feedEl.setAttribute('data-post-content', content);
                        const captionP = feedEl.querySelector('.caption p');
                        if (captionP) {
                            const username = captionP.querySelector('b')?.textContent || '';
                            captionP.innerHTML = `<b>${username}</b> ${escapeHtml(content)}`;
                        }
                    }
                    if (typeof showToast === 'function') showToast('Post updated', 'success');
                    closeEditModal();
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Update failed', 'error');
                }
            } catch (err) {
                console.error(err);
                if (typeof showToast === 'function') showToast('Server error', 'error');
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
