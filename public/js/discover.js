document.addEventListener('DOMContentLoaded', () => {
    console.log('Discover.js loaded'); // DEBUG
    console.log('BASE_PATH:', BASE_PATH); // DEBUG
    
    const feedContainer = document.getElementById('discover-feeds');
    console.log('Feed container:', feedContainer); // DEBUG
    
    let isLoading = false;
    let offset = 12;
    let hasMore = true;

    // Tile click -> show myfeed format post
    const tiles = document.querySelectorAll('.discover-item');
    console.log('Found tiles:', tiles.length); // DEBUG
    
    tiles.forEach((tile, index) => {
        console.log(`Attaching click listener to tile ${index}`, tile); // DEBUG
        tile.addEventListener('click', function(e) {
            const postId = this.getAttribute('data-post-id');
            console.log('🔴 TILE CLICKED - postId:', postId); // DEBUG
            if (!postId) {
                console.log('❌ No postId found');
                return;
            }

            const url = `${BASE_PATH}index.php?controller=Discover&action=getPost&post_id=${postId}`;
            console.log('🔵 Fetching URL:', url); // DEBUG
            
            fetch(url)
                .then(res => {
                    console.log('🟢 Response status:', res.status); // DEBUG
                    return res.json();
                })
                .then(data => {
                    console.log('🟡 Response data:', data); // DEBUG
                    if (!data.success) {
                        console.error('❌ Error:', data.error);
                        return;
                    }
                    const postEl = createPostElement(data.post);
                    feedContainer.prepend(postEl);
                    postEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    console.log('✅ Post added to feed');
                })
                .catch(error => {
                    console.error('❌ Fetch error:', error); // DEBUG
                });
        });
    });

    // Infinite scroll
    window.addEventListener('scroll', () => {
        if (isLoading || !hasMore) return;

        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 300) {
            loadMorePosts();
        }
    });

    function loadMorePosts() {
        isLoading = true;

        fetch(`${BASE_PATH}index.php?controller=Discover&action=loadMore&offset=${offset}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.posts.length === 0) {
                    hasMore = false;
                    return;
                }

                data.posts.forEach(post => {
                    const postEl = createPostElement(post);
                    feedContainer.appendChild(postEl);
                });

                offset += data.posts.length;
                if (data.posts.length < 12) hasMore = false;
            })
            .finally(() => {
                isLoading = false;
            });
    }

    function createPostElement(post) {
        const fullName = `${post.first_name || ''} ${post.last_name || ''}`.trim();
        const displayName = post.username || fullName || 'Unknown';
        const avatarUrl = post.profile_picture || `${BASE_PATH}images/avatars/defaultProfilePic.png`;
        const postImage = post.image_url || '';
        const authorId = post.author_id || post.user_id;
        const postId = post.post_id;

        const isGroupPost = !!post.group_id;
        const groupName = post.group_name || '';

        const postUrl = isGroupPost
            ? `${BASE_PATH}index.php?controller=Profile&action=view&user_id=${authorId}#group-post-${postId}`
            : `${BASE_PATH}index.php?controller=Profile&action=view&user_id=${authorId}#personal-post-${postId}`;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="feed" data-post-id="${postId}" data-navigate-url="${postUrl}" style="cursor:pointer;">
                <div class="head">
                    <div class="user">
                        <div class="profile-picture">
                            <img src="${escapeHtml(avatarUrl)}" alt="Profile">
                        </div>
                        <div class="info">
                            <h3>
                                ${escapeHtml(displayName)}
                                ${isGroupPost ? `
                                    <span class="group-indicator" style="font-weight: normal; color: var(--color-gray); font-size: 0.9em;">
                                        <i class="uil uil-angle-right"></i>
                                        <span style="font-weight:600;">${escapeHtml(groupName)}</span>
                                    </span>
                                ` : ''}
                            </h3>
                            <small>${escapeHtml(post.created_at || '')}</small>
                        </div>
                    </div>
                </div>

                <div class="body">
                    ${post.content ? `<div class="caption" style="margin-bottom: 1rem;">
                        <p class="post-text">${escapeHtml(post.content)}</p>
                    </div>` : ''}
                    ${postImage ? `<div class="photo post-image"><img src="${escapeHtml(postImage)}" alt="Post image"></div>` : ''}
                </div>

                <div class="stats">
                    <div class="stat"><i class="uil uil-heart"></i> ${post.upvote_count || 0}</div>
                    <div class="stat"><i class="uil uil-comment-dots"></i> ${post.comment_count || 0}</div>
                </div>
            </div>
        `;
        const el = wrapper.firstElementChild;

        el.addEventListener('click', (e) => {
            if (e.target.closest('a, button')) return;
            window.location.href = postUrl;
        });

        return el;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
});