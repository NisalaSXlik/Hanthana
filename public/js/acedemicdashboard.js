(function () {
    const browser = document.getElementById('acdResourceBrowser');
    if (!browser) return;

    const tabs = Array.from(document.querySelectorAll('.acd-tab[data-tab]'));
    const breadcrumbEl = document.getElementById('acdBreadcrumb');
    const groupsPane = document.getElementById('acdGroupsPane');
    const binsPane = document.getElementById('acdBinsPane');
    const filesPane = document.getElementById('acdFilesPane');
    const groupsList = document.getElementById('acdGroupsList');
    const binsList = document.getElementById('acdBinsList');
    const filesList = document.getElementById('acdFilesList');

    const basePath = browser.dataset.basePath || '/';

    const state = {
        tab: 'all',
        navigationLevel: 'groups', // 'groups', 'bins', or 'files'
        selectedGroupId: 0,
        selectedGroupName: '',
        selectedBinId: 0,
        selectedBinName: '',
        groups: [],
        bins: [],
        files: [],
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatBytes(bytes) {
        const size = Number(bytes || 0);
        if (size <= 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const index = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
        const value = size / Math.pow(1024, index);
        return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
    }

    function formatDate(value) {
        if (!value) return 'recently';
        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return 'recently';
        return date.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    function getFileIconClass(fileName, fileType) {
        const lower = String(fileName || '').toLowerCase();
        if (lower.endsWith('.pdf')) return 'uil-file-bookmark-alt';
        if (lower.endsWith('.doc') || lower.endsWith('.docx')) return 'uil-file-alt';
        if (lower.endsWith('.xls') || lower.endsWith('.xlsx')) return 'uil-file-graph';
        if (lower.endsWith('.zip') || lower.endsWith('.rar') || lower.endsWith('.7z')) return 'uil-file-download-alt';
        if (String(fileType || '').toLowerCase() === 'image') return 'uil-image';
        return 'uil-file';
    }

    function buildDownloadUrl(filePath) {
        const cleanPath = String(filePath || '').replace(/^\/+/, '');
        if (!cleanPath) return '#';
        const normalizedBase = basePath.endsWith('/') ? basePath : `${basePath}/`;
        return `${normalizedBase}${cleanPath}`.replace(/([^:]\/)\/+/, '$1');
    }

    async function postJson(payload) {
        const url = `${basePath}index.php?controller=AcedemicDashboard&action=handleAjax`;
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Request failed');
        }
        return data;
    }

    function renderTabs() {
        tabs.forEach((tab) => {
            const active = tab.dataset.tab === state.tab;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function showPane(paneName) {
        groupsPane.style.display = paneName === 'groups' ? '' : 'none';
        binsPane.style.display = paneName === 'bins' ? '' : 'none';
        filesPane.style.display = paneName === 'files' ? '' : 'none';
    }

    function renderBreadcrumb() {
        breadcrumbEl.innerHTML = '';
    }

    function renderInlinePathRow() {
        if (state.tab === 'recent_uploads') {
            return `
                <div class="acd-inline-path-row" role="presentation">
                    <i class="uil uil-folder-open acd-inline-path-icon" aria-hidden="true"></i>
                    <span class="acd-inline-path-current">Recent Uploads</span>
                </div>`;
        }

        if (state.tab === 'top_downloads') {
            return `
                <div class="acd-inline-path-row" role="presentation">
                    <i class="uil uil-folder-open acd-inline-path-icon" aria-hidden="true"></i>
                    <span class="acd-inline-path-current">Top Downloads</span>
                </div>`;
        }

        if (state.tab === 'my_saves') {
            return `
                <div class="acd-inline-path-row" role="presentation">
                    <i class="uil uil-folder-open acd-inline-path-icon" aria-hidden="true"></i>
                    <span class="acd-inline-path-current">My Saves</span>
                </div>`;
        }

        if (state.navigationLevel === 'groups') {
            return `
                <div class="acd-inline-path-row" role="presentation">
                    <i class="uil uil-folder-open acd-inline-path-icon" aria-hidden="true"></i>
                    <span class="acd-inline-path-current">Groups</span>
                </div>`;
        }

        if (state.navigationLevel === 'bins') {
            return `
                <div class="acd-inline-path-row" role="presentation">
                    <i class="uil uil-folder-open acd-inline-path-icon" aria-hidden="true"></i>
                    <button type="button" class="acd-inline-path-btn" data-path-action="back-to-groups">Groups</button>
                    <span class="acd-inline-path-sep">›</span>
                    <span class="acd-inline-path-current">${escapeHtml(state.selectedGroupName || 'Group')}</span>
                </div>`;
        }

        return `
            <div class="acd-inline-path-row" role="presentation">
                <i class="uil uil-folder-open acd-inline-path-icon" aria-hidden="true"></i>
                <button type="button" class="acd-inline-path-btn" data-path-action="back-to-groups">Groups</button>
                <span class="acd-inline-path-sep">›</span>
                <button type="button" class="acd-inline-path-btn" data-path-action="back-to-bins">${escapeHtml(state.selectedBinName || 'Folder')}</button>
                <span class="acd-inline-path-sep">›</span>
                <span class="acd-inline-path-current">Files</span>
            </div>`;
    }

    function renderGroups() {
        if (state.navigationLevel !== 'groups') {
            return;
        }

        showPane('groups');

        if (!state.groups.length) {
            groupsList.innerHTML = '<div class="acd-empty">No groups with resources yet.</div>';
            return;
        }

        const rowsHtml = state.groups.map((group) => {
            const category = String(group.group_category || 'General');
            const fileCount = Number(group.file_count || 0);
            return `
                <button type="button" class="acd-list-btn acd-group-row" data-group-id="${Number(group.group_id)}" data-group-name="${escapeHtml(group.group_name || 'Group')}">
                    <span class="acd-group-col acd-group-name-col">
                        <i class="uil uil-folder acd-group-folder-icon" aria-hidden="true"></i>
                        <span class="acd-list-title">${escapeHtml(group.group_name || 'Group')}</span>
                    </span>
                    <span class="acd-group-col acd-group-category-col">${escapeHtml(category)}</span>
                    <span class="acd-group-col acd-group-files-col">${fileCount}</span>
                </button>`;
        }).join('');

        groupsList.innerHTML = `
            ${renderInlinePathRow()}
            <div class="acd-group-table-head" role="presentation">
                <span class="acd-group-col acd-group-name-col">Group</span>
                <span class="acd-group-col acd-group-category-col">Category</span>
                <span class="acd-group-col acd-group-files-col">Files</span>
            </div>
            ${rowsHtml}
        `;
    }

    function renderBins() {
        if (state.tab === 'recent_uploads' || state.tab === 'top_downloads' || state.tab === 'my_saves' || state.navigationLevel !== 'bins') {
            return;
        }

        showPane('bins');

        if (!state.bins.length) {
            binsList.innerHTML = '<div class="acd-empty">No bins available in this group.</div>';
            return;
        }

        const rowsHtml = state.bins.map((bin) => {
            const fileCount = Number(bin.file_count || 0);
            const latest = bin.latest_upload_at ? formatDate(bin.latest_upload_at) : 'No files yet';
            return `
                <button type="button" class="acd-list-btn acd-bin-row" data-bin-id="${Number(bin.bin_id)}" data-bin-name="${escapeHtml(bin.bin_name || 'Unnamed Bin')}">
                    <span class="acd-bin-col acd-bin-name-col">
                        <i class="uil uil-folder acd-group-folder-icon" aria-hidden="true"></i>
                        <span class="acd-list-title">${escapeHtml(bin.bin_name || 'Unnamed Bin')}</span>
                    </span>
                    <span class="acd-bin-col">Folder</span>
                    <span class="acd-bin-col acd-bin-files-col">${fileCount}</span>
                    <span class="acd-bin-col acd-bin-date-col">${escapeHtml(latest)}</span>
                </button>`;
        }).join('');

        binsList.innerHTML = `
            ${renderInlinePathRow()}
            <div class="acd-bin-table-head" role="presentation">
                <span class="acd-bin-col acd-bin-name-col">Name</span>
                <span class="acd-bin-col">Kind</span>
                <span class="acd-bin-col acd-bin-files-col">Files</span>
                <span class="acd-bin-col acd-bin-date-col">Date Added</span>
            </div>
            ${rowsHtml}
        `;
    }

    function renderFiles() {
        const isRecentMode = state.tab === 'recent_uploads' || state.tab === 'top_downloads' || state.tab === 'my_saves';

        if (isRecentMode) {
            showPane('files');
            if (!state.files.length) {
                filesList.innerHTML = `
                    ${renderInlinePathRow()}
                    <div class="acd-file-table-head" role="presentation">
                        <span class="acd-file-col acd-file-name-col">Name</span>
                        <span class="acd-file-col acd-file-source-col">Group / Folder</span>
                        <span class="acd-file-col acd-file-size-col">Size</span>
                        <span class="acd-file-col acd-file-downloads-col">Downloads</span>
                        <span class="acd-file-col acd-file-date-col">Date Added</span>
                        <span class="acd-file-col acd-file-actions-col">Actions</span>
                    </div>
                    <div class="acd-empty">No files found.</div>`;
                return;
            }
        } else if (state.navigationLevel === 'files') {
            showPane('files');
            if (!state.files.length) {
                filesList.innerHTML = `
                    ${renderInlinePathRow()}
                    <div class="acd-file-table-head" role="presentation">
                        <span class="acd-file-col acd-file-name-col">Name</span>
                        <span class="acd-file-col acd-file-size-col">Size</span>
                        <span class="acd-file-col acd-file-downloads-col">Downloads</span>
                        <span class="acd-file-col acd-file-date-col">Date Added</span>
                        <span class="acd-file-col acd-file-actions-col">Actions</span>
                    </div>
                    <div class="acd-empty">No files found for this folder.</div>`;
                return;
            }
        } else {
            return;
        }

        const headersHtml = isRecentMode
            ? `
                ${renderInlinePathRow()}
                <div class="acd-file-table-head" role="presentation">
                    <span class="acd-file-col acd-file-name-col">Name</span>
                    <span class="acd-file-col acd-file-source-col">Group / Folder</span>
                    <span class="acd-file-col acd-file-size-col">Size</span>
                    <span class="acd-file-col acd-file-downloads-col">Downloads</span>
                    <span class="acd-file-col acd-file-date-col">Date Added</span>
                    <span class="acd-file-col acd-file-actions-col">Actions</span>
                </div>`
            : `
                ${renderInlinePathRow()}
                <div class="acd-file-table-head" role="presentation">
                    <span class="acd-file-col acd-file-name-col">Name</span>
                    <span class="acd-file-col acd-file-size-col">Size</span>
                    <span class="acd-file-col acd-file-downloads-col">Downloads</span>
                    <span class="acd-file-col acd-file-date-col">Date Added</span>
                    <span class="acd-file-col acd-file-actions-col">Actions</span>
                </div>`;

        const rowsHtml = state.files.map((file) => {
            const icon = getFileIconClass(file.file_name, file.file_type);
            const downloads = Number(file.download_count || 0);
            const saved = Number(file.is_saved || 0) === 1;
            const sourceLabel = `${escapeHtml(file.group_name || 'Group')} • ${escapeHtml(file.bin_name || 'Bin')}`;
            const dateText = escapeHtml(formatDate(file.added_at));

            return `
                <div class="acd-file-row" data-media-id="${Number(file.media_id)}" data-file-path="${escapeHtml(file.file_path || '')}">
                    <span class="acd-file-col acd-file-name-col">
                        <i class="uil ${icon} acd-file-row-icon" aria-hidden="true"></i>
                        <span class="acd-file-name-text">${escapeHtml(file.file_name || 'Unnamed file')}</span>
                    </span>
                    ${isRecentMode ? `<span class="acd-file-col acd-file-source-col">${sourceLabel}</span>` : ''}
                    <span class="acd-file-col acd-file-size-col">${escapeHtml(formatBytes(file.file_size))}</span>
                    <span class="acd-file-col acd-file-downloads-col">${downloads}</span>
                    <span class="acd-file-col acd-file-date-col">${dateText}</span>
                    <span class="acd-file-col acd-file-actions-col">
                        <button type="button" class="acd-action-icon acd-download-btn" title="Download">
                            <i class="uil uil-download-alt"></i>
                        </button>
                        <button type="button" class="acd-action-icon acd-save-btn ${saved ? 'saved' : ''}" title="Save file">
                            <i class="uil uil-bookmark"></i>
                        </button>
                    </span>
                </div>`;
        }).join('');

        filesList.innerHTML = `${headersHtml}${rowsHtml}`;
    }

    function renderAll() {
        renderTabs();
        renderBreadcrumb();
        if (state.tab === 'recent_uploads' || state.tab === 'top_downloads' || state.tab === 'my_saves') {
            browser.classList.add('recent-mode');
        } else {
            browser.classList.remove('recent-mode');
        }
        renderGroups();
        renderBins();
        renderFiles();
    }

    async function loadData() {
        const response = await postJson({
            sub_action: 'resource_data',
            tab: state.tab,
            group_id: state.selectedGroupId,
            bin_id: state.selectedBinId,
        });

        state.groups = response.data.groups || [];
        state.bins = response.data.bins || [];
        state.files = response.data.files || [];
        state.selectedGroupId = Number(response.data.selected_group_id || 0);
        state.selectedBinId = Number(response.data.selected_bin_id || 0);

        renderAll();
    }

    function showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', async () => {
            if (state.tab === tab.dataset.tab) return;
            state.tab = tab.dataset.tab;
            const fileOnlyTabs = ['recent_uploads', 'top_downloads', 'my_saves'];
            state.navigationLevel = fileOnlyTabs.includes(tab.dataset.tab) ? 'files' : 'groups';
            state.selectedGroupId = 0;
            state.selectedBinId = 0;
            try {
                await loadData();
            } catch (error) {
                showToast(error.message || 'Could not load resources', 'error');
            }
        });
    });

    groupsList.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-group-id]');
        if (!button) return;

        state.selectedGroupId = Number(button.dataset.groupId);
        state.selectedGroupName = button.dataset.groupName || '';
        state.selectedBinId = 0;
        state.navigationLevel = 'bins';
        try {
            await loadData();
        } catch (error) {
            showToast(error.message || 'Could not load group bins', 'error');
        }
    });

    binsList.addEventListener('click', async (event) => {
        const pathBtn = event.target.closest('[data-path-action]');
        if (pathBtn) {
            const action = pathBtn.dataset.pathAction;
            if (action === 'back-to-groups') {
                state.navigationLevel = 'groups';
                state.selectedGroupId = 0;
                state.selectedBinId = 0;
            }
            try {
                await loadData();
            } catch (error) {
                showToast(error.message || 'Failed to navigate', 'error');
            }
            return;
        }

        const button = event.target.closest('[data-bin-id]');
        if (!button) return;

        state.selectedBinId = Number(button.dataset.binId);
        state.selectedBinName = button.dataset.binName || '';
        state.navigationLevel = 'files';
        try {
            await loadData();
        } catch (error) {
            showToast(error.message || 'Could not load files', 'error');
        }
    });

    filesList.addEventListener('click', async (event) => {
        const pathBtn = event.target.closest('[data-path-action]');
        if (pathBtn) {
            const action = pathBtn.dataset.pathAction;
            if (action === 'back-to-groups') {
                state.navigationLevel = 'groups';
                state.selectedGroupId = 0;
                state.selectedBinId = 0;
            } else if (action === 'back-to-bins') {
                state.navigationLevel = 'bins';
                state.selectedBinId = 0;
            }
            try {
                await loadData();
            } catch (error) {
                showToast(error.message || 'Failed to navigate', 'error');
            }
            return;
        }

        const item = event.target.closest('.acd-file-row');
        if (!item) return;

        const mediaId = Number(item.dataset.mediaId || 0);
        if (!mediaId) return;

        const downloadBtn = event.target.closest('.acd-download-btn');
        if (downloadBtn) {
            try {
                const response = await postJson({ sub_action: 'record_download', media_id: mediaId });
                const filePath = String(response.data.file_path || '');
                const url = buildDownloadUrl(filePath);
                if (url && url !== '#') {
                    const a = document.createElement('a');
                    a.href = url;
                    a.setAttribute('download', '');
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }
                const countEl = item.querySelector('.acd-file-downloads-col');
                if (countEl) {
                    countEl.textContent = String(Number(response.data.download_count || 0));
                }
            } catch (error) {
                showToast(error.message || 'Download failed', 'error');
            }
            return;
        }

        const saveBtn = event.target.closest('.acd-save-btn');
        if (saveBtn) {
            try {
                const response = await postJson({ sub_action: 'toggle_save', media_id: mediaId });
                const saved = !!response.data.saved;
                saveBtn.classList.toggle('saved', saved);
                if (state.tab === 'my_saves' && !saved) {
                    await loadData();
                }
            } catch (error) {
                showToast(error.message || 'Save failed', 'error');
            }
        }
    });

    loadData().catch((error) => {
        groupsList.innerHTML = '<div class="acd-empty">Failed to load groups.</div>';
        binsList.innerHTML = '<div class="acd-empty">Failed to load bins.</div>';
        filesList.innerHTML = '<div class="acd-empty">Failed to load files.</div>';
        showToast(error.message || 'Failed to load resources', 'error');
    });
})();
