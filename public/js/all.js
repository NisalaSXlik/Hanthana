
// Generate random posts on page load
document.addEventListener('DOMContentLoaded', function() {
    // Generate random likes and comments for existing posts
    document.querySelectorAll('.feed').forEach((feed, index) => {
        const likeCount = feed.querySelector('.liked-by p');
        const commentCount = feed.querySelector('.comments');
        
        if (likeCount) {
            const randomLikes = Math.floor(Math.random() * 100) + 50;
            likeCount.innerHTML = likeCount.innerHTML.replace(/\d+ others/, `${randomLikes} others`);
        }
        
        if (commentCount) {
            const randomComments = Math.floor(Math.random() * 30) + 10;
            commentCount.textContent = `View all ${randomComments} comments`;
        }
    });
    
    // Add interactive like buttons
    document.querySelectorAll('.uil-heart').forEach(heart => {
        heart.addEventListener('click', function() {
            this.classList.toggle('liked');
            if (this.classList.contains('liked')) {
                this.style.color = 'var(--color-danger)';
            } else {
                this.style.color = '';
            }
        });
    });
});

// Auto-update notification count every minute
setInterval(() => {
    const notificationCount = document.querySelector('.notification-count');
    const currentCount = parseInt(notificationCount.textContent);
    if (currentCount < 15) {
        const newCount = currentCount + Math.floor(Math.random() * 3);
        notificationCount.textContent = newCount > 9 ? '9+' : newCount;
    }
}, 60000);

document.addEventListener('DOMContentLoaded', () => {
    const menuItems = document.querySelectorAll('.menu-item');
    const contentSections = document.querySelectorAll('.content-section');

    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default link behavior

            const target = item.getAttribute('data-target');
            
            // Hide all content sections
            contentSections.forEach(section => {
                section.classList.remove('active');
            });

            // Show the selected section
            document.getElementById(target).classList.add('active');

            // Update active menu item
            menuItems.forEach(menuItem => {
                menuItem.classList.remove('active');
            });
            item.classList.add('active');

            // (Optional) Load content dynamically (e.g., fetch posts for Discover)
            if (target === 'discover') {
                loadDiscoverContent();
            }
        });
    });
});

// Optional: Fetch Discover content dynamically
function loadDiscoverContent() {
    fetch('/api/discover')
        .then(res => res.json())
        .then(data => {
            document.getElementById('discover').innerHTML = `
                <div class="discover-grid">
                    ${data.posts.map(post => `
                        <div class="discover-item">
                            <img src="${post.image}">
                            <div class="item-overlay">
                                <span><i class="uil uil-heart"></i> ${post.likes}</span>
                                <span><i class="uil uil-comment"></i> ${post.comments}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        })
        .catch(err => console.error("Error loading discover content:", err));
}

// Enhanced JavaScript with better event handling
const profileDropdown = document.getElementById('profileDropdown');
const dropdownMenu = profileDropdown.querySelector('.profile-dropdown');

// Toggle dropdown
profileDropdown.addEventListener('click', function(e) {
    e.stopPropagation();
    dropdownMenu.classList.toggle('show');
});

// Close when clicking outside
document.addEventListener('click', function() {
    dropdownMenu.classList.remove('show');
});

// Close when pressing ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        dropdownMenu.classList.remove('show');
    }
});

// Add this to your myFeed.js
document.addEventListener('DOMContentLoaded', function() {
    // Friend request handling
    const acceptButtons = document.querySelectorAll('.accept-btn');
    const declineButtons = document.querySelectorAll('.decline-btn');
    const toastContainer = document.getElementById('toastContainer');
    const friendRequestsContainer = document.querySelector('.friend-requests');
    acceptButtons.forEach(button => {
        button.addEventListener('click', function() {
            const request = this.closest('.request');
            const userName = request.querySelector('h5').textContent;
            
            // Remove the request
            request.remove();
            
            // Update badge count
            updateRequestCount();
            
            // Show toast notification
            showToast(`You are now friends with ${userName}!`, 'success');
        });
    });
    
    declineButtons.forEach(button => {
        button.addEventListener('click', function() {
            const request = this.closest('.request');
            
            // Remove the request
            request.remove();
            
            // Update badge count
            updateRequestCount();
            
            // Optional: Show declined notification
            showToast('Request declined', 'info');
        });
    });
    
    function updateRequestCount() {
        const badge = document.querySelector('.badge');
        const remainingRequests = document.querySelectorAll('.request').length;
        badge.textContent = `(${remainingRequests})`;
        
        if (remainingRequests === 0) {
            badge.style.display = 'none';
        }
    }
    
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<i class="uil uil-check-circle"></i> ${message}`;
        toastContainer.appendChild(toast);
        
        // Remove toast after animation
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});


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


// Handle login/signup button clicks
document.querySelector('.btn-login').addEventListener('click', () => {
    window.location.href = 'login.html';
});

document.querySelector('.btn-primary').addEventListener('click', () => {
    window.location.href = 'register.html';
});

function protectedContentLoad() {
    console.log("HEllo");
    // Get modal element
    const signupModal = document.getElementById('signupModal');
    const closeModal = document.getElementById('closeModal');
    const loginBtn = document.getElementById('loginBtn');
    const signupBtn = document.getElementById('signupBtn');



    
    // Function to show modal
    function showModal(e) {
        e.preventDefault();
        e.stopPropagation();
        signupModal.style.display = 'flex'; // Change display to flex
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
    
    // Function to hide modal
    function hideModal() {
        signupModal.style.display = 'none'; // Change display to none
        document.body.style.overflow = ''; // Re-enable scrolling
    }
    
    // Elements that should trigger the modal
    const protectedElements = [
        // Navbar buttons
        document.querySelector('.nav-right .btn-primary'), // Create button
        document.querySelector('.calendar-icon'), // Calendar
        document.querySelector('.notification'), // Notification
        
        // Post interaction buttons (need to handle multiple elements)
        ...document.querySelectorAll('.discover-item .uil-heart'), // Like buttons
        ...document.querySelectorAll('.discover-item .uil-comment'), // Comment buttons
        
        // Follow buttons
        ...document.querySelectorAll('.follow-btn')
    ];
    
    // Add click handlers to all protected elements

    console.log(protectedElements);
    protectedElements.forEach(element => {
        if (element) {
            // For single elements
            if (element.addEventListener) {
                element.addEventListener('click', showModal);
            } 
            // For NodeLists (querySelectorAll results)
            else if (element.length) {
                element.forEach(el => el.addEventListener('click', showModal));
            }
        }
    });
    
    // Close modal handlers
    if (closeModal) {
        closeModal.addEventListener('click', hideModal);
    }
    
    // Close when clicking outside modal content
    signupModal.addEventListener('click', function(e) {
        if (e.target === signupModal) {
            hideModal();
        }
    });
    
    // Close with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && signupModal.style.display === 'flex') {
            hideModal();
        }
    });
};