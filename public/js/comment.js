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

                    // Edit/Delete comment
                    if (e.target.closest('.edit-comment-btn')) {
                        const id = e.target.closest('.edit-comment-btn').dataset.commentId;
                        this.openInlineEditor(id);
                    }
                    if (e.target.closest('.delete-comment-btn')) {
                        const id = e.target.closest('.delete-comment-btn').dataset.commentId;
                        this.deleteComment(id);
                    }
                    // Edit/Delete reply
                    if (e.target.closest('.edit-reply-btn')) {
                        const id = e.target.closest('.edit-reply-btn').dataset.commentId;
                        this.openInlineEditor(id);
                    }
                    if (e.target.closest('.delete-reply-btn')) {
                        const id = e.target.closest('.delete-reply-btn').dataset.commentId;
                        this.deleteComment(id);
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
                
                container.innerHTML = '<div class="comments-loading">Loading comments...</div>';
                
                try {
                    const response = await fetch(BASE_PATH + '/index.php?controller=Comment&action=handleAjax', {  // Use BASE_PATH and correct action
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=get_comments&post_id=${encodeURIComponent(postId)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.displayComments(container, data.comments || [], { currentUserId: data.currentUserId, postOwnerId: data.postOwnerId });
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
                const base = (typeof BASE_PATH !== 'undefined' ? String(BASE_PATH).replace(/\/$/, '') : '');
                const defaultPath = base + '/public/images/avatars/defaultProfilePic.png';

                if (!rawAvatar) return defaultPath;
                const val = String(rawAvatar).trim();
                if (/^https?:\/\//i.test(val)) return val; // Full URL

                // Normalize common stored paths
                // Cases: 'public/images/avatars/x.jpg', 'images/avatars/x.jpg', 'uploads/...', 'x.jpg'
                let normalized = val.replace(/^\\+/g, '').replace(/^\/+/, '');
                if (normalized.startsWith('public/')) {
                    normalized = normalized.substring('public/'.length);
                }
                if (normalized.startsWith('images/') || normalized.startsWith('uploads/')) {
                    return base + '/public/' + normalized;
                }

                // If value already contains a subpath with slash, prepend '/public/'
                if (normalized.includes('/')) {
                    return base + '/public/' + normalized;
                }

                // Treat as plain filename inside avatars directory
                return base + '/public/images/avatars/' + normalized;
            }
            
            // Update displayComments to map data (no deep processing needed)
            displayComments(container, comments, meta = {}) {
                if (comments.length === 0) {
                    container.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
                    return;
                }
                
                let html = '';
                comments.forEach(comment => {
                    comment.author = comment.username || 'Unknown';
                    comment.avatar = this.processAvatar(comment.profile_picture);
                    html += this.renderComment(comment, 0, meta);
                });
                container.innerHTML = html;
            }
            
            // Update renderComment to render main comment + direct replies (no deeper nesting)
            renderComment(comment, level = 0, meta = {}) {
                const timeAgo = this.getTimeAgo(comment.created_at);
                const hasReplies = comment.replies && comment.replies.length > 0;
                const currentUserId = meta.currentUserId;
                const postOwnerId = meta.postOwnerId;
                const canModerate = (Number(comment.commenter_id) === Number(currentUserId)) || (Number(postOwnerId) === Number(currentUserId));
                
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
                            <div class="comment-text" data-comment-content>${this.escapeHtml(reply.content)}</div>
                            ${((Number(reply.commenter_id) === Number(currentUserId)) || (Number(postOwnerId) === Number(currentUserId))) ? `
                            <div class="comment-actions">
                                <button class="comment-action edit-reply-btn" data-comment-id="${reply.comment_id}">Edit</button>
                                <button class="comment-action delete-reply-btn" data-comment-id="${reply.comment_id}">Delete</button>
                            </div>` : ''}
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
                    <div class="comment-text" data-comment-content>${this.escapeHtml(comment.content)}</div>
                    <div class="comment-actions">
                        <button class="comment-action reply-btn" data-comment-id="${comment.comment_id}">
                            <i class="fas fa-reply"></i>
                            <span>Reply</span>
                        </button>
                        ${canModerate ? `
                        <button class="comment-action edit-comment-btn" data-comment-id="${comment.comment_id}">Edit</button>
                        <button class="comment-action delete-comment-btn" data-comment-id="${comment.comment_id}">Delete</button>
                        ` : ''}
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
                    const response = await fetch(BASE_PATH + '/index.php?controller=Comment&action=handleAjax', {  // Use BASE_PATH
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=add_comment&post_id=${encodeURIComponent(postId)}&content=${encodeURIComponent(content)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        input.value = '';
                        this.loadComments(postId);
                        // Update comment count in UI
                        const commentBtn = document.querySelector(`.load-comments-btn[data-post-id="${postId}"]`);
                        if (commentBtn) {
                            const currentText = commentBtn.textContent;
                            const match = currentText.match(/View all (\d+) comments/);
                            if (match) {
                                const newCount = parseInt(match[1]) + 1;
                                commentBtn.textContent = `View all ${newCount} comments`;
                            }
                        }
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
                    const response = await fetch(BASE_PATH + '/index.php?controller=Comment&action=handleAjax', {  // Use BASE_PATH
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=add_comment&post_id=${this.getPostIdFromComment(commentId)}&content=${encodeURIComponent(content)}&parent_comment_id=${encodeURIComponent(commentId)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        input.value = '';
                        this.toggleReplyForm(commentId, false);
                        const postId = this.getPostIdFromComment(commentId);
                        this.loadComments(postId);
                        // Update comment count for replies too
                        const commentBtn = document.querySelector(`.load-comments-btn[data-post-id="${postId}"]`);
                        if (commentBtn) {
                            const currentText = commentBtn.textContent;
                            const match = currentText.match(/View all (\d+) comments/);
                            if (match) {
                                const newCount = parseInt(match[1]) + 1;
                                commentBtn.textContent = `View all ${newCount} comments`;
                            }
                        }
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

        // Additional prototype methods for inline editing and deletion
        CommentSystem.prototype.openInlineEditor = function(commentId) {
            const root = document.querySelector(`[data-comment-id="${commentId}"]`);
            if (!root) return;
            const contentEl = root.querySelector('[data-comment-content]');
            if (!contentEl) return;
            const original = contentEl.textContent;
            if (root.querySelector('.edit-inline')) return; // already open
            const editor = document.createElement('div');
            editor.className = 'edit-inline';
            editor.innerHTML = `
                <textarea class="edit-textarea">${this.escapeHtml(original)}</textarea>
                <div class="edit-actions">
                    <button class="btn btn-primary" data-save-edit>Save</button>
                    <button class="btn btn-secondary" data-cancel-edit>Cancel</button>
                </div>
            `;
            contentEl.style.display = 'none';
            contentEl.insertAdjacentElement('afterend', editor);

            editor.querySelector('[data-cancel-edit]').addEventListener('click', () => {
                editor.remove();
                contentEl.style.display = '';
            });
            editor.querySelector('[data-save-edit]').addEventListener('click', () => {
                const newVal = editor.querySelector('.edit-textarea').value.trim();
                if (!newVal) { alert('Comment cannot be empty'); return; }
                this.saveEdit(commentId, newVal, () => {
                    contentEl.textContent = newVal;
                    editor.remove();
                    contentEl.style.display = '';
                });
            });
        };

        CommentSystem.prototype.saveEdit = async function(commentId, content, onSuccess) {
            try {
                const response = await fetch(BASE_PATH + '/index.php?controller=Comment&action=handleAjax', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=edit_comment&comment_id=${encodeURIComponent(commentId)}&content=${encodeURIComponent(content)}`
                });
                const data = await response.json();
                if (data.success) {
                    if (onSuccess) onSuccess();
                    this.showNotification('Comment updated', 'success');
                } else {
                    this.showNotification(data.message || 'Failed to update comment', 'error');
                }
            } catch (err) {
                console.error('Edit comment error', err);
                this.showNotification('Error updating comment', 'error');
            }
        };

        CommentSystem.prototype.deleteComment = async function(commentId) {
            if (!confirm('Delete this comment?')) return;
            try {
                const response = await fetch(BASE_PATH + '/index.php?controller=Comment&action=handleAjax', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=delete_comment&comment_id=${encodeURIComponent(commentId)}`
                });
                const data = await response.json();
                if (data.success) {
                    const root = document.querySelector(`[data-comment-id="${commentId}"]`);
                    if (root) {
                        if (data.softDeleted) {
                            const contentEl = root.querySelector('[data-comment-content]');
                            if (contentEl) contentEl.textContent = '[deleted]';
                        } else {
                            // On hard delete remove node and decrement visible count
                            const section = root.closest('.comment-section');
                            root.remove();
                            if (section && section.dataset.postId) {
                                const postId = section.dataset.postId;
                                const commentBtn = document.querySelector(`.load-comments-btn[data-post-id="${postId}"]`);
                                if (commentBtn) {
                                    const currentText = commentBtn.textContent;
                                    const match = currentText.match(/View all (\d+) comments/);
                                    if (match) {
                                        const newCount = Math.max(0, parseInt(match[1]) - 1);
                                        commentBtn.textContent = `View all ${newCount} comments`;
                                    }
                                }
                            }
                        }
                    }
                    this.showNotification(data.message || 'Comment deleted', 'success');
                } else {
                    this.showNotification(data.message || 'Failed to delete comment', 'error');
                }
            } catch (err) {
                console.error('Delete comment error', err);
                this.showNotification('Error deleting comment', 'error');
            }
        };