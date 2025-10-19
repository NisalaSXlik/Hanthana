class CommentSystem {
            constructor() {
                this.init();
            }
            
            init() {
                this.bindEvents();
            }
            
            bindEvents() {
                // Load comments when comment button is clicked
                document.addEventListener('click', (e) => {
                    if (e.target.closest('.load-comments-btn')) {
                        const postId = e.target.closest('.load-comments-btn').dataset.postId;
                        this.toggleCommentSection(postId);
                    }
                    
                    // Close comments
                    if (e.target.closest('.close-comments')) {
                        const commentSection = e.target.closest('.comment-section');
                        const postId = commentSection.dataset.postId;
                        this.hideCommentSection(postId);
                    }
                    
                    // Submit main comment
                    if (e.target.closest('.comment-submit-btn')) {
                        const btn = e.target.closest('.comment-submit-btn');
                        const postId = btn.dataset.postId;
                        this.submitComment(postId);
                    }
                    
                    // Submit reply
                    if (e.target.closest('.reply-submit-btn')) {
                        const btn = e.target.closest('.reply-submit-btn');
                        const commentId = btn.dataset.commentId;
                        this.submitReply(commentId);
                    }
                    
                    // Show reply form
                    if (e.target.closest('.reply-btn')) {
                        const commentId = e.target.closest('.reply-btn').dataset.commentId;
                        this.toggleReplyForm(commentId);
                    }
                });
                
                // Handle enter key in comment inputs
                document.addEventListener('keypress', (e) => {
                    if (e.target.classList.contains('comment-input') && e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        const postId = e.target.dataset.postId;
                        this.submitComment(postId);
                    }
                    
                    if (e.target.classList.contains('reply-input') && e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        const commentId = e.target.dataset.commentId;
                        this.submitReply(commentId);
                    }
                });
            }
            
            toggleCommentSection(postId) {
                const commentSection = document.querySelector(`.comment-section[data-post-id="${postId}"]`);
                const loadBtn = document.querySelector(`.load-comments-btn[data-post-id="${postId}"]`);
                if (commentSection.classList.contains('active')) {
                    this.hideCommentSection(postId);
                    if (loadBtn) loadBtn.classList.remove('active');
                } else {
                    this.showCommentSection(postId);
                    if (loadBtn) loadBtn.classList.add('active');
                }
            }
            
            showCommentSection(postId) {
                const commentSection = document.querySelector(`.comment-section[data-post-id="${postId}"]`);
                const container = document.getElementById(`comments-container-${postId}`);
                
                commentSection.classList.add('active');
                this.loadComments(postId);
            }
            
            hideCommentSection(postId) {
                const commentSection = document.querySelector(`.comment-section[data-post-id="${postId}"]`);
                commentSection.classList.remove('active');
            }
            
            async loadComments(postId) {
                const container = document.getElementById(`comments-container-${postId}`);
                if (!container) return;
                
                // Show loading
                container.innerHTML = '<div class="comments-loading">Loading comments...</div>';
                
                try {
                    const response = await fetch('../../app/controllers/CommentController.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=get_comments&post_id=${encodeURIComponent(postId)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.displayComments(container, data.comments || []);
                    } else {
                        container.innerHTML = `<div class="comments-loading">${data.message || 'Failed to load comments'}</div>`;
                    }
                    
                } catch (error) {
                    console.error('Error loading comments:', error);
                    container.innerHTML = '<div class="comments-loading">Error loading comments</div>';
                }
            }
            
            // Add helper function to process avatar URLs (matches PHP logic)
            processAvatar(rawAvatar) {
                if (!rawAvatar) return '../../public/images/avatars/default.png';
                if (rawAvatar.match(/^https?:\/\//)) return rawAvatar; // Full URL
                return '../../public/images/avatars/' + rawAvatar; // Filename
            }
            
            // Update displayComments to map data (no deep processing needed)
            displayComments(container, comments) {
                if (comments.length === 0) {
                    container.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
                    return;
                }
                
                let html = '';
                comments.forEach(comment => {
                    comment.author = comment.username || 'Unknown';
                    comment.avatar = this.processAvatar(comment.profile_picture);
                    html += this.renderComment(comment);
                });
                container.innerHTML = html;
            }
            
            // Update renderComment to render main comment + direct replies (no deeper nesting)
            renderComment(comment, level = 0) {
                const timeAgo = this.getTimeAgo(comment.created_at);
                const hasReplies = comment.replies && comment.replies.length > 0;
                
                // Process replies (only direct ones)
                let repliesHtml = '';
                if (hasReplies) {
                    repliesHtml = comment.replies.map(reply => {
                        reply.author = reply.username || 'Unknown';
                        reply.avatar = this.processAvatar(reply.profile_picture);
                        return `
                        <div class="comment reply" data-comment-id="${reply.comment_id}" style="margin-left: 40px;">
                            <div class="comment-header-info">
                                <img src="${reply.avatar}" alt="${reply.author}" class="comment-avatar">
                                <span class="comment-author">${reply.author}</span>
                                <span class="comment-time">${this.getTimeAgo(reply.created_at)}</span>
                            </div>
                            <div class="comment-text">${this.escapeHtml(reply.content)}</div>
                            <!-- No reply button for replies to prevent deeper nesting -->
                        </div>`;
                    }).join('');
                }
                
                return `
                <div class="comment" data-comment-id="${comment.comment_id}">
                    <div class="comment-header-info">
                        <img src="${comment.avatar}" alt="${comment.author}" class="comment-avatar">
                        <span class="comment-author">${comment.author}</span>
                        <span class="comment-time">${timeAgo}</span>
                    </div>
                    <div class="comment-text">${this.escapeHtml(comment.content)}</div>
                    <div class="comment-actions">
                        <button class="comment-action reply-btn" data-comment-id="${comment.comment_id}">
                            <i class="fas fa-reply"></i>
                            <span>Reply</span>
                        </button>
                    </div>
                    
                    <div class="reply-form" id="reply-form-${comment.comment_id}">
                        <div class="reply-input-container">
                            <input type="text" class="reply-input" placeholder="Write a reply..." data-comment-id="${comment.comment_id}">
                            <button class="reply-submit-btn" data-comment-id="${comment.comment_id}">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    
                    ${repliesHtml ? `<div class="comment-replies">${repliesHtml}</div>` : ''}
                </div>`;
            }
            
            async submitComment(postId) {
                const input = document.querySelector(`.comment-input[data-post-id="${postId}"]`);
                const btn = document.querySelector(`.comment-submit-btn[data-post-id="${postId}"]`);
                const content = input.value.trim();
                
                if (!content) return;
                
                btn.disabled = true;
                
                try {
                    const response = await fetch('../../app/controllers/CommentController.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=add_comment&post_id=${encodeURIComponent(postId)}&content=${encodeURIComponent(content)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        input.value = '';
                        this.loadComments(postId);
                        this.showNotification('Comment added successfully!', 'success');
                    } else {
                        this.showNotification(data.message || 'Failed to add comment', 'error');
                    }
                    btn.disabled = false;
                    
                } catch (error) {
                    console.error('Error submitting comment:', error);
                    this.showNotification('Error adding comment', 'error');
                    btn.disabled = false;
                }
            }
            
            async submitReply(commentId) {
                const input = document.querySelector(`.reply-input[data-comment-id="${commentId}"]`);
                const btn = document.querySelector(`.reply-submit-btn[data-comment-id="${commentId}"]`);
                const content = input.value.trim();
                
                if (!content) return;
                
                btn.disabled = true;
                
                try {
                    const response = await fetch('../../app/controllers/CommentController.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=add_comment&post_id=${this.getPostIdFromComment(commentId)}&content=${encodeURIComponent(content)}&parent_comment_id=${encodeURIComponent(commentId)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        input.value = '';
                        this.toggleReplyForm(commentId, false);
                        // Reload comments to show new reply
                        const postId = this.getPostIdFromComment(commentId);
                        this.loadComments(postId);
                        this.showNotification('Reply added successfully!', 'success');
                    } else {
                        this.showNotification(data.message || 'Failed to add reply', 'error');
                    }
                } catch (error) {
                    console.error('Error submitting reply:', error);
                    this.showNotification('Error adding reply', 'error');
                } finally {
                    btn.disabled = false;
                }
            }
            
            toggleReplyForm(commentId, show = true) {
                const form = document.getElementById(`reply-form-${commentId}`);
                if (form) {
                    form.classList.toggle('active', show);
                    if (show) {
                        const input = form.querySelector('.reply-input');
                        input.focus();
                    }
                }
            }
            
            getTimeAgo(timestamp) {
                // Mock implementation
                const times = ['just now', '5 mins ago', '1 hour ago', '2 days ago'];
                return times[Math.floor(Math.random() * times.length)];
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            getPostIdFromComment(commentId) {
                // Implement logic to find post ID, e.g., from DOM or cache
                return document.querySelector('.comment-section').dataset.postId; // Assuming it's set
            }
            
            showNotification(message, type = 'info') {
                // Use your existing notification system
                if (typeof showToast === 'function') {
                    showToast(message, type);
                } else {
                    alert(message);
                }
            }
        }
        
        // Initialize comment system when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new CommentSystem();
        });