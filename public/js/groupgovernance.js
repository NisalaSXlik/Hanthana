(function () {
    const pageRoot = document.body;
    const eventsHost = document.getElementById('governanceEventsList');
    const basePath = pageRoot && pageRoot.dataset.basePath
        ? pageRoot.dataset.basePath
        : (typeof BASE_PATH !== 'undefined' ? BASE_PATH : '/');

    // Read GROUP_ID from page dataset
    const groupId = Number(pageRoot && pageRoot.dataset.groupId ? pageRoot.dataset.groupId : 0);
    
    if (groupId <= 0) {
        console.error('Governance Page Error: GROUP_ID not available', { groupId, availableVars: Object.keys(window).filter(k => k.includes('GROUP')) });
        return;
    }

    const eventTypeFilter = document.getElementById('eventTypeFilter');
    const eventTimeFilter = document.getElementById('eventTimeFilter');
    const governanceEvents = Array.from(document.querySelectorAll('.governance-event-item'));

    const seeVotesModal = document.getElementById('seeVotesModal');
    const closeSeeVotesModal = document.getElementById('closeSeeVotesModal');
    const votesPopupInFavorList = document.getElementById('votesPopupInFavorList');
    const votesPopupAgainstList = document.getElementById('votesPopupAgainstList');
    const votesPopupPendingList = document.getElementById('votesPopupPendingList');
    const votesPopupPendingTitle = document.getElementById('votesPopupPendingTitle');

    let voteEvents = [];
    try {
        const rawEvents = eventsHost && eventsHost.dataset.voteEvents ? eventsHost.dataset.voteEvents : '[]';
        const parsedEvents = JSON.parse(rawEvents);
        voteEvents = Array.isArray(parsedEvents) ? parsedEvents : [];
    } catch (err) {
        voteEvents = [];
    }

    function openModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.add('is-open');
        modalEl.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.remove('is-open');
        modalEl.setAttribute('aria-hidden', 'true');
    }

    function showToast(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'info');
            return;
        }
        alert(message);
    }

    function applyGovernanceFilters() {
        const typeValue = eventTypeFilter ? eventTypeFilter.value : 'all';
        const timeValue = eventTimeFilter ? eventTimeFilter.value : 'current';

        governanceEvents.forEach((eventEl) => {
            const matchesType = typeValue === 'all' || eventEl.dataset.type === typeValue;
            const matchesTime = eventEl.dataset.time === timeValue;
            eventEl.style.display = (matchesType && matchesTime) ? '' : 'none';
        });
    }

    async function postGovernanceAction(fields) {
        const payload = new FormData();
        Object.keys(fields || {}).forEach((key) => {
            payload.append(key, String(fields[key]));
        });

        const response = await fetch(basePath + 'index.php?controller=Group&action=handleAjax', {
            method: 'POST',
            body: payload
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Governance action failed.');
        }

        return data;
    }

    function buildAvatarUrl(raw) {
        const value = String(raw || '').trim();
        if (!value) {
            return basePath + 'uploads/user_dp/default_user_dp.jpg';
        }
        if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
            return value;
        }
        return basePath + value;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function updateCountsFromEvents() {
        voteEvents.forEach((event) => {
            if (!event || !event.event_id) return;
            const inFavorElem = document.querySelector(`.vote-count[data-event-id="${event.event_id}"][data-vote-choice="in_favor"]`);
            const againstElem = document.querySelector(`.vote-count[data-event-id="${event.event_id}"][data-vote-choice="not_in_favor"]`);
            
            if (inFavorElem) {
                inFavorElem.textContent = String(Number(event.in_favor || 0));
            }
            if (againstElem) {
                againstElem.textContent = String(Number(event.against || 0));
            }
        });
    }

    function syncVoteControlsFromEvents() {
        const eventVoteMap = new Map();
        voteEvents.forEach((event) => {
            const eventId = Number(event && event.event_id ? event.event_id : 0);
            if (eventId > 0) {
                eventVoteMap.set(eventId, String(event.viewer_vote || '').toLowerCase());
            }
        });

        document.querySelectorAll('.vote-choice-checkbox').forEach((checkbox) => {
            const eventId = Number(checkbox.getAttribute('data-event-id') || 0);
            const voteChoice = String(checkbox.getAttribute('data-vote-choice') || '').toLowerCase();
            const viewerVote = eventVoteMap.get(eventId) || '';
            checkbox.checked = viewerVote !== '' && viewerVote === voteChoice;
        });
    }

    function renderVotesList(listEl, voters, voteType) {
        if (!listEl) return;
        const rows = Array.isArray(voters) ? voters : [];
        if (!rows.length) {
            const pendingLabel = voteType === 'pending'
                ? ((votesPopupPendingTitle && /didn't vote/i.test(votesPopupPendingTitle.textContent || '')) ? "No one missed voting." : 'Everyone has voted.')
                : 'No votes yet.';
            listEl.innerHTML = `<li class="votes-popup-empty">${pendingLabel}</li>`;
            return;
        }

        listEl.innerHTML = rows.map((voter) => {
            const iconClass = voteType === 'up'
                ? 'uil-thumbs-up vote-mark-up'
                : (voteType === 'down' ? 'uil-thumbs-down vote-mark-down' : 'uil-clock vote-mark-pending');
            return '<li class="votes-popup-item">'
                + '<img src="' + escapeHtml(buildAvatarUrl(voter.avatar)) + '" alt="' + escapeHtml(voter.name) + '">'
                + '<div class="votes-popup-main">'
                + '<strong>' + escapeHtml(voter.name) + '</strong>'
                + '<small>@' + escapeHtml(voter.username) + '</small>'
                + '</div>'
                + '<i class="uil ' + iconClass + '"></i>'
                + '</li>';
        }).join('');
    }

    const pendingVoteEvents = new Set();

    function setVoteControlsDisabled(eventIndex, disabled) {
        document.querySelectorAll('.vote-choice-checkbox[data-event-index="' + eventIndex + '"]').forEach((input) => {
            input.disabled = disabled;
        });
    }

    if (eventTypeFilter) eventTypeFilter.addEventListener('change', applyGovernanceFilters);
    if (eventTimeFilter) eventTimeFilter.addEventListener('change', applyGovernanceFilters);
    applyGovernanceFilters();

    document.querySelectorAll('.vote-choice-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', async (event) => {
            const current = event.currentTarget;
            const eventIndex = current.getAttribute('data-event-index');
            const eventId = Number(current.getAttribute('data-event-id') || 0);
            const voteChoice = current.getAttribute('data-vote-choice') || '';

            if (!eventId || !voteChoice) {
                return;
            }

            if (pendingVoteEvents.has(eventId)) {
                syncVoteControlsFromEvents();
                return;
            }

            const isNowChecked = current.checked;
            const otherCheckboxes = document.querySelectorAll('.vote-choice-checkbox[data-event-index="' + eventIndex + '"]');

            // If unchecking, allow it to toggle off (withdraw vote)
            if (!isNowChecked) {
                // User unchecked it, submit withdrawal
                try {
                    pendingVoteEvents.add(eventId);
                    setVoteControlsDisabled(eventIndex, true);

                    const result = await postGovernanceAction({
                        sub_action: 'cast_governance_vote',
                        group_id: groupId,
                        event_id: eventId,
                        vote_choice: voteChoice,
                    });

                    if (!result.success) {
                        current.checked = true;
                        showToast(result.message || 'Vote withdrawal failed.', 'error');
                        return;
                    }

                    // Update voteEvents from response
                    if (result.events && Array.isArray(result.events)) {
                        voteEvents = result.events;
                        updateCountsFromEvents();
                        syncVoteControlsFromEvents();
                    }

                    showToast('Vote withdrawn.', 'success');
                } catch (error) {
                    current.checked = true;
                    showToast(error.message, 'error');
                } finally {
                    pendingVoteEvents.delete(eventId);
                    setVoteControlsDisabled(eventIndex, false);
                }
                return;
            }

            // Uncheck other checkboxes for this event (mutually exclusive)
            otherCheckboxes.forEach((peer) => {
                if (peer !== current && peer.checked) {
                    peer.checked = false;
                }
            });

            // Submit the vote
            try {
                pendingVoteEvents.add(eventId);
                setVoteControlsDisabled(eventIndex, true);

                const result = await postGovernanceAction({
                    sub_action: 'cast_governance_vote',
                    group_id: groupId,
                    event_id: eventId,
                    vote_choice: voteChoice,
                });

                if (!result.success) {
                    current.checked = false;
                    showToast(result.message || 'Voting failed.', 'error');
                    return;
                }

                // Update voteEvents from response
                if (result.events && Array.isArray(result.events)) {
                    voteEvents = result.events;
                    updateCountsFromEvents();
                    syncVoteControlsFromEvents();
                }

                showToast('Vote recorded.', 'success');
            } catch (error) {
                current.checked = false;
                showToast(error.message, 'error');
            } finally {
                pendingVoteEvents.delete(eventId);
                setVoteControlsDisabled(eventIndex, false);
            }
        });
    });

    document.querySelectorAll('.see-votes-popup-trigger').forEach((triggerBtn) => {
        triggerBtn.addEventListener('click', () => {
            const eventIndex = Number(triggerBtn.getAttribute('data-event-index'));
            const selected = voteEvents[eventIndex] || {};
            const status = String(selected.status || '').toLowerCase();
            const isPast = ['approved', 'passed', 'accepted', 'rejected', 'declined', 'expired'].includes(status);

            if (votesPopupPendingTitle) {
                votesPopupPendingTitle.textContent = isPast ? "Didn't Vote" : 'Pending';
            }

            renderVotesList(votesPopupInFavorList, selected.supporters || [], 'up');
            renderVotesList(votesPopupAgainstList, selected.opponents || [], 'down');
            renderVotesList(votesPopupPendingList, selected.pending_voters || [], 'pending');
            openModal(seeVotesModal);
        });
    });

    if (closeSeeVotesModal) {
        closeSeeVotesModal.addEventListener('click', () => closeModal(seeVotesModal));
    }

    [seeVotesModal].forEach((modalEl) => {
        if (!modalEl) return;
        modalEl.addEventListener('click', (event) => {
            if (event.target === modalEl) {
                closeModal(modalEl);
            }
        });
    });

    updateCountsFromEvents();
    syncVoteControlsFromEvents();
})();
