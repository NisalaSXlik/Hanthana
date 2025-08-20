// feed.js - Handles the main feed functionality (posts, likes, comments, infinite scroll)

document.addEventListener('DOMContentLoaded', function() {
    // Load initial posts
    loadFeedPosts();

    // Set up infinite scroll
    setupInfiniteScroll();

    // Initialize interactive elements (likes, comments)
    initPostInteractions();
});

// Render posts to the DOM
function renderPosts(posts) {
    const feedContainer = document.querySelector('.feed-container');
    
    posts.forEach(post => {
        const postElement = createPostElement(post);
        feedContainer.appendChild(postElement);
    });

    // Re-initialize interactions for new posts
    initPostInteractions();
}

// Create HTML for a single post
function createPostElement(post) {
    const postElement = document.createElement('div');
    postElement.className = 'feed-post';
    postElement.dataset.postId = post.id;

    postElement.innerHTML = `
        <div class="post-header">
            <img src="${post.user.avatar}" alt="${post.user.name}" class="post-avatar">
            <div class="post-user">
                <h5>${post.user.name}</h5>
                <span>${post.location || ''}</span>
            </div>
            <i class="uil uil-ellipsis-h more-btn"></i>
        </div>
        <div class="post-image">
            <img src="${post.image}" alt="Post image">
        </div>
        <div class="post-actions">
            <div class="interaction-buttons">
                <i class="uil uil-heart like-btn ${post.isLiked ? 'liked' : ''}"></i>
                <i class="uil uil-comment comment-btn"></i>
                <i class="uil uil-share-alt share-btn"></i>
            </div>
            <i class="uil uil-bookmark save-btn ${post.isSaved ? 'saved' : ''}"></i>
        </div>
        <div class="liked-by">
            <p>Liked by <strong>${post.likes[0]?.name || 'user'}</strong> and <span>${post.likeCount} others</span></p>
        </div>
        <div class="post-caption">
            <p><strong>${post.user.name}</strong> ${post.caption}</p>
        </div>
        <div class="comments">
            <p class="view-comments">View all ${post.commentCount} comments</p>
            ${post.comments.slice(0, 2).map(comment => `
                <div class="comment">
                    <p><strong>${comment.user.name}</strong> ${comment.text}</p>
                </div>
            `).join('')}
        </div>
        <div class="post-time">
            <p>${formatTime(post.createdAt)}</p>
        </div>
        <div class="add-comment">
            <input type="text" placeholder="Add a comment...">
            <button class="post-comment-btn">Post</button>
        </div>
    `;

    return postElement;
}

// Initialize like/save/comment interactions
function initPostInteractions() {
    // Like button functionality
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!isUserLoggedIn()) {
                showLoginModal();
                return;
            }

            const postId = this.closest('.feed-post').dataset.postId;
            const isLiked = this.classList.contains('liked');

            toggleLike(postId, isLiked).then(success => {
                if (success) {
                    this.classList.toggle('liked');
                    updateLikeCount(this.closest('.feed-post'), !isLiked);
                }
            });
        });
    });

    // Save button functionality
    document.querySelectorAll('.save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!isUserLoggedIn()) {
                showLoginModal();
                return;
            }

            const postId = this.closest('.feed-post').dataset.postId;
            const isSaved = this.classList.contains('saved');

            toggleSave(postId, isSaved).then(success => {
                if (success) {
                    this.classList.toggle('saved');
                }
            });
        });
    });

    // Comment submission
    document.querySelectorAll('.post-comment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!isUserLoggedIn()) {
                showLoginModal();
                return;
            }

            const postId = this.closest('.feed-post').dataset.postId;
            const commentInput = this.previousElementSibling;
            const commentText = commentInput.value.trim();

            if (commentText) {
                postComment(postId, commentText).then(comment => {
                    if (comment) {
                        addCommentToDOM(this.closest('.feed-post'), comment);
                        commentInput.value = '';
                    }
                });
            }
        });
    });
}

// Toggle like via API
async function toggleLike(postId, isLiked) {
    try {
        const response = await fetch(`/api/posts/${postId}/like`, {
            method: isLiked ? 'DELETE' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });

        return response.ok;
    } catch (error) {
        console.error('Error toggling like:', error);
        showToast('Failed to update like', 'error');
        return false;
    }
}

// Toggle save via API
async function toggleSave(postId, isSaved) {
    try {
        const response = await fetch(`/api/posts/${postId}/save`, {
            method: isSaved ? 'DELETE' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`
            }
        });

        return response.ok;
    } catch (error) {
        console.error('Error toggling save:', error);
        showToast('Failed to update saved post', 'error');
        return false;
    }
}

// Post comment via API
async function postComment(postId, text) {
    try {
        const response = await fetch(`/api/posts/${postId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: JSON.stringify({ text })
        });

        if (response.ok) {
            return await response.json();
        }
        return null;
    } catch (error) {
        console.error('Error posting comment:', error);
        showToast('Failed to post comment', 'error');
        return null;
    }
}

// Add new comment to DOM
function addCommentToDOM(postElement, comment) {
    const commentsContainer = postElement.querySelector('.comments');
    const viewComments = postElement.querySelector('.view-comments');
    const commentCount = parseInt(viewComments.textContent.match(/\d+/)[0]);

    // Create new comment element
    const commentElement = document.createElement('div');
    commentElement.className = 'comment';
    commentElement.innerHTML = `<p><strong>${comment.user.name}</strong> ${comment.text}</p>`;

    // Insert before "View all comments"
    commentsContainer.insertBefore(commentElement, viewComments);

    // Update comment count
    viewComments.textContent = `View all ${commentCount + 1} comments`;
}

// Update like count display
function updateLikeCount(postElement, isIncrement) {
    const likedBy = postElement.querySelector('.liked-by p span');
    const currentCount = parseInt(likedBy.textContent.match(/\d+/)[0]);
    const newCount = isIncrement ? currentCount + 1 : Math.max(0, currentCount - 1);

    likedBy.textContent = `${newCount} others`;
}

// Set up infinite scroll
function setupInfiniteScroll() {
    let isLoading = false;
    let offset = 5; // Initial offset after first load

    window.addEventListener('scroll', () => {
        const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
        const isNearBottom = scrollTop + clientHeight >= scrollHeight - 500;

        if (isNearBottom && !isLoading) {
            isLoading = true;
            loadFeedPosts(offset);
            offset += 5; // Increase offset for next load
            setTimeout(() => { isLoading = false; }, 1000);
        }
    });
}

// Format time (e.g., "2 hours ago")
function formatTime(timestamp) {
    const now = new Date();
    const postTime = new Date(timestamp);
    const diffInSeconds = Math.floor((now - postTime) / 1000);

    if (diffInSeconds < 60) return `${diffInSeconds}s ago`;
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
}

// Helper function to get auth token
function getAuthToken() {
    return localStorage.getItem('authToken');
}