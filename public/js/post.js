
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
});
