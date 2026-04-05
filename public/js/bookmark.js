document.addEventListener('DOMContentLoaded', function () {
    const basePathRaw = typeof BASE_PATH !== 'undefined'
        ? BASE_PATH
        : (typeof window !== 'undefined' && typeof window.BASE_PATH !== 'undefined' ? window.BASE_PATH : './');
    const basePath = basePathRaw.endsWith('/') ? basePathRaw : basePathRaw + '/';

    const setBookmarkVisualState = (button, isBookmarked) => {
        if (!button) return;
        const icon = button.querySelector('i');

        button.classList.toggle('bookmarked', !!isBookmarked);
        button.setAttribute('aria-pressed', isBookmarked ? 'true' : 'false');

        if (icon) {
            icon.classList.toggle('bookmarked', !!isBookmarked);
            icon.classList.remove('uil', 'uil-bookmark', 'uil-bookmark-full', 'uis', 'uis-bookmark');

            if (isBookmarked) {
                icon.classList.add('uis', 'uis-bookmark');
            } else {
                icon.classList.add('uil', 'uil-bookmark');
            }
        }
    };

    const updateBookmarkStateInKnownLists = (postId, isBookmarked) => {
        const collections = [
            window.PERSONAL_POSTS,
            window.GROUP_POSTS,
            window.SAVED_POSTS,
            window.POSTS,
        ];

        collections.forEach((collection) => {
            if (!Array.isArray(collection)) return;

            collection.forEach((post) => {
                if (!post) return;
                const candidateId = parseInt(post.post_id ?? post.postId ?? 0, 10);
                if (candidateId !== postId) return;

                post.is_bookmarked = isBookmarked ? 1 : 0;
                post.isBookmarked = !!isBookmarked;
            });
        });
    };

    const syncBookmarkStateAcrossPage = (postId, isBookmarked) => {
        if (!postId) return;

        const selector = `.bookmark-btn[data-post-id="${postId}"]`;
        document.querySelectorAll(selector).forEach((bookmarkButton) => {
            setBookmarkVisualState(bookmarkButton, !!isBookmarked);
        });

        updateBookmarkStateInKnownLists(postId, !!isBookmarked);

        window.dispatchEvent(new CustomEvent('hanthana:bookmark-changed', {
            detail: {
                postId,
                bookmarked: !!isBookmarked,
            },
        }));
    };

    window.__hanthanaSetBookmarkState = (postId, isBookmarked) => {
        const normalizedPostId = parseInt(postId, 10);
        if (!normalizedPostId) return;
        syncBookmarkStateAcrossPage(normalizedPostId, !!isBookmarked);
    };

    document.addEventListener('click', async function (event) {
        const button = event.target.closest('.bookmark-btn');
        if (!button) return;

        event.preventDefault();

        if (button.dataset.busy === '1') return;

        const postId = parseInt(button.dataset.postId || '', 10);
        if (!postId) {
            window.showToast?.('Invalid post', 'error');
            return;
        }

        const currentlyBookmarked = button.classList.contains('bookmarked');
        const nextBookmarkedState = !currentlyBookmarked;

        setBookmarkVisualState(button, nextBookmarkedState);
        button.dataset.busy = '1';

        try {
            const formData = new FormData();
            formData.append('post_id', String(postId));
            formData.append('action', currentlyBookmarked ? 'remove' : 'add');

            const response = await fetch(basePath + 'index.php?controller=Posts&action=bookmark', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!data.success) {
                setBookmarkVisualState(button, currentlyBookmarked);
                window.showToast?.(data.message || 'Unable to update bookmark', 'error');
                return;
            }

            syncBookmarkStateAcrossPage(postId, !!data.bookmarked);
            window.showToast?.(data.message || (data.bookmarked ? 'Post saved' : 'Bookmark removed'), 'success');
        } catch (error) {
            console.error('Bookmark request failed:', error);
            setBookmarkVisualState(button, currentlyBookmarked);
            window.showToast?.('Unable to update bookmark', 'error');
        } finally {
            button.dataset.busy = '0';
        }
    });
});
