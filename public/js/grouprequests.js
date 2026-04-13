document.addEventListener('DOMContentLoaded', function () {
    const feedbackBox = document.getElementById('groupRequestsFeedback');

    const showFeedback = (message, isError) => {
        if (!feedbackBox) {
            if (isError) {
                window.alert(message);
            }
            return;
        }

        feedbackBox.textContent = message;
        feedbackBox.style.display = 'block';
        feedbackBox.classList.toggle('error', Boolean(isError));
        feedbackBox.classList.toggle('success', !isError);

        window.setTimeout(function () {
            feedbackBox.style.display = 'none';
        }, 3000);
    };

    const setLoadingState = (button, loading) => {
        if (!button) {
            return;
        }

        if (loading) {
            button.dataset.originalText = button.textContent;
            button.textContent = 'Please wait...';
            button.disabled = true;
            return;
        }

        if (button.dataset.originalText) {
            button.textContent = button.dataset.originalText;
        }
        button.disabled = false;
    };

    document.querySelectorAll('.manage-tabs a[data-tab]').forEach(function (tabLink) {
        tabLink.addEventListener('click', function (event) {
            event.preventDefault();

            const selectedTab = this.getAttribute('data-tab');
            if (!selectedTab) {
                return;
            }

            document.querySelectorAll('.manage-tabs li').forEach(function (tabItem) {
                tabItem.classList.remove('active');
            });
            this.parentElement.classList.add('active');

            document.querySelectorAll('.manage-content .tab-content').forEach(function (section) {
                section.classList.remove('active');
            });

            const activeSection = document.getElementById(selectedTab + '-content');
            if (activeSection) {
                activeSection.classList.add('active');
            }
        });
    });

    const callRequestAction = async (subAction, userId, groupId) => {
        const body = new URLSearchParams();
        body.append('sub_action', subAction);
        body.append('user_id', String(userId));
        body.append('group_id', String(groupId));

        const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
            credentials: 'same-origin'
        });

        return response.json();
    };

    const callOtherRequestAction = async (subAction, requestId, groupId, requestKind) => {
        const body = new URLSearchParams();
        body.append('sub_action', subAction);
        body.append('request_id', String(requestId));
        body.append('group_id', String(groupId));
        body.append('request_kind', String(requestKind || 'other'));

        const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
            credentials: 'same-origin'
        });

        return response.json();
    };

    document.querySelectorAll('.approve-request, .reject-request').forEach(function (button) {
        button.addEventListener('click', async function () {
            const userId = parseInt(this.getAttribute('data-user-id') || '0', 10);
            const groupId = parseInt(this.getAttribute('data-group-id') || String(window.GROUP_ID || 0), 10);
            if (!userId || !groupId) {
                showFeedback('Invalid request payload.', true);
                return;
            }

            const isApprove = this.classList.contains('approve-request');
            const action = isApprove ? 'approve_request' : 'reject_request';

            try {
                setLoadingState(this, true);
                const data = await callRequestAction(action, userId, groupId);
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Action failed');
                }

                const row = document.getElementById('request-' + userId);
                if (row) {
                    row.remove();
                }

                showFeedback(data.message || (isApprove ? 'Request approved.' : 'Request rejected.'), false);

                const remainingRows = document.querySelectorAll('#join-requests-content .request-row');
                const list = document.querySelector('#join-requests-content .requests-list');
                if (remainingRows.length === 0 && list) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'manage-empty-inline';
                    emptyState.textContent = 'No pending join requests right now.';
                    list.replaceWith(emptyState);
                }
            } catch (error) {
                showFeedback(error.message || 'Unable to process request.', true);
            } finally {
                setLoadingState(this, false);
            }
        });
    });

    document.querySelectorAll('.approve-other-request, .reject-other-request').forEach(function (button) {
        button.addEventListener('click', async function () {
            const requestId = parseInt(this.getAttribute('data-request-id') || '0', 10);
            const groupId = parseInt(this.getAttribute('data-group-id') || String(window.GROUP_ID || 0), 10);
            const requestKind = this.getAttribute('data-request-kind') || 'other';

            if (!requestId || !groupId) {
                showFeedback('Invalid request payload.', true);
                return;
            }

            const isApprove = this.classList.contains('approve-other-request');
            const action = isApprove ? 'approve_other_request' : 'reject_other_request';

            try {
                setLoadingState(this, true);
                const data = await callOtherRequestAction(action, requestId, groupId, requestKind);
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Action failed');
                }

                const row = this.closest('.request-row');
                if (row) {
                    row.remove();
                }

                showFeedback(data.message || (isApprove ? 'Request approved.' : 'Request rejected.'), false);
            } catch (error) {
                showFeedback(error.message || 'Unable to process request.', true);
            } finally {
                setLoadingState(this, false);
            }
        });
    });
});
