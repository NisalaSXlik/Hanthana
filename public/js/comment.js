class CommentSystem {
    constructor() {
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        document.addEventListener('click', (e) => {
            console.log('CommentSystem: Click detected on:', e.target);
            console.log('CommentSystem: Target classes:', e.target.className);
            
            if (e.target.closest('.load-comments-btn')) {
                const btn = e.target.closest('.load-comments-btn');
                const postId = btn.dataset.postId;
                console.log('CommentSystem: Load comments for post:', postId);
                this.toggleCommentSection(postId);
                return;
            }
            
            if (e.target.closest('.close-comments')) {
                const btn = e.target.closest('.close-comments');
                const commentSection = btn.closest('.comment-section');
                const postId = commentSection.dataset.postId;
                console.log('CommentSystem: Close comments for post:', postId);
                this.hideCommentSection(postId);
                return;
            }
            
            if (e.target.closest('.comment-submit-btn')) {
                const btn = e.target.closest('.comment-submit-btn');
                const postId = btn.dataset.postId;
                console.log('CommentSystem: Submit comment for post:', postId);
                this.submitComment(postId);
                return;
            }
            
            if (e.target.closest('.reply-submit-btn')) {
                const btn = e.target.closest('.reply-submit-btn');
                const commentId = btn.dataset.commentId;
                console.log('CommentSystem: Submit reply for comment:', commentId);
                this.submitReply(commentId);
                return;
            }
            
            if (e.target.closest('.reply-btn')) {
                const btn = e.target.closest('.reply-btn');
                const commentId = btn.dataset.commentId;
                console.log('CommentSystem: Reply button for comment:', commentId);
                this.toggleReplyForm(commentId);
                return;
            }

            if (e.target.closest('.edit-comment-btn, .edit-reply-btn')) {
                const btn = e.target.closest('.edit-comment-btn, .edit-reply-btn');
                const id = btn.dataset.commentId;
                console.log('CommentSystem: Edit comment:', id);
                this.openInlineEditor(id);
                return;
            }
            
            if (e.target.closest('.delete-comment-btn, .delete-reply-btn')) {
                const btn = e.target.closest('.delete-comment-btn, .delete-reply-btn');
                const id = btn.dataset.commentId;
                console.log('CommentSystem: Delete comment:', id);
                this.deleteComment(id);
                return;
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.target.classList.contains('comment-input') && e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const postId = e.target.dataset.postId;
                console.log('CommentSystem: Enter key in comment for post:', postId);
                this.submitComment(postId);
            }
            
            if (e.target.classList.contains('reply-input') && e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const commentId = e.target.dataset.commentId;
                console.log('CommentSystem: Enter key in reply for comment:', commentId);
                this.submitReply(commentId);
            }
        });
    }
    
    toggleCommentSection(postId) {
        const commentSection = document.querySelector(`.comment-section[data-post-id="${postId}"]`);
        const loadBtn = document.querySelector(`.load-comments-btn[data-post-id="${postId}"]`);
        
        // FIXED: Check if commentSection exists
        if (!commentSection) return;
        
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
        if (!commentSection) return;
        
        commentSection.classList.add('active');
        this.loadComments(postId);
    }
    
    hideCommentSection(postId) {
        const commentSection = document.querySelector(`.comment-section[data-post-id="${postId}"]`);
        if (commentSection) {
            commentSection.classList.remove('active');
        }
    }
    
    async loadComments(postId) {
        const container = document.getElementById(`comments-container-${postId}`);
        if (!container) return;
        
        try {
            const formData = new FormData();
            formData.append('sub_action', 'load');
            formData.append('post_id', postId);

            const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            console.log('Load comments response:', text);
            
            // FIXED: Better JSON parsing with error handling
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                container.innerHTML = `<div class="comments-loading">Error parsing response</div>`;
                return;
            }
            
            if (data.success) {
                this.displayComments(container, data.comments || [], { 
                    currentUserId: data.currentUserId, 
                    postOwnerId: data.postOwnerId 
                });
            } else {
                container.innerHTML = `<div class="comments-loading">${data.message || 'Failed to load comments'}</div>`;
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            container.innerHTML = '<div class="comments-loading">Error loading comments</div>';
        }
    }
    
    displayComments(container, comments, meta = {}) {
        if (comments.length === 0) {
            container.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
            return;
        }
        
        let html = '';
        comments.forEach(comment => {
            comment.author = comment.username || 'Unknown';
            html += this.renderComment(comment, 0, meta);
        });
        container.innerHTML = html;
    }
    
    renderComment(comment, level = 0, meta = {}) {
        const timeAgo = this.getTimeAgo(comment.created_at);
        const hasReplies = comment.replies && comment.replies.length > 0;
        const currentUserId = meta.currentUserId;
        const postOwnerId = meta.postOwnerId;
        const canModerate = (Number(comment.commenter_id) === Number(currentUserId)) || (Number(postOwnerId) === Number(currentUserId));
        
        // Profile picture is already resolved by MediaHelper on the server side
        const profilePic = comment.profile_picture || BASE_PATH + 'uploads/user_dp/default_user_dp.jpg';
        
        let repliesHtml = '';
        if (hasReplies) {
            repliesHtml = comment.replies.map(reply => {
                reply.author = reply.username || 'Unknown';
                const replyCanModerate = (Number(reply.commenter_id) === Number(currentUserId)) || (Number(postOwnerId) === Number(currentUserId));
                
                // Profile picture is already resolved by MediaHelper on the server side
                const replyProfilePic = reply.profile_picture || BASE_PATH + 'uploads/user_dp/default_user_dp.jpg';
                
                return `
                <div class="comment reply" data-comment-id="${reply.comment_id}" style="margin-left: 40px;">
                    <div class="comment-header-info">
                        <img src="${replyProfilePic}" alt="${reply.author}" class="comment-avatar">
                        <span class="comment-author">${reply.author}</span>
                        <span class="comment-time">${this.getTimeAgo(reply.created_at)}</span>
                    </div>
                    <div class="comment-text" data-comment-content>${this.escapeHtml(reply.content)}</div>
                    ${replyCanModerate ? `
                    <div class="comment-actions">
                        <button class="comment-action edit-reply-btn" data-comment-id="${reply.comment_id}">Edit</button>
                        <button class="comment-action delete-reply-btn" data-comment-id="${reply.comment_id}">Delete</button>
                    </div>` : ''}
                </div>`;
            }).join('');
        }
        
        return `
        <div class="comment" data-comment-id="${comment.comment_id}">
            <div class="comment-header-info">
                <img src="${profilePic}" alt="${comment.author}" class="comment-avatar">
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
        console.log('CommentSystem: submitComment called for post:', postId);
        
        const input = document.querySelector(`.comment-input[data-post-id="${postId}"]`);
        const btn = document.querySelector(`.comment-submit-btn[data-post-id="${postId}"]`);
        
        console.log('CommentSystem: Input element:', input);
        console.log('CommentSystem: Button element:', btn);
        
        if (!input || !btn) {
            console.error('CommentSystem: Could not find input or button for post:', postId);
            return;
        }
        
        const content = input.value.trim();
        console.log('CommentSystem: Comment content:', content);
        
        if (!content) {
            console.log('CommentSystem: Empty comment, skipping');
            return;
        }
        
        btn.disabled = true;
        btn.textContent = 'Posting...';
        
        try {
            // Generate local timestamp in MySQL format (YYYY-MM-DD HH:MM:SS)
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const localTimestamp = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            
            const formData = new FormData();
            formData.append('sub_action', 'add');
            formData.append('post_id', postId);
            formData.append('content', content);
            formData.append('created_at', localTimestamp); // Send local timestamp

            console.log('CommentSystem: Sending request to:', BASE_PATH + 'index.php?controller=Comment&action=handleAjax');
            
            const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const text = await response.text();
            console.log('CommentSystem: Submit comment response TEXT:', text);
            console.log('CommentSystem: Response length:', text.length);
            console.log('CommentSystem: First 100 chars:', text.substring(0, 100));
            console.log('CommentSystem: Last 100 chars:', text.substring(text.length - 100));
            
            let data;
            try {
                // First try direct JSON parse
                try {
                    data = JSON.parse(text);
                    console.log('CommentSystem: Direct JSON parse SUCCESS');
                } catch (firstError) {
                    console.log('CommentSystem: Direct JSON parse failed, trying regex extraction');
                    // Try to extract JSON from response even if there's extra content
                    const jsonMatch = text.match(/\{[\s\S]*\}/);
                    if (jsonMatch) {
                        data = JSON.parse(jsonMatch[0]);
                        console.log('CommentSystem: Regex JSON parse SUCCESS');
                    } else {
                        throw new Error('No JSON found in response');
                    }
                }
            } catch (parseError) {
                console.error('CommentSystem: JSON parse error:', parseError);
                console.error('CommentSystem: Full response text:', text);
                this.showToast('Error processing response', 'error');
                btn.disabled = false;
                btn.textContent = 'Post Comment';
                return;
            }
            
            console.log('CommentSystem: Parsed data:', data);
            
            if (data.success) {
                input.value = '';
                await this.loadComments(postId);
                
                // Update both the comment button text and the comment count display
                if (data.comment_count !== undefined) {
                    // Update "View all X comments" text
                    const viewCommentsDiv = document.querySelector(`.comments.load-comments-btn[data-post-id="${postId}"]`);
                    if (viewCommentsDiv) {
                        viewCommentsDiv.textContent = `View all ${data.comment_count} comments`;
                        // Make sure it's visible
                        viewCommentsDiv.style.display = 'block';
                    }
                    
                    // Update the comment count next to the comment icon
                    const commentIcon = document.querySelector(`.interaction-buttons .uil-comment[data-post-id="${postId}"]`);
                    if (commentIcon) {
                        let countSpan = commentIcon.nextElementSibling;
                        if (countSpan && countSpan.tagName === 'SMALL') {
                            countSpan.textContent = data.comment_count;
                        } else {
                            // Create count span if it doesn't exist
                            countSpan = document.createElement('small');
                            countSpan.textContent = data.comment_count;
                            commentIcon.parentNode.insertBefore(countSpan, commentIcon.nextSibling);
                        }
                    }
                }
                
                this.showToast('Comment added successfully!', 'success');
            } else {
                console.error('CommentSystem: Server error:', data.message);
                this.showToast(data.message || 'Failed to add comment', 'error');
            }
        } catch (error) {
            console.error('CommentSystem: Error submitting comment:', error);
            this.showToast('Error adding comment', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Post Comment';
        }
    }
    
    async submitReply(commentId) {
        const input = document.querySelector(`.reply-input[data-comment-id="${commentId}"]`);
        const btn = document.querySelector(`.reply-submit-btn[data-comment-id="${commentId}"]`);
        
        if (!input || !btn) return;
        
        const content = input.value.trim();
        
        if (!content) return;
        
        btn.disabled = true;
        
        try {
            const postId = this.getPostIdFromComment(commentId);
            if (!postId) {
                throw new Error('Could not find post ID');
            }
            
            // Generate local timestamp in MySQL format (YYYY-MM-DD HH:MM:SS)
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const localTimestamp = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            
            const formData = new FormData();
            formData.append('sub_action', 'add');
            formData.append('post_id', postId);
            formData.append('content', content);
            formData.append('parent_comment_id', commentId);
            formData.append('created_at', localTimestamp); // Send local timestamp

            const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            console.log('Submit reply response:', text);
            
            let data;
            try {
                // Try to extract JSON from response even if there's extra content
                const jsonMatch = text.match(/\{[\s\S]*\}/);
                if (jsonMatch) {
                    data = JSON.parse(jsonMatch[0]);
                } else {
                    data = JSON.parse(text);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                this.showToast('Error processing response', 'error');
                btn.disabled = false;
                return;
            }
            
            if (data.success) {
                input.value = '';
                this.toggleReplyForm(commentId, false);
                this.loadComments(postId);
                
                // Update both the comment button text and the comment count display
                if (data.comment_count !== undefined) {
                    // Update "View all X comments" text
                    const viewCommentsDiv = document.querySelector(`.comments.load-comments-btn[data-post-id="${postId}"]`);
                    if (viewCommentsDiv) {
                        viewCommentsDiv.textContent = `View all ${data.comment_count} comments`;
                        viewCommentsDiv.style.display = 'block';
                    }
                    
                    // Update the comment count next to the comment icon
                    const commentIcon = document.querySelector(`.interaction-buttons .uil-comment[data-post-id="${postId}"]`);
                    if (commentIcon) {
                        let countSpan = commentIcon.nextElementSibling;
                        if (countSpan && countSpan.tagName === 'SMALL') {
                            countSpan.textContent = data.comment_count;
                        }
                    }
                }
                
                this.showToast('Reply added successfully!', 'success');
            } else {
                this.showToast(data.message || 'Failed to add reply', 'error');
            }
        } catch (error) {
            console.error('Error submitting reply:', error);
            this.showToast('Error adding reply', 'error');
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
                if (input) input.focus();
            }
        }
    }
    
    async deleteComment(commentId) {
        if (!confirm('Delete this comment?')) return;
        
        try {
            const formData = new FormData();
            formData.append('sub_action', 'delete');
            formData.append('comment_id', commentId);

            const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            let data;
            try {
                // Try to extract JSON from response even if there's extra content
                const jsonMatch = text.match(/\{[\s\S]*\}/);
                if (jsonMatch) {
                    data = JSON.parse(jsonMatch[0]);
                } else {
                    data = JSON.parse(text);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                this.showToast('Error processing response', 'error');
                return;
            }
            
            if (data.success) {
                const root = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (root) {
                    if (data.soft_deleted) {
                        const contentEl = root.querySelector('[data-comment-content]');
                        if (contentEl) contentEl.textContent = '[deleted]';
                    } else {
                        const section = root.closest('.comment-section');
                        const postId = section ? section.dataset.postId : null;
                        root.remove();
                        
                        if (postId && data.comment_count !== undefined) {
                            // Update "View all X comments" text
                            const viewCommentsDiv = document.querySelector(`.comments.load-comments-btn[data-post-id="${postId}"]`);
                            if (viewCommentsDiv) {
                                if (data.comment_count > 0) {
                                    viewCommentsDiv.textContent = `View all ${data.comment_count} comments`;
                                    viewCommentsDiv.style.display = 'block';
                                } else {
                                    viewCommentsDiv.style.display = 'none';
                                }
                            }
                            
                            // Update the comment count next to the comment icon
                            const commentIcon = document.querySelector(`.interaction-buttons .uil-comment[data-post-id="${postId}"]`);
                            if (commentIcon) {
                                let countSpan = commentIcon.nextElementSibling;
                                if (countSpan && countSpan.tagName === 'SMALL') {
                                    countSpan.textContent = data.comment_count;
                                }
                            }
                        }
                    }
                }
                this.showToast('Comment deleted', 'success');
            } else {
                this.showToast(data.message || 'Failed to delete comment', 'error');
            }
        } catch (error) {
            console.error('Delete comment error:', error);
            this.showToast('Error deleting comment', 'error');
        }
    }
    
    openInlineEditor(commentId) {
        const root = document.querySelector(`[data-comment-id="${commentId}"]`);
        if (!root) return;
        const contentEl = root.querySelector('[data-comment-content]');
        if (!contentEl) return;
        const original = contentEl.textContent;
        if (root.querySelector('.edit-inline')) return;
        
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

        const cancelBtn = editor.querySelector('[data-cancel-edit]');
        const saveBtn = editor.querySelector('[data-save-edit]');
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                editor.remove();
                contentEl.style.display = '';
            });
        }
        
        if (saveBtn) {
            saveBtn.addEventListener('click', async () => {
                const textarea = editor.querySelector('.edit-textarea');
                if (!textarea) return;
                
                const newVal = textarea.value.trim();
                if (!newVal) {
                    this.showToast('Comment cannot be empty', 'error');
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('sub_action', 'edit');
                    formData.append('comment_id', commentId);
                    formData.append('content', newVal);

                    const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const text = await response.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError);
                        this.showToast('Error processing response', 'error');
                        return;
                    }
                    
                    if (data.success) {
                        contentEl.textContent = newVal;
                        editor.remove();
                        contentEl.style.display = '';
                        this.showToast('Comment updated', 'success');
                    } else {
                        this.showToast(data.message || 'Failed to update comment', 'error');
                    }
                } catch (error) {
                    console.error('Edit comment error:', error);
                    this.showToast('Error updating comment', 'error');
                }
            });
        }
    }
    
    getTimeAgo(timestamp) {
        console.log('=== getTimeAgo Debug ===');
        console.log('Input timestamp:', timestamp);
        console.log('Timestamp type:', typeof timestamp);
        
        if (!timestamp) {
            return 'just now';
        }
        
        // Handle MySQL timestamp format (YYYY-MM-DD HH:MM:SS)
        // Replace space with 'T' to make it ISO 8601 compliant
        const isoTimestamp = timestamp.replace(' ', 'T');
        console.log('ISO timestamp:', isoTimestamp);
        
        const now = new Date();
        const past = new Date(isoTimestamp);
        
        console.log('Current time (now):', now.toString());
        console.log('Comment time (past):', past.toString());
        console.log('Now ISO:', now.toISOString());
        console.log('Past ISO:', past.toISOString());
        
        // Check if date is valid
        if (isNaN(past.getTime())) {
            console.warn('Invalid timestamp:', timestamp);
            return 'just now';
        }
        
        const diffMs = now - past;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHr = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHr / 24);
        
        console.log('Difference:', {
            milliseconds: diffMs,
            seconds: diffSec,
            minutes: diffMin,
            hours: diffHr,
            days: diffDay
        });
        
        if (diffSec < 0) {
            console.log('Future timestamp - returning "just now"');
            return 'just now';
        }
        if (diffSec < 60) {
            console.log('Less than 60 seconds - returning "just now"');
            return 'just now';
        }
        if (diffMin < 60) {
            const result = `${diffMin} min${diffMin > 1 ? 's' : ''} ago`;
            console.log('Returning:', result);
            return result;
        }
        if (diffHr < 24) {
            const result = `${diffHr} hour${diffHr > 1 ? 's' : ''} ago`;
            console.log('Returning:', result);
            return result;
        }
        if (diffDay < 7) {
            const result = `${diffDay} day${diffDay > 1 ? 's' : ''} ago`;
            console.log('Returning:', result);
            return result;
        }
        
        // For older comments, show the date
        const options = { month: 'short', day: 'numeric' };
        if (past.getFullYear() !== now.getFullYear()) {
            options.year = 'numeric';
        }
        const result = past.toLocaleDateString('en-US', options);
        console.log('Returning date:', result);
        return result;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    getPostIdFromComment(commentId) {
        const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
        const section = commentEl ? commentEl.closest('.comment-section') : null;
        return section ? section.dataset.postId : null;
    }
    
    showToast(message, type = 'success') {
        if (window.showToast) {
            window.showToast(message, type);
        } else {
            console.log(`Toast (${type}): ${message}`);
            alert(`${type.toUpperCase()}: ${message}`);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing CommentSystem...');
    window.commentSystem = new CommentSystem();
});