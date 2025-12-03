// Popular page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs
    initializeTabs();
    
    // Load initial content
    loadPopularGroups();
    loadTrendingPosts();
});

/**
 * Initialize tab functionality
 */
function initializeTabs() {
    const tabs = document.querySelectorAll('.popular-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show target tab content
            const targetContent = document.getElementById(`${targetTab}Tab`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
}

/**
 * Load popular groups
 */
async function loadPopularGroups() {
    const container = document.getElementById('popularGroupsContainer');
    
    try {
        const response = await fetch(`${BASE_PATH}/index.php?controller=Popular&ajax_action=getPopularGroups&limit=12`);
        const data = await response.json();
        
        if (data.success && data.groups && data.groups.length > 0) {
            container.innerHTML = data.groups.map(group => createGroupCard(group)).join('');
            
            // Add click handlers for join buttons
            container.querySelectorAll('.group-join-btn').forEach(btn => {
                btn.addEventListener('click', handleGroupJoin);
            });
            
            // Add click handlers for group cards (except button)
            container.querySelectorAll('.popular-group-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!e.target.classList.contains('group-join-btn')) {
                        const groupId = this.dataset.groupId;
                        window.location.href = `${BASE_PATH}/index.php?controller=Group&action=index&group_id=${groupId}`;
                    }
                });
            });
        } else {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="uil uil-users-alt"></i>
                    <h3>No Popular Groups Yet</h3>
                    <p>Be the first to create a group!</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading popular groups:', error);
        container.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="uil uil-exclamation-triangle"></i>
                <h3>Error Loading Groups</h3>
                <p>Please try again later</p>
            </div>
        `;
    }
}

/**
 * Create HTML for a group card
 */
function createGroupCard(group) {
    const displayPicture = group.display_picture 
        ? `<img src="${BASE_PATH}/public/${group.display_picture}" alt="${group.name}">` 
        : `<i class="uil uil-users-alt"></i>`;
    
    let buttonHtml = '';
    if (group.is_member == 1) {
        buttonHtml = '<button class="group-join-btn joined" disabled>Joined</button>';
    } else if (group.has_pending_request == 1) {
        buttonHtml = '<button class="group-join-btn pending" disabled>Pending</button>';
    } else {
        buttonHtml = `<button class="group-join-btn" data-group-id="${group.group_id}">Join</button>`;
    }
    
    return `
        <div class="popular-group-card" data-group-id="${group.group_id}">
            <div class="group-card-header">
                <div class="group-card-icon">
                    ${displayPicture}
                </div>
                <div class="group-card-info">
                    <h4>${escapeHtml(group.name)}</h4>
                    <p>${group.member_count || 0} members</p>
                </div>
            </div>
            <div class="group-card-description">
                ${escapeHtml(group.description || 'No description available')}
            </div>
            <div class="group-card-footer">
                <div class="group-card-stats">
                    <span><i class="uil uil-postcard"></i> ${group.post_count || 0}</span>
                    <span><i class="uil uil-shield-check"></i> ${group.privacy_status}</span>
                </div>
                ${buttonHtml}
            </div>
        </div>
    `;
}

/**
 * Handle group join button click
 */
async function handleGroupJoin(e) {
    e.stopPropagation();
    const btn = e.target;
    const groupId = btn.dataset.groupId;
    
    if (!groupId) return;
    
    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Joining...';
    
    try {
        const response = await fetch(`${BASE_PATH}/app/controllers/GroupController.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sub_action: 'join',
                group_id: groupId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            btn.textContent = 'Joined';
            btn.classList.add('joined');
            // Update sidebar member count
            if (window.updateSidebarGroupMemberCount) {
                window.updateSidebarGroupMemberCount(groupId, 1);
            }
            showNotification('Successfully joined the group!', 'success');
        } else {
            btn.disabled = false;
            btn.textContent = originalText;
            showNotification(data.message || 'Failed to join group', 'error');
        }
    } catch (error) {
        console.error('Error joining group:', error);
        btn.disabled = false;
        btn.textContent = originalText;
        showNotification('An error occurred', 'error');
    }
}

/**
 * Load trending posts
 */
async function loadTrendingPosts() {
    const container = document.getElementById('trendingPostsContainer');
    
    try {
        const response = await fetch(`${BASE_PATH}/index.php?controller=Popular&ajax_action=getTrendingPosts&limit=10`);
        const data = await response.json();
        
        console.log('Trending posts data:', data); // Debug log
        
        if (data.success && data.posts && data.posts.length > 0) {
            console.log('First post:', data.posts[0]); // Debug first post
            
            // Import post rendering from feed if available, otherwise simple display
            if (typeof window.renderPost === 'function') {
                container.innerHTML = data.posts.map(post => window.renderPost(post)).join('');
            } else {
                container.innerHTML = data.posts.map(post => createSimplePostCard(post)).join('');
            }
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="uil uil-chart-line"></i>
                    <h3>No Trending Posts</h3>
                    <p>Check back later for trending content</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading trending posts:', error);
        container.innerHTML = `
            <div class="empty-state">
                <i class="uil uil-exclamation-triangle"></i>
                <h3>Error Loading Posts</h3>
                <p>Please try again later</p>
            </div>
        `;
    }
}

/**
 * Create simple post card (fallback)
 */
function createSimplePostCard(post) {
    // Handle profile picture
    let avatarUrl = `${BASE_PATH}/public/images/avatars/defaultProfilePic.png`;
    if (post.profile_picture) {
        if (post.profile_picture.startsWith('http')) {
            avatarUrl = post.profile_picture;
        } else if (post.profile_picture.startsWith('images/') || post.profile_picture.startsWith('/images/')) {
            avatarUrl = `${BASE_PATH}/public/${post.profile_picture.replace(/^\//, '')}`;
        } else {
            avatarUrl = `${BASE_PATH}/public/images/avatars/${post.profile_picture}`;
        }
    }
    
    const fullName = `${post.first_name || ''} ${post.last_name || ''}`.trim();
    const username = post.username || 'unknown';
    
    // Truncate content if too long
    const content = post.content || 'No content';
    let displayContent = content;

    // Check for structured question format (Problem, Context, etc.)
    // If found, only show the Problem part
    const problemMatch = content.match(/Problem:\s*([\s\S]*?)\s*(?:Context:|Attempts:|Expected Outcome:|$)/i);
    if (problemMatch && problemMatch[1]) {
        displayContent = problemMatch[1].trim();
        // Add ellipsis if there's more content hidden
        if (content.length > displayContent.length + 20) {
             displayContent += '...'; 
        }
    } else {
        const maxLength = (post.image_url || post.file_url || post.video_url) ? 200 : 300;
        displayContent = content.length > maxLength ? content.substring(0, maxLength) + '...' : content;
    }
    
    // Handle media - check both image_url and file_url
    let mediaHtml = '';
    const imageField = post.image_url || post.file_url;
    
    console.log('Post ID:', post.post_id, 'Image field:', imageField); // Debug log
    
    if (imageField) {
        let imageUrl = imageField;
        
        // If the path doesn't start with BASE_PATH or http, construct the URL
        if (!imageUrl.startsWith('http') && !imageUrl.includes(BASE_PATH)) {
            const normalized = imageUrl.replace(/^\//, '');
            if (normalized.startsWith('public/')) {
                imageUrl = `${BASE_PATH}/${normalized}`;
            } else if (normalized.startsWith('images/') || normalized.startsWith('uploads/')) {
                imageUrl = `${BASE_PATH}/public/${normalized}`;
            } else {
                imageUrl = `${BASE_PATH}/public/uploads/${imageUrl.split('/').pop()}`;
            }
        }
        
        console.log('Final image URL:', imageUrl); // Debug log
        mediaHtml = `<img src="${imageUrl}" alt="Post image" onerror="this.style.display='none'; console.log('Failed to load image: ${imageUrl}')">`;
    } else if (post.video_url) {
        const videoUrl = post.video_url.startsWith('http') 
            ? post.video_url 
            : `${BASE_PATH}/public/${post.video_url}`;
        mediaHtml = `
            <video controls>
                <source src="${videoUrl}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        `;
    }
    
    return `
        <div class="post" data-post-id="${post.post_id}" style="cursor: pointer;" onclick="window.location.href='${BASE_PATH}/index.php?controller=Profile&action=index&user_id=${post.user_id}#post-${post.post_id}'">
            <div class="post-header">
                <div class="profile-picture">
                    <img src="${avatarUrl}" alt="${fullName}" onerror="this.src='${BASE_PATH}/public/images/avatars/defaultProfilePic.png'">
                </div>
                <div class="post-author-info">
                    <h4>${escapeHtml(fullName)}</h4>
                    <p>@${escapeHtml(username)}</p>
                    <small>${formatDate(post.created_at)}</small>
                </div>
            </div>
            <div class="post-content">
                <p>${escapeHtml(displayContent)}</p>
            </div>
            ${mediaHtml ? `<div class="post-media">${mediaHtml}</div>` : ''}
            <div class="post-stats">
                <span>
                    <i class="uil uil-thumbs-up"></i> 
                    ${post.upvote_count || 0} ${post.upvote_count == 1 ? 'Like' : 'Likes'}
                </span>
                <span>
                    <i class="uil uil-comment"></i> 
                    ${post.comment_count || 0} ${post.comment_count == 1 ? 'Comment' : 'Comments'}
                </span>
            </div>
        </div>
    `;
}

/**
 * Helper: Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

/**
 * Helper: Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString();
}

/**
 * Helper: Show notification
 */
function showNotification(message, type = 'info') {
    // If notification system exists, use it
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }
    
    // Simple fallback
    alert(message);
}

/**
 * Open post modal
 */
window.openPostModal = async function(postId) {
    try {
        const response = await fetch(`${BASE_PATH}/index.php?controller=Post&ajax_action=getPost&post_id=${postId}`);
        const data = await response.json();
        
        if (!data.success || !data.post) {
            showNotification('Failed to load post', 'error');
            return;
        }
        
        const post = data.post;
        
        // Handle profile picture
        let avatarUrl = `${BASE_PATH}/public/images/avatars/defaultProfilePic.png`;
        if (post.profile_picture) {
            if (post.profile_picture.startsWith('http')) {
                avatarUrl = post.profile_picture;
            } else if (post.profile_picture.startsWith('images/') || post.profile_picture.startsWith('/images/')) {
                avatarUrl = `${BASE_PATH}/public/${post.profile_picture.replace(/^\//, '')}`;
            } else {
                avatarUrl = `${BASE_PATH}/public/images/avatars/${post.profile_picture}`;
            }
        }
        
        const fullName = `${post.first_name || ''} ${post.last_name || ''}`.trim();
        
        // Handle media
        let mediaHtml = '';
        const imageField = post.image_url || post.file_url;
        
        if (imageField) {
            let imageUrl = '';
            if (imageField.startsWith('http')) {
                imageUrl = imageField;
            } else if (imageField.startsWith('images/') || imageField.startsWith('/images/') || 
                       imageField.startsWith('uploads/') || imageField.startsWith('/uploads/')) {
                imageUrl = `${BASE_PATH}/public/${imageField.replace(/^\//, '')}`;
            } else {
                imageUrl = `${BASE_PATH}/public/images/posts/${imageField}`;
            }
            mediaHtml = `<div class="modal-post-image"><img src="${imageUrl}" alt="Post image" onerror="this.parentElement.style.display='none'"></div>`;
        }
        
        // Create modal HTML
        const modalHtml = `
            <div class="post-modal-overlay" id="postModal" onclick="closePostModal(event)">
                <div class="post-modal-content" onclick="event.stopPropagation()">
                    <button class="modal-close-btn" onclick="closePostModal()">&times;</button>
                    
                    <div class="modal-post-header">
                        <div class="profile-picture">
                            <img src="${avatarUrl}" alt="${fullName}">
                        </div>
                        <div class="modal-author-info">
                            <h4>${escapeHtml(fullName)}</h4>
                            <p>@${escapeHtml(post.username || 'unknown')}</p>
                            <small>${formatDate(post.created_at)}</small>
                        </div>
                    </div>
                    
                    ${mediaHtml}
                    
                    <div class="modal-post-content">
                        <p>${escapeHtml(post.content || '')}</p>
                    </div>
                    
                    <div class="modal-action-buttons">
                        <div class="modal-interaction-buttons">
                            <i class="uil uil-arrow-up ${post.user_vote === 'upvote' ? 'liked' : ''}" data-vote-type="upvote" onclick="handleModalVote(${postId}, 'upvote')"></i>
                            <small id="upvote-count-${postId}">${post.upvote_count || 0}</small>
                            <i class="uil uil-arrow-down ${post.user_vote === 'downvote' ? 'liked' : ''}" data-vote-type="downvote" onclick="handleModalVote(${postId}, 'downvote')"></i>
                            <small id="downvote-count-${postId}">${post.downvote_count || 0}</small>
                            <i class="uil uil-comment"></i>
                            <small>${post.comment_count || 0}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('postModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
    } catch (error) {
        console.error('Error opening post modal:', error);
        showNotification('Failed to load post', 'error');
    }
}

/**
 * Close post modal
 */
window.closePostModal = function(event) {
    if (event && event.target !== event.currentTarget) return;
    
    const modal = document.getElementById('postModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

/**
 * Handle vote in modal
 */
window.handleModalVote = async function(postId, voteType) {
    try {
        const response = await fetch(`${BASE_PATH}/index.php?controller=Vote&action=vote`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `post_id=${postId}&vote_type=${voteType}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update counts in modal
            if (data.upvote_count !== undefined) {
                document.getElementById(`upvote-count-${postId}`).textContent = data.upvote_count;
            }
            if (data.downvote_count !== undefined) {
                document.getElementById(`downvote-count-${postId}`).textContent = data.downvote_count;
            }
            
            // Update arrow states
            const modal = document.getElementById('postModal');
            const upArrow = modal.querySelector('.uil-arrow-up');
            const downArrow = modal.querySelector('.uil-arrow-down');
            
            upArrow.classList.remove('liked');
            downArrow.classList.remove('liked');
            
            if (data.action === 'added' || data.action === 'updated') {
                if (voteType === 'upvote') {
                    upArrow.classList.add('liked');
                } else {
                    downArrow.classList.add('liked');
                }
            }
            
            if (typeof window.showToast === 'function') {
                window.showToast('Vote registered', 'success');
            }
        } else {
            showNotification(data.message || 'Vote failed', 'error');
        }
    } catch (error) {
        console.error('Vote error:', error);
        showNotification('Vote failed', 'error');
    }
}
