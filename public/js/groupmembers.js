document.addEventListener('DOMContentLoaded', function () {
    const shell = document.querySelector('.group-members-shell');
    if (!shell) {
        return;
    }

    const searchInput = document.getElementById('membersSearchInput');
    const rows = Array.from(shell.querySelectorAll('.member-row'));
    const sectionHeaders = Array.from(shell.querySelectorAll('.member-section-header'));
    const emptyState = document.getElementById('membersEmptyState');
    const visibleCountEl = document.getElementById('membersVisibleCount');
    const kickConfirmModal = document.getElementById('kickConfirmModal');
    const kickConfirmMemberName = document.getElementById('kickConfirmMemberName');
    const cancelKickBtn = document.getElementById('cancelKickBtn');
    const confirmKickBtn = document.getElementById('confirmKickBtn');
    const roleVoteModal = document.getElementById('roleVoteModal');
    const roleVoteForm = document.getElementById('roleVoteForm');
    const closeRoleVoteModal = document.getElementById('closeRoleVoteModal');
    const cancelRoleVoteBtn = document.getElementById('cancelRoleVoteBtn');
    const submitRoleVoteBtn = document.getElementById('submitRoleVoteBtn');
    const groupId = parseInt(shell.getAttribute('data-group-id') || '0', 10);
    const isAdmin = shell.getAttribute('data-is-admin') === '1';
    let pendingKick = null;
    let pendingRoleVote = null;

    function notify(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }
        window.alert(message);
    }

    function closeAllMenus() {
        shell.querySelectorAll('.member-menu.open').forEach((menu) => {
            menu.classList.remove('open');
            menu.style.left = '';
            menu.style.top = '';
        });
        shell.querySelectorAll('.member-row.menu-open').forEach((row) => {
            row.classList.remove('menu-open');
        });
    }

    function openKickConfirmModal(memberName) {
        if (kickConfirmMemberName) {
            kickConfirmMemberName.textContent = memberName;
        }

        if (kickConfirmModal) {
            kickConfirmModal.classList.add('active');
            kickConfirmModal.setAttribute('aria-hidden', 'false');
        }
    }

    function closeKickConfirmModal() {
        pendingKick = null;
        if (kickConfirmModal) {
            kickConfirmModal.classList.remove('active');
            kickConfirmModal.setAttribute('aria-hidden', 'true');
        }
    }

    function openRoleVote(payload) {
        if (!roleVoteModal || !roleVoteForm) {
            return;
        }

        pendingRoleVote = payload;
        roleVoteForm.reset();

        const title = document.getElementById('roleVoteTitle');
        const targetInput = document.getElementById('roleVoteTargetUserId');
        const nameInput = document.getElementById('roleVoteMemberName');
        const fromRoleInput = document.getElementById('roleVoteFromRole');
        const toRoleInput = document.getElementById('roleVoteToRole');

        if (title) {
            title.textContent = payload.action === 'demote' ? 'Start Demotion Vote' : 'Start Promotion Vote';
        }
        if (targetInput) targetInput.value = String(payload.userId || '');
        if (nameInput) nameInput.value = payload.memberName || '';
        if (fromRoleInput) fromRoleInput.value = payload.fromRole || 'member';
        if (toRoleInput) toRoleInput.value = payload.toRole || 'member';

        roleVoteModal.classList.add('active');
        roleVoteModal.setAttribute('aria-hidden', 'false');
    }

    function closeRoleVote() {
        pendingRoleVote = null;
        if (roleVoteModal) {
            roleVoteModal.classList.remove('active');
            roleVoteModal.setAttribute('aria-hidden', 'true');
        }
    }

    function positionMenu(menu, trigger) {
        const menuRect = menu.getBoundingClientRect();
        const triggerRect = trigger.getBoundingClientRect();
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const gap = 8;
        const menuWidth = menuRect.width || 190;
        const menuHeight = menuRect.height || 120;

        let left = triggerRect.right - menuWidth;
        if (left + menuWidth > viewportWidth - gap) {
            left = viewportWidth - menuWidth - gap;
        }
        if (left < gap) {
            left = gap;
        }

        let top = triggerRect.bottom + gap;
        if (top + menuHeight > viewportHeight - gap) {
            top = triggerRect.top - menuHeight - gap;
        }
        if (top < gap) {
            top = gap;
        }

        menu.style.left = Math.round(left) + 'px';
        menu.style.top = Math.round(top) + 'px';
    }

    function applyFilters() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach((row) => {
            const name = row.getAttribute('data-name') || '';
            const textMatch = !query || name.indexOf(query) !== -1;
            const visible = textMatch;

            row.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        if (visibleCountEl) {
            visibleCountEl.textContent = String(visibleCount);
        }

        sectionHeaders.forEach((header) => {
            const role = header.getAttribute('data-section') || '';
            const hasVisibleRowsInSection = rows.some((row) => {
                return !row.hidden && (row.getAttribute('data-role') || '') === role;
            });
            header.hidden = !hasVisibleRowsInSection;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (cancelKickBtn) {
        cancelKickBtn.addEventListener('click', closeKickConfirmModal);
    }

    if (kickConfirmModal) {
        kickConfirmModal.addEventListener('click', function (event) {
            if (event.target === kickConfirmModal) {
                closeKickConfirmModal();
            }
        });
    }

    if (confirmKickBtn) {
        confirmKickBtn.addEventListener('click', async function () {
            if (!pendingKick || !isAdmin || !groupId) {
                return;
            }

            const targetUserId = pendingKick.userId;
            const targetUserName = pendingKick.memberName || 'this member';
            if (!targetUserId) {
                return;
            }

            const originalText = confirmKickBtn.textContent;
            confirmKickBtn.disabled = true;
            confirmKickBtn.textContent = 'Deleting...';

            try {
                const response = await fetch('./index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'sub_action=kick_member&group_id=' + encodeURIComponent(groupId) + '&target_user_id=' + encodeURIComponent(targetUserId)
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to remove member');
                }

                if (pendingKick && pendingKick.row) {
                    pendingKick.row.remove();
                }
                applyFilters();
                closeKickConfirmModal();
                notify('Member removed successfully.', 'success');
            } catch (error) {
                notify(error.message || 'Failed to remove member', 'error');
            } finally {
                confirmKickBtn.disabled = false;
                confirmKickBtn.textContent = originalText;
            }
        });
    }

    closeRoleVoteModal?.addEventListener('click', closeRoleVote);
    cancelRoleVoteBtn?.addEventListener('click', closeRoleVote);

    roleVoteModal?.addEventListener('click', function (event) {
        if (event.target === roleVoteModal) {
            closeRoleVote();
        }
    });

    roleVoteForm?.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!pendingRoleVote || !isAdmin || !groupId) {
            return;
        }

        const reason = (document.getElementById('roleVoteReason')?.value || '').trim();
        if (!reason) {
            notify('Reason is required to start a role vote.', 'error');
            return;
        }

        const originalText = submitRoleVoteBtn ? submitRoleVoteBtn.textContent : '';
        if (submitRoleVoteBtn) {
            submitRoleVoteBtn.disabled = true;
            submitRoleVoteBtn.textContent = 'Starting...';
        }

        try {
            const response = await fetch('./index.php?controller=Group&action=handleAjax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    sub_action: 'start_governance_vote',
                    group_id: String(groupId),
                    vote_type: 'member_role_change',
                    target_type: 'user',
                    target_id: String(pendingRoleVote.userId),
                    from_role: String(pendingRoleVote.fromRole || 'member'),
                    to_role: String(pendingRoleVote.toRole || 'member'),
                    reason
                }).toString()
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to start role vote');
            }

            closeRoleVote();
            notify('Role change vote started. Check Governance for approvals.', 'success');
        } catch (error) {
            notify(error.message || 'Failed to start role vote', 'error');
        } finally {
            if (submitRoleVoteBtn) {
                submitRoleVoteBtn.disabled = false;
                submitRoleVoteBtn.textContent = originalText || 'Start Vote';
            }
        }
    });

    function closeMenusOnViewportChange() {
        closeAllMenus();
    }

    window.addEventListener('scroll', closeMenusOnViewportChange, true);
    document.addEventListener('scroll', closeMenusOnViewportChange, true);
    window.addEventListener('resize', closeMenusOnViewportChange);

    rows.forEach((row) => {
        row.addEventListener('click', function (event) {
            if (event.target.closest('.member-menu')) {
                return;
            }
            const profileUrl = this.getAttribute('data-profile-url');
            if (profileUrl) {
                window.location.href = profileUrl;
            }
        });
    });

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.member-menu-trigger');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            const menu = trigger.closest('.member-menu');
            const willOpen = menu && !menu.classList.contains('open');
            closeAllMenus();
            if (menu && willOpen) {
                menu.classList.add('open');
                positionMenu(menu.querySelector('.member-menu-dropdown'), trigger);
                const row = menu.closest('.member-row');
                if (row) {
                    row.classList.add('menu-open');
                }
            }
            return;
        }

        if (!event.target.closest('.member-menu')) {
            closeAllMenus();
        }
    });

    document.addEventListener('click', async function (event) {
        const kickBtn = event.target.closest('[data-member-action="kick"]');
        if (!kickBtn) {
            const roleBtn = event.target.closest('[data-member-action="promote"], [data-member-action="demote"]');
            if (!roleBtn) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (!isAdmin || !groupId) {
                return;
            }

            const targetUserId = parseInt(roleBtn.getAttribute('data-user-id') || '0', 10);
            const targetUserName = roleBtn.getAttribute('data-user-name') || 'this member';
            const fromRole = roleBtn.getAttribute('data-current-role') || 'member';
            const requestedRole = roleBtn.getAttribute('data-requested-role') || 'member';
            const action = roleBtn.getAttribute('data-member-action') || 'promote';

            if (!targetUserId) {
                return;
            }

            closeAllMenus();
            openRoleVote({
                userId: targetUserId,
                memberName: targetUserName,
                fromRole,
                toRole: requestedRole,
                action
            });
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (!isAdmin || !groupId) {
            return;
        }

        const targetUserId = parseInt(kickBtn.getAttribute('data-user-id') || '0', 10);
        const targetUserName = kickBtn.getAttribute('data-user-name') || 'this member';
        if (!targetUserId) {
            return;
        }

        closeAllMenus();
        pendingKick = {
            userId: targetUserId,
            memberName: targetUserName,
            row: kickBtn.closest('.member-row')
        };
        openKickConfirmModal(targetUserName);
    });

    document.addEventListener('click', function (event) {
        const reportBtn = event.target.closest('[data-report-type]');
        if (!reportBtn) {
            return;
        }
        closeAllMenus();
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeKickConfirmModal();
            closeRoleVote();
        }
    });

    applyFilters();
});
