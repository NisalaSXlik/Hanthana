import { api } from "./utils/api.js";

(function () {
    const shell = document.querySelector('.filebank-shell');
    const breadcrumb = document.getElementById('fbBreadcrumb');
    const searchInput = document.getElementById('fileSearchInput');
    const statusText = document.getElementById('fb-status-text');
    const binNavRow = document.getElementById('fbBinNavRow');
    const backBtn = document.getElementById('fbBackBtn');
    const currentBinLabel = document.getElementById('fbCurrentBinLabel');
    const body = document.getElementById('fileBankBody');
    const dataContainer = document.getElementById('fbDataContainer');

    const createBinForm = document.getElementById('createBinForm');
    const fileForm = document.getElementById('fileForm');
    const postViewModal = document.getElementById('postViewModal');
    const postViewMenuTrigger = document.getElementById('postViewFileMenuTrigger');
    const postViewMenu = document.getElementById('postViewFileMenu');
    const postViewRenameBtn = document.getElementById('postViewRenameBtn');
    const postViewDeleteBtn = document.getElementById('postViewDeleteBtn');
    const postViewReportBtn = document.getElementById('postViewReportBtn');

    const urlParams = new URLSearchParams(window.location.search);
    const groupId = Number(
        window.FILEBANK_GROUP_ID
        || window.CURRENT_GROUP_ID
        || urlParams.get('group_id')
        || urlParams.get('groupId')
        || urlParams.get('id')
        || 0
    );
    const currentUserId = Number(window.FILEBANK_CURRENT_USER_ID || 0);
    const canModerate = window.FILEBANK_CAN_MODERATE === true || window.FILEBANK_CAN_MODERATE === 'true';

    let bins = [];
    let activeBinId = null;
    let searchQuery = '';
    let activeModalFile = null;

    function notify(msg, type = 'info') {
        const mappedType = type === 'danger' ? 'error' : type;
        if (typeof window.showToast === 'function') {
            window.showToast(msg, mappedType);
        }
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.fb-dropdown.show').forEach(dropdown => dropdown.classList.remove('show'));
    }

    function setRootBreadcrumb() {
        breadcrumb.innerHTML = '<i class="uil uil-folder"></i><span class="fb-breadcrumb-part active" data-path="root">File Bank</span>';
    }

    function setBinBreadcrumb(binName) {
        breadcrumb.innerHTML = `
            <i class="uil uil-folder"></i>
            <span class="fb-breadcrumb-part" data-path="root">File Bank</span>
            <span class="fb-breadcrumb-sep">›</span>
            <span class="fb-breadcrumb-part active">${escapeHtml(binName)}</span>`;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getIconClassByFileName(name) {
        const lower = String(name || '').toLowerCase();
        if (lower.endsWith('.pdf')) return 'icon-pdf';
        if (lower.endsWith('.doc') || lower.endsWith('.docx')) return 'icon-doc';
        if (lower.endsWith('.xls') || lower.endsWith('.xlsx')) return 'icon-xls';
        if (lower.endsWith('.txt')) return 'icon-txt';
        if (lower.endsWith('.jpg') || lower.endsWith('.jpeg') || lower.endsWith('.png') || lower.endsWith('.gif') || lower.endsWith('.webp')) return 'icon-img';
        if (lower.endsWith('.zip') || lower.endsWith('.rar') || lower.endsWith('.7z')) return 'icon-zip';
        return 'icon-file';
    }

    function getDownloadUrl(path) {
        if (!path) {
            return '#';
        }
        if (path.startsWith('/')) {
            return path;
        }
        return `${window.BASE_PATH || '/'}${path}`.replace('//', '/');
    }

    function splitFileName(name) {
        const value = String(name || '').trim();
        const idx = value.lastIndexOf('.');
        if (idx <= 0 || idx === value.length - 1) {
            return { base: value, ext: '' };
        }
        return {
            base: value.slice(0, idx),
            ext: value.slice(idx + 1)
        };
    }

    function ensureNameWithExtension(name, referenceName) {
        const typed = String(name || '').trim();
        if (!typed) {
            return '';
        }

        const typedParts = splitFileName(typed);
        if (typedParts.ext) {
            return typed;
        }

        const refParts = splitFileName(referenceName);
        if (refParts.ext) {
            return `${typed}.${refParts.ext}`;
        }

        return typed;
    }

    function canManageBin(bin) {
        return canModerate || Number(bin.created_by || 0) === currentUserId;
    }

    function canManageFile(file) {
        return canModerate || Number(file.added_by || 0) === currentUserId;
    }

    function updateStatusText(visibleBins, visibleFiles) {
        if (activeBinId) {
            statusText.textContent = `${visibleFiles} ${visibleFiles === 1 ? 'file' : 'files'}`;
            return;
        }
        statusText.textContent = `${visibleBins} ${visibleBins === 1 ? 'bin' : 'bins'}`;
    }

    function renderBody() {
        const q = searchQuery.trim().toLowerCase();
        const inBinMode = activeBinId !== null;

        shell.classList.toggle('in-bin-mode', inBinMode);
        binNavRow.classList.toggle('active', inBinMode);

        let visibleBins = 0;
        let visibleFiles = 0;

        const html = bins.map((bin) => {
            const binName = String(bin.name || 'Untitled Bin');
            const files = Array.isArray(bin.files) ? bin.files : [];
            const filteredFiles = files.filter((file) => {
                const name = String(file.file_name || '').toLowerCase();
                return !q || name.includes(q) || binName.toLowerCase().includes(q);
            });

            const showBin = inBinMode
                ? Number(bin.bin_id) === Number(activeBinId)
                : (!q || binName.toLowerCase().includes(q) || filteredFiles.length > 0);

            if (!showBin) {
                return '';
            }

            visibleBins += 1;
            if (inBinMode) {
                visibleFiles = filteredFiles.length;
            }

            const isActive = Number(bin.bin_id) === Number(activeBinId);

            const filesHtml = filteredFiles.map((file) => {
                const iconClass = getIconClassByFileName(file.file_name);
                const canManageThisFile = canManageFile(file);
                const fileMenuHtml = canManageThisFile
                    ? `<div class="fb-dropdown-item fb-edit-file-btn"><i class="uil uil-pen"></i> Rename</div>
                       <div class="fb-dropdown-item danger fb-delete-file-btn"><i class="uil uil-trash-alt"></i> Delete</div>`
                    : `<button type="button" class="fb-dropdown-item fb-report-btn"
                                     data-report-type="media"
                                     data-target-id="${Number(file.media_id || 0)}"
                            data-target-label="file \"${escapeHtml(file.file_name || 'Unnamed file')}\" in file bank">
                            <i class="uil uil-exclamation-triangle"></i> Report
                       </button>`;

                return `
                    <div class="fb-file-row"
                         data-file-id="${file.media_id}"
                         data-bin-id="${bin.bin_id}"
                         data-file-name="${escapeHtml(file.file_name || 'Unnamed file')}"
                         data-file-path="${escapeHtml(file.file_path || '')}"
                         data-added-by="${Number(file.added_by || 0)}">
                        <i class="uil uil-file-blank fb-file-icon ${iconClass}"></i>
                        <span class="fb-file-name">${escapeHtml(file.file_name || 'Unnamed file')}</span>
                        <div class="fb-file-actions" onclick="event.stopPropagation()">
                            <a class="fb-file-download-btn" href="${escapeHtml(getDownloadUrl(file.file_path))}" download title="Download">
                                <i class="uil uil-download-alt"></i>
                            </a>
                            <div class="fb-dropdown-wrap" style="position:relative;">
                                <button class="fb-dot-btn fb-file-dot" title="Options"><i class="uil uil-ellipsis-v"></i></button>
                                <div class="fb-dropdown">
                                    ${fileMenuHtml}
                                </div>
                            </div>
                        </div>
                    </div>`;
            }).join('');

            const canManageThisBin = canManageBin(bin);
            const binMenuHtml = canManageThisBin
                ? `<div class="fb-dropdown-item fb-edit-bin-btn"><i class="uil uil-pen"></i> Rename</div>
                   <div class="fb-dropdown-item danger fb-delete-bin-btn"><i class="uil uil-trash-alt"></i> Delete</div>`
                : `<button type="button" class="fb-dropdown-item fb-report-btn"
                        data-report-type="group"
                        data-target-id="${groupId}"
                        data-target-label="bin \"${escapeHtml(binName)}\" in file bank">
                        <i class="uil uil-exclamation-triangle"></i> Report
                   </button>`;

            return `
                <div class="fb-bin-item ${isActive ? 'active-bin' : ''}" data-bin-id="${bin.bin_id}">
                    <div class="fb-bin-header" data-bin="${bin.bin_id}" style="display:${isActive && inBinMode ? 'none' : 'flex'};">
                        <i class="uil uil-angle-right fb-bin-expander"></i>
                        <i class="uil uil-folder fb-bin-icon"></i>
                        <span class="fb-bin-name">${escapeHtml(binName)}</span>
                        <span class="fb-bin-meta">${files.length} ${files.length === 1 ? 'file' : 'files'}</span>
                        <div class="fb-bin-actions" onclick="event.stopPropagation()">
                            <div class="fb-dropdown-wrap" style="position:relative;">
                                <button class="fb-dot-btn fb-bin-dot" title="Options"><i class="uil uil-ellipsis-v"></i></button>
                                <div class="fb-dropdown">
                                    ${binMenuHtml}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="fb-bin-files ${isActive && inBinMode ? 'open' : ''}" id="files-${bin.bin_id}">
                        <div class="fb-bin-files-toolbar">
                            <span style="font-size:0.8rem;color:var(--color-gray);">${escapeHtml(binName)}</span>
                            <button class="btn btn-primary fb-add-file-btn" data-bin="${bin.bin_id}">
                                <i class="uil uil-plus"></i> Add File
                            </button>
                        </div>
                        ${filesHtml || '<div class="fb-empty"><i class="uil uil-folder-open"></i><p>No files yet. Add one above.</p></div>'}
                    </div>
                </div>`;
        }).join('');

        dataContainer.innerHTML = html || '<div class="fb-empty"><i class="uil uil-folder-open"></i><p>No bins found.</p></div>';
        updateStatusText(visibleBins, visibleFiles);
    }

    function getActiveBin() {
        return bins.find((bin) => Number(bin.bin_id) === Number(activeBinId)) || null;
    }

    function enterBin(binId) {
        const bin = bins.find((item) => Number(item.bin_id) === Number(binId));
        if (!bin) return;
        activeBinId = Number(bin.bin_id);
        currentBinLabel.textContent = bin.name;
        setBinBreadcrumb(bin.name);
        searchInput.value = '';
        searchQuery = '';
        renderBody();
    }

    function exitBinView() {
        activeBinId = null;
        currentBinLabel.textContent = '';
        setRootBreadcrumb();
        searchInput.value = '';
        searchQuery = '';
        renderBody();
    }

    async function loadBank() {
        try {
            const response = await api('Bin', 'getBank', { group_id: groupId });
            bins = response?.data?.bins || [];

            if (activeBinId && !getActiveBin()) {
                activeBinId = null;
                setRootBreadcrumb();
            }

            if (activeBinId) {
                const active = getActiveBin();
                if (active) {
                    setBinBreadcrumb(active.name);
                    currentBinLabel.textContent = active.name;
                }
            }

            renderBody();
        } catch (error) {
            notify(error.message || 'Failed to load file bank.', 'danger');
        }
    }

    function resetBinForm() {
        document.getElementById('binModalTitle').textContent = 'Create New Bin';
        document.getElementById('submitBinBtn').textContent = 'Create Bin';
        document.getElementById('binName').value = '';
        document.getElementById('binDescription').value = '';
        document.getElementById('binEditId').value = '';
    }

    function resetFileForm() {
        document.getElementById('fileModalTitle').textContent = 'Add File';
        document.getElementById('submitFileBtn').textContent = 'Save File';
        document.getElementById('fileName').value = '';
        document.getElementById('fileUpload').value = '';
        document.getElementById('fileEditId').value = '';
        document.getElementById('existingFileInfo').style.display = 'none';
        fileForm.dataset.currentFileName = '';
    }

    function closePostViewModal() {
        if (!postViewModal) {
            return;
        }
        postViewModal.classList.remove('active');
        postViewModal.setAttribute('aria-hidden', 'true');
        if (postViewMenuTrigger) {
            const wrap = postViewMenuTrigger.closest('.post-menu');
            if (wrap) {
                wrap.classList.remove('open');
            }
        }
    }

    function syncPostViewMenu() {
        if (!activeModalFile || !postViewRenameBtn || !postViewDeleteBtn || !postViewReportBtn) {
            return;
        }

        const canManage = !!activeModalFile.canManage;
        postViewRenameBtn.style.display = canManage ? 'flex' : 'none';
        postViewDeleteBtn.style.display = canManage ? 'flex' : 'none';
        postViewReportBtn.style.display = canManage ? 'none' : 'flex';

        postViewReportBtn.setAttribute('data-report-type', 'media');
        postViewReportBtn.setAttribute('data-target-id', String(activeModalFile.fileId || 0));
        postViewReportBtn.setAttribute('data-target-label', `file "${activeModalFile.fileName}" in file bank`);
    }

    backBtn.addEventListener('click', exitBinView);

    breadcrumb.addEventListener('click', function (e) {
        const rootTarget = e.target.closest('[data-path="root"]');
        if (rootTarget && activeBinId) {
            exitBinView();
        }
    });

    searchInput.addEventListener('input', function () {
        searchQuery = this.value;
        renderBody();
    });

    document.getElementById('addBinBtn').addEventListener('click', function () {
        resetBinForm();
        openModal('createBinModal');
    });

    document.getElementById('closeBinModal').addEventListener('click', () => closeModal('createBinModal'));
    document.getElementById('cancelBinBtn').addEventListener('click', () => closeModal('createBinModal'));
    document.getElementById('closeFileModal').addEventListener('click', () => closeModal('fileModal'));
    document.getElementById('cancelFileBtn').addEventListener('click', () => closeModal('fileModal'));
    document.getElementById('cancelDeleteBtn').addEventListener('click', () => closeModal('deleteConfirmModal'));

    createBinForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const name = document.getElementById('binName').value.trim();
        const binId = Number(document.getElementById('binEditId').value || 0);

        try {
            if (binId > 0) {
                await api('Bin', 'updateBin', { bin_id: binId, group_id: groupId, name });
                notify('Bin updated successfully.', 'success');
            } else {
                await api('Bin', 'createBin', { group_id: groupId, name });
                notify('Bin created successfully.', 'success');
            }

            closeModal('createBinModal');
            await loadBank();
        } catch (error) {
            notify(error.message || 'Could not save bin.', 'danger');
        }
    });

    fileForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const binId = Number(document.getElementById('fileBinId').value || 0);
        const mediaId = Number(document.getElementById('fileEditId').value || 0);
        const typedName = document.getElementById('fileName').value.trim();
        const fileUpload = document.getElementById('fileUpload');
        const uploadedFile = fileUpload.files[0] || null;
        const currentFileName = fileForm.dataset.currentFileName || '';
        const fileName = ensureNameWithExtension(
            typedName,
            uploadedFile ? uploadedFile.name : currentFileName
        );

        const payload = new FormData();
        payload.append('group_id', String(groupId));
        payload.append('bin_id', String(binId));
        payload.append('file_name', fileName);

        if (mediaId > 0) {
            payload.append('media_id', String(mediaId));
        }

        if (uploadedFile) {
            payload.append('file_data', uploadedFile);
        }

        try {
            if (mediaId > 0) {
                await api('Bin', 'editMedia', payload);
                notify('File updated successfully.', 'success');
            } else {
                await api('Bin', 'addMedia', payload);
                notify('File added successfully.', 'success');
            }

            closeModal('fileModal');
            await loadBank();
            if (binId) {
                enterBin(binId);
            }
        } catch (error) {
            notify(error.message || 'Could not save file.', 'danger');
        }
    });

    document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
        const type = this.dataset.target;
        const id = Number(this.dataset.id || 0);
        closeModal('deleteConfirmModal');

        if (!id) {
            return;
        }

        try {
            if (type === 'bin') {
                await api('Bin', 'deleteBin', { bin_id: id, group_id: groupId });
                notify('Bin deleted.', 'success');
                if (activeBinId === id) {
                    exitBinView();
                }
            } else {
                await api('Bin', 'removeMedia', { media_id: id });
                notify('File deleted.', 'success');
            }
            await loadBank();
        } catch (error) {
            notify(error.message || 'Delete failed.', 'danger');
        }
    });

    document.addEventListener('click', function (e) {
        const dotBtn = e.target.closest('.fb-dot-btn');
        if (dotBtn) {
            e.stopPropagation();
            closeAllDropdowns();
            const dropdown = dotBtn.closest('.fb-dropdown-wrap').querySelector('.fb-dropdown');
            dropdown.classList.add('show');
            return;
        }
        closeAllDropdowns();

        const binHeader = e.target.closest('.fb-bin-header');
        if (binHeader && !e.target.closest('.fb-bin-actions')) {
            enterBin(Number(binHeader.dataset.bin));
            return;
        }

        const editBinBtn = e.target.closest('.fb-edit-bin-btn');
        if (editBinBtn) {
            const binItem = editBinBtn.closest('.fb-bin-item');
            const binName = binItem.querySelector('.fb-bin-name').textContent;
            const binId = Number(binItem.dataset.binId);

            document.getElementById('binModalTitle').textContent = 'Edit Bin';
            document.getElementById('submitBinBtn').textContent = 'Save Changes';
            document.getElementById('binName').value = binName;
            document.getElementById('binEditId').value = String(binId);
            openModal('createBinModal');
            return;
        }

        const deleteBinBtn = e.target.closest('.fb-delete-bin-btn');
        if (deleteBinBtn) {
            const binItem = deleteBinBtn.closest('.fb-bin-item');
            const name = binItem.querySelector('.fb-bin-name').textContent;
            document.getElementById('deleteConfirmText').textContent = `Delete bin "${name}" and all its files? This cannot be undone.`;
            document.getElementById('confirmDeleteBtn').dataset.target = 'bin';
            document.getElementById('confirmDeleteBtn').dataset.id = binItem.dataset.binId;
            openModal('deleteConfirmModal');
            return;
        }

        const addFileBtn = e.target.closest('.fb-add-file-btn');
        if (addFileBtn) {
            resetFileForm();
            document.getElementById('fileBinId').value = addFileBtn.dataset.bin;
            openModal('fileModal');
            return;
        }

        const editFileBtn = e.target.closest('.fb-edit-file-btn');
        if (editFileBtn) {
            const row = editFileBtn.closest('.fb-file-row');
            const fileName = row.querySelector('.fb-file-name').textContent;
            const binItem = editFileBtn.closest('.fb-bin-item');

            document.getElementById('fileModalTitle').textContent = 'Edit File';
            document.getElementById('submitFileBtn').textContent = 'Save Changes';
            document.getElementById('fileName').value = fileName;
            document.getElementById('fileEditId').value = row.dataset.fileId;
            document.getElementById('fileBinId').value = binItem.dataset.binId;
            document.getElementById('existingFileInfo').style.display = 'block';
            fileForm.dataset.currentFileName = fileName;
            openModal('fileModal');
            return;
        }

        const deleteFileBtn = e.target.closest('.fb-delete-file-btn');
        if (deleteFileBtn) {
            const row = deleteFileBtn.closest('.fb-file-row');
            const fileName = row.querySelector('.fb-file-name').textContent;
            document.getElementById('deleteConfirmText').textContent = `Delete "${fileName}"? This cannot be undone.`;
            document.getElementById('confirmDeleteBtn').dataset.target = 'file';
            document.getElementById('confirmDeleteBtn').dataset.id = row.dataset.fileId;
            openModal('deleteConfirmModal');
            return;
        }

        const reportBtn = e.target.closest('.fb-report-btn');
        if (reportBtn) {
            closeAllDropdowns();
            return;
        }

        const fileRow = e.target.closest('.fb-file-row');
        if (fileRow && !e.target.closest('.fb-file-actions')) {
            const fileName = fileRow.dataset.fileName || fileRow.querySelector('.fb-file-name').textContent;
            const iconEl = fileRow.querySelector('.fb-file-icon');
            const canManage = canModerate || Number(fileRow.dataset.addedBy || 0) === currentUserId;
            const filePath = fileRow.dataset.filePath || '';
            const fileId = Number(fileRow.dataset.fileId || 0);
            const binId = Number(fileRow.dataset.binId || 0);

            activeModalFile = {
                fileId,
                fileName,
                filePath,
                binId,
                canManage
            };

            document.getElementById('postViewFileName').textContent = fileName;
            document.getElementById('postViewFileMeta').textContent = 'Uploaded in this bin';
            document.getElementById('postViewDate').textContent = 'Recently';
            document.getElementById('postViewUsername').textContent = 'Group Member';
            document.getElementById('postViewFileIcon').className = iconEl.className;
            document.getElementById('postViewCommentsList').innerHTML = '<div class="comments-loading">Comments API not wired yet.</div>';
            document.getElementById('postViewCommentBadge').textContent = '0';
            document.getElementById('postViewModal').classList.add('active');
            document.getElementById('postViewModal').setAttribute('aria-hidden', 'false');
            syncPostViewMenu();
        }
    });

    document.querySelector('.post-view-close').addEventListener('click', closePostViewModal);

    document.querySelector('.post-view-overlay').addEventListener('click', closePostViewModal);

    if (postViewMenuTrigger && postViewMenu) {
        postViewMenuTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const wrap = postViewMenuTrigger.closest('.post-menu');
            if (wrap) {
                wrap.classList.toggle('open');
            }
        });

        document.addEventListener('click', function () {
            const wrap = postViewMenuTrigger.closest('.post-menu');
            if (wrap) {
                wrap.classList.remove('open');
            }
        });

        postViewMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    if (postViewRenameBtn) {
        postViewRenameBtn.addEventListener('click', function () {
            if (!activeModalFile || !activeModalFile.canManage) {
                return;
            }

            document.getElementById('fileModalTitle').textContent = 'Rename File';
            document.getElementById('submitFileBtn').textContent = 'Save Changes';
            document.getElementById('fileName').value = activeModalFile.fileName;
            document.getElementById('fileEditId').value = String(activeModalFile.fileId);
            document.getElementById('fileBinId').value = String(activeModalFile.binId);
            document.getElementById('existingFileInfo').style.display = 'block';
            fileForm.dataset.currentFileName = activeModalFile.fileName;
            closePostViewModal();
            openModal('fileModal');
        });
    }

    if (postViewDeleteBtn) {
        postViewDeleteBtn.addEventListener('click', function () {
            if (!activeModalFile || !activeModalFile.canManage) {
                return;
            }

            document.getElementById('deleteConfirmText').textContent = `Delete "${activeModalFile.fileName}"? This cannot be undone.`;
            document.getElementById('confirmDeleteBtn').dataset.target = 'file';
            document.getElementById('confirmDeleteBtn').dataset.id = String(activeModalFile.fileId);
            closePostViewModal();
            openModal('deleteConfirmModal');
        });
    }

    if (postViewReportBtn) {
        postViewReportBtn.addEventListener('click', function () {
            closePostViewModal();
        });
    }

    document.getElementById('postViewCommentForm').addEventListener('submit', function (e) {
        e.preventDefault();
        notify('Comment API is not wired yet.', 'info');
    });

    document.getElementById('postViewDownloadBtn').addEventListener('click', function () {
        const name = document.getElementById('postViewFileName').textContent;
        const downloadUrl = activeModalFile ? getDownloadUrl(activeModalFile.filePath) : '#';
        if (downloadUrl && downloadUrl !== '#') {
            window.open(downloadUrl, '_blank');
            notify(`Downloading "${name}"...`, 'info');
            return;
        }
        notify('Download link unavailable for this file.', 'danger');
    });

    if (!groupId) {
        notify('Missing group ID. File bank cannot load.', 'danger');
        dataContainer.innerHTML = '<div class="fb-empty"><i class="uil uil-folder-open"></i><p>Invalid group context.</p></div>';
        return;
    }

    setRootBreadcrumb();
    loadBank();
})();
