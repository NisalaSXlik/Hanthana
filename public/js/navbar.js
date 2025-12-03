// navbar.js - Handles navbar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Profile dropdown
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) {
        const dropdownMenu = profileDropdown.querySelector('.profile-dropdown');
        
        profileDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            dropdownMenu.classList.remove('show');
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdownMenu.classList.remove('show');
            }
        });
    }
    
    const navSearchInput = document.getElementById('navSearchInput');
    const navSearchResults = document.getElementById('navSearchResults');
    let navSearchTimer;

    const hideNavSearchResults = () => {
        if (!navSearchResults) {
            return;
        }
        navSearchResults.innerHTML = '';
        navSearchResults.classList.add('hidden');
    };

    const renderNavSearchResults = (payload) => {
        if (!navSearchResults) {
            return;
        }

        const users = Array.isArray(payload?.users) ? payload.users : [];
        const groups = Array.isArray(payload?.groups) ? payload.groups : [];

        navSearchResults.innerHTML = '';

        const sections = [];

        const createSection = (title, items, renderItem) => {
            if (!items.length) {
                return null;
            }

            const section = document.createElement('div');
            section.className = 'nav-search-section';

            const heading = document.createElement('p');
            heading.className = 'nav-search-section__title';
            heading.textContent = title;
            section.appendChild(heading);

            const list = document.createElement('ul');
            items.forEach((item) => list.appendChild(renderItem(item)));
            section.appendChild(list);

            return section;
        };

        const renderUserItem = (item) => {
            const li = document.createElement('li');
            const link = document.createElement('a');
            link.href = item.profileUrl;

            const avatar = document.createElement('img');
            avatar.src = item.avatar;
            avatar.alt = item.name;

            const meta = document.createElement('div');
            meta.className = 'result-meta';

            const name = document.createElement('strong');
            name.textContent = item.name;

            const username = document.createElement('span');
            username.textContent = `@${item.username}`;

            meta.appendChild(name);
            meta.appendChild(username);

            link.appendChild(avatar);
            link.appendChild(meta);
            li.appendChild(link);
            return li;
        };

        const renderGroupItem = (item) => {
            const li = document.createElement('li');
            li.classList.add('nav-search-result--group');

            const link = document.createElement('a');
            link.href = item.groupUrl;

            const avatar = document.createElement('img');
            avatar.src = item.avatar;
            avatar.alt = item.name;

            const meta = document.createElement('div');
            meta.className = 'result-meta';

            const name = document.createElement('strong');
            name.textContent = item.name;

            const details = document.createElement('span');
            const membersLabel = `${item.memberCount || 0} member${(item.memberCount || 0) === 1 ? '' : 's'}`;
            const tagLabel = item.tag ? `#${item.tag}` : '';
            details.textContent = tagLabel ? `${tagLabel} • ${membersLabel}` : membersLabel;

            meta.appendChild(name);
            meta.appendChild(details);

            link.appendChild(avatar);
            link.appendChild(meta);
            li.appendChild(link);

            const actions = document.createElement('div');
            actions.className = 'nav-search-result__actions';

            if (item.isOwner) {
                const status = document.createElement('span');
                status.className = 'nav-search-action-status';
                status.textContent = 'You manage this';
                actions.appendChild(status);
            } else if (item.isMember) {
                const status = document.createElement('span');
                status.className = 'nav-search-action-status';
                status.textContent = 'Joined';
                actions.appendChild(status);
            } else if (item.hasPendingRequest) {
                const status = document.createElement('span');
                status.className = 'nav-search-action-status';
                status.textContent = 'Request sent';
                actions.appendChild(status);
            } else {
                const joinButton = document.createElement('button');
                joinButton.type = 'button';
                joinButton.className = 'nav-search-action-btn';
                joinButton.textContent = item.privacy === 'public' ? 'Join' : 'Private';
                joinButton.disabled = item.privacy !== 'public';
                joinButton.dataset.groupJoin = 'true';
                joinButton.dataset.groupId = item.id;
                actions.appendChild(joinButton);
            }

            li.appendChild(actions);
            return li;
        };

        const groupSection = createSection('Groups', groups, renderGroupItem);
        if (groupSection) {
            sections.push(groupSection);
        }

        const userSection = createSection('People', users, renderUserItem);
        if (userSection) {
            sections.push(userSection);
        }

        if (!sections.length) {
            navSearchResults.innerHTML = '<p class="search-empty">No matching people or groups</p>';
        } else {
            sections.forEach((section) => navSearchResults.appendChild(section));
        }

        navSearchResults.classList.remove('hidden');
    };

    if (navSearchInput && navSearchResults) {
        const basePathRaw = window.BASE_PATH || navSearchInput.dataset.basePath || '/';
        const basePath = basePathRaw.endsWith('/') ? basePathRaw : `${basePathRaw}/`;

        const normalizeSearchPayload = (data) => {
            if (!data || typeof data !== 'object') {
                return { users: [], groups: [] };
            }

            if (Array.isArray(data.results)) {
                return { users: data.results, groups: [] };
            }

            if (data.results && typeof data.results === 'object') {
                return {
                    users: Array.isArray(data.results.users) ? data.results.users : [],
                    groups: Array.isArray(data.results.groups) ? data.results.groups : [],
                };
            }

            return {
                users: Array.isArray(data.users) ? data.users : [],
                groups: Array.isArray(data.groups) ? data.groups : [],
            };
        };

        navSearchInput.addEventListener('input', (event) => {
            const term = event.target.value.trim();
            clearTimeout(navSearchTimer);

            if (term.length < 2) {
                hideNavSearchResults();
                return;
            }

            navSearchTimer = setTimeout(() => {
                fetch(`${basePath}index.php?controller=Search&action=all&query=${encodeURIComponent(term)}`)
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (!data || !data.success) {
                            hideNavSearchResults();
                            return;
                        }
                        renderNavSearchResults(normalizeSearchPayload(data));
                    })
                    .catch(() => hideNavSearchResults());
            }, 250);
        });

        navSearchInput.addEventListener('focus', () => {
            if (navSearchInput.value.trim().length >= 2 && navSearchResults.innerHTML !== '') {
                navSearchResults.classList.remove('hidden');
            }
        });

        document.addEventListener('click', (event) => {
            if (event.target === navSearchInput || (navSearchResults && navSearchResults.contains(event.target))) {
                return;
            }
            hideNavSearchResults();
        });

        navSearchResults.addEventListener('click', (event) => {
            const joinButton = event.target.closest('[data-group-join]');
            if (!joinButton) {
                return;
            }

            event.preventDefault();

            if (joinButton.disabled) {
                return;
            }

            const groupId = parseInt(joinButton.dataset.groupId || '', 10);
            if (!groupId) {
                return;
            }

            const originalLabel = joinButton.textContent;
            joinButton.disabled = true;
            joinButton.dataset.state = 'loading';
            joinButton.textContent = 'Joining...';

            fetch(`${basePath}index.php?controller=Group&action=handleAjax`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `sub_action=join&group_id=${encodeURIComponent(groupId)}`
            })
                .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    const success = ok && data && data.success;
                    const message = data && data.message ? data.message : null;

                    if (success || (message && /already joined/i.test(message))) {
                        joinButton.textContent = success ? 'Joined' : 'Already joined';
                        joinButton.dataset.state = 'joined';
                        joinButton.disabled = true;
                        joinButton.classList.add('is-success');
                        // Update sidebar member count
                        if (success && window.updateSidebarGroupMemberCount) {
                            window.updateSidebarGroupMemberCount(groupId, 1);
                        }
                        if (typeof showToast === 'function' && success) {
                            showToast(message || 'Joined group successfully', 'success');
                        }
                        return;
                    }

                    throw new Error(message || 'Unable to join group.');
                })
                .catch((error) => {
                    console.error(error);
                    joinButton.disabled = false;
                    joinButton.dataset.state = 'idle';
                    joinButton.textContent = originalLabel;
                    if (typeof showToast === 'function') {
                        showToast(error.message || 'Unable to join group.', 'error');
                    } else {
                        alert(error.message || 'Unable to join group.');
                    }
                });
        });
    }

    
    // Menu navigation
    const menuItems = document.querySelectorAll('.menu-item');
    const contentSections = document.querySelectorAll('.content-section');
    
    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const target = item.getAttribute('data-target');
            
            contentSections.forEach(section => {
                section.classList.remove('active');
            });
            
            document.getElementById(target).classList.add('active');
            
            menuItems.forEach(menuItem => {
                menuItem.classList.remove('active');
            });
            item.classList.add('active');
        });
    });
});

