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
            
            displayComments(container, comments) {
                if (comments.length === 0) {
                    container.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
                    return;
                }
                
                let html = '';
                comments.forEach(comment => {
                    html += this.renderComment(comment);
                });
                container.innerHTML = html;
            }
            
            renderComment(comment, level = 0) {
                const timeAgo = this.getTimeAgo(comment.created_at);
                const hasReplies = comment.replies && comment.replies.length > 0;
                
                return `
                <div class="comment" data-comment-id="${comment.id}" style="margin-left: ${level * 20}px;">
                    <div class="comment-header-info">
                        <img src="${comment.avatar}" alt="${comment.author}" class="comment-avatar">
                        <span class="comment-author">${comment.author}</span>
                        <span class="comment-time">${timeAgo}</span>
                    </div>
                    <div class="comment-text">${this.escapeHtml(comment.content)}</div>
                    <div class="comment-actions">
                        <button class="comment-action like-btn">
                            <i class="far fa-heart"></i>
                            <span>${comment.likes || 0}</span>
                        </button>
                        <button class="comment-action reply-btn" data-comment-id="${comment.id}">
                            <i class="fas fa-reply"></i>
                            <span>Reply</span>
                        </button>
                    </div>
                    
                    <div class="reply-form" id="reply-form-${comment.id}">
                        <div class="reply-input-container">
                            <input type="text" class="reply-input" placeholder="Write a reply..." data-comment-id="${comment.id}">
                            <button class="reply-submit-btn" data-comment-id="${comment.id}">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    
                    ${hasReplies ? `
                        <div class="comment-replies">
                            ${comment.replies.map(reply => this.renderComment(reply, level + 1)).join('')}
                        </div>
                    ` : ''}
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
                    // Mock success
                    setTimeout(() => {
                        input.value = '';
                        btn.disabled = false;
                        this.toggleReplyForm(commentId, false);
                        this.showNotification('Reply added successfully!', 'success');
                    }, 500);
                    
                } catch (error) {
                    console.error('Error submitting reply:', error);
                    this.showNotification('Error adding reply', 'error');
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
            
            getMockComments() {
                return [
                    {
                        id: 1,
                        author: 'Minthaka J.',
                        avatar: '../../public/images/2.jpg',
                        content: 'Wow, this looks amazing! Which beach is this?',
                        likes: 12,
                        created_at: new Date(),
                        replies: [
                            {
                                id: 2,
                                author: 'Nisal Gamage',
                                avatar: '../../public/images/profile-1.jpg',
                                content: 'This is Mirissa Beach! Definitely worth visiting.',
                                likes: 5,
                                created_at: new Date()
                            }
                        ]
                    },
                    {
                        id: 3,
                        author: 'Lahiru F.',
                        avatar: '../../public/images/6.jpg',
                        content: 'Beautiful capture! The colors are stunning 🌅',
                        likes: 8,
                        created_at: new Date()
                    }
                ];
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