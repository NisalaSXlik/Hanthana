<?php

class BinController extends BaseController
{
    private BinModel $binModel;
    private GroupModel $groupModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->binModel = new BinModel();
        $this->groupModel = new GroupModel();
    }

    public function getBank()
    {
        $payload = $this->requestData();
        $groupId = isset($payload['group_id']) ? (int) $payload['group_id'] : 0;

        if ($groupId <= 0) {
            return $this->response(['status' => 'error', 'errors' => ['Valid group ID is required.']], 400);
        }

        $bins = $this->binModel->getBinsWithFiles($groupId);
        return $this->response(['status' => 'success', 'data' => ['bins' => $bins]]);
    }

    public function createBin()
    {
        $payload = $this->requestData();
        $errors = $this->validateBinCreate($payload);

        if (!empty($errors)) {
            return $this->response(['status' => 'error', 'errors' => $errors], 400);
        }

        $groupId = (int)($payload['group_id'] ?? 0);
        $userId = (int)$_SESSION['user_id'];
        $canBypassApproval = $this->canManageGroupContent($groupId);
        if (!$canBypassApproval) {
            $queued = $this->groupModel->queueBinCreationRequest($groupId, $userId, [
                'name' => trim((string)$payload['name'])
            ]);
            if ($queued) {
                return $this->response([
                    'status' => 'success',
                    'queued' => true,
                    'message' => 'Bin request submitted for approval.'
                ]);
            }

            return $this->response(['status' => 'error', 'errors' => ['Failed to submit bin request.']], 500);
        }

        $created = $this->binModel->createBin($payload, $userId);
        if (!$created) {
            return $this->response(['status' => 'error', 'errors' => ['Failed to create bin.']], 500);
        }

        return $this->response([
            'status' => 'success',
            'message' => 'Bin created successfully.',
            'data' => ['bin' => $created]
        ]);
    }

    public function updateBin()
    {
        $payload = $this->requestData();
        $binId = isset($payload['bin_id']) ? (int) $payload['bin_id'] : 0;
        $errors = $this->validateBinUpdate($payload, $binId);

        if (!empty($errors)) {
            return $this->response(['status' => 'error', 'errors' => $errors], 400);
        }

        $bin = $this->binModel->getBinById($binId);
        if (!$bin || (int)($bin['group_id'] ?? 0) !== (int)$payload['group_id']) {
            return $this->response(['status' => 'error', 'errors' => ['Bin not found.']], 404);
        }

        if (!$this->canManageGroupContent((int)$payload['group_id'], (int)($bin['created_by'] ?? 0))) {
            return $this->response(['status' => 'error', 'errors' => ['You are not allowed to rename this bin.']], 403);
        }

        if (!$this->binModel->updateBin($payload, $binId)) {
            return $this->response(['status' => 'error', 'errors' => ['Failed to update bin.']], 500);
        }

        return $this->response(['status' => 'success', 'message' => 'Bin updated successfully.']);
    }

    public function deleteBin()
    {
        $payload = $this->requestData();
        $binId = isset($payload['bin_id']) ? (int) $payload['bin_id'] : 0;
        $groupId = isset($payload['group_id']) ? (int) $payload['group_id'] : 0;

        if ($binId <= 0 || $groupId <= 0) {
            return $this->response(['status' => 'error', 'errors' => ['Bin ID and Group ID are required.']], 400);
        }

        $bin = $this->binModel->getBinById($binId);
        if (!$bin || (int)($bin['group_id'] ?? 0) !== $groupId) {
            return $this->response(['status' => 'error', 'errors' => ['Bin not found.']], 404);
        }

        if (!$this->canManageGroupContent($groupId, (int)($bin['created_by'] ?? 0))) {
            return $this->response(['status' => 'error', 'errors' => ['You are not allowed to delete this bin.']], 403);
        }

        if (!$this->binModel->deleteBin($binId, $groupId)) {
            return $this->response(['status' => 'error', 'errors' => ['Failed to delete bin.']], 500);
        }

        return $this->response(['status' => 'success', 'message' => 'Bin deleted successfully.']);
    }

    public function addMedia()
    {
        $payload = $this->requestData();
        $payload['file_name'] = $this->normalizeFileNameWithExtension(
            (string)($payload['file_name'] ?? ''),
            (string)($_FILES['file_data']['name'] ?? '')
        );
        $errors = $this->validateMediaPayload($payload, null, true);

        if (!empty($errors)) {
            return $this->response(['status' => 'error', 'errors' => $errors], 400);
        }

        $fileInfo = $this->handleUpload($_FILES['file_data']);
        if (isset($fileInfo['errors'])) {
            return $this->response(['status' => 'error', 'errors' => $fileInfo['errors']], 400);
        }

        $payload = array_merge($payload, $fileInfo);
        $groupId = (int)($payload['group_id'] ?? 0);
        $userId = (int)$_SESSION['user_id'];
        $canBypassApproval = $this->canManageGroupContent($groupId);

        if (!$canBypassApproval) {
            $queued = $this->groupModel->queueBinMediaAddRequest(
                $groupId,
                $userId,
                (int)($payload['bin_id'] ?? 0),
                [
                    'group_id' => $groupId,
                    'bin_id' => (int)($payload['bin_id'] ?? 0),
                    'file_name' => (string)($payload['file_name'] ?? ''),
                    'file_path' => (string)($payload['file_path'] ?? ''),
                    'file_size' => (int)($payload['file_size'] ?? 0),
                    'media_file_type' => (string)($payload['media_file_type'] ?? 'other'),
                    'bin_file_type' => (string)($payload['bin_file_type'] ?? 'other')
                ]
            );

            if ($queued) {
                return $this->response([
                    'status' => 'success',
                    'queued' => true,
                    'message' => 'File request submitted for approval.'
                ]);
            }

            return $this->response(['status' => 'error', 'errors' => ['Failed to submit file request.']], 500);
        }

        $created = $this->binModel->addMedia($payload, $userId);

        if (!$created) {
            return $this->response(['status' => 'error', 'errors' => ['Failed to add file.']], 500);
        }

        return $this->response([
            'status' => 'success',
            'message' => 'File added successfully.',
            'data' => ['file' => $created]
        ]);
    }

    public function editMedia()
    {
        $payload = $this->requestData();
        $mediaId = isset($payload['media_id']) ? (int) $payload['media_id'] : 0;

        if ($mediaId <= 0) {
            return $this->response(['status' => 'error', 'errors' => ['Valid media ID is required.']], 400);
        }

        $existing = $this->binModel->getMediaById($mediaId);
        if (!$existing) {
            return $this->response(['status' => 'error', 'errors' => ['File not found.']], 404);
        }

        $bin = $this->binModel->getBinById((int)($existing['bin_id'] ?? 0));
        if (!$bin) {
            return $this->response(['status' => 'error', 'errors' => ['Bin not found.']], 404);
        }

        if (!$this->canManageGroupContent((int)($bin['group_id'] ?? 0), (int)($existing['added_by'] ?? 0))) {
            return $this->response(['status' => 'error', 'errors' => ['You are not allowed to rename this file.']], 403);
        }

        $referenceName = (string)($_FILES['file_data']['name'] ?? $existing['file_name'] ?? '');
        $payload['file_name'] = $this->normalizeFileNameWithExtension((string)($payload['file_name'] ?? ''), $referenceName);
        $payload['group_id'] = (int)($bin['group_id'] ?? 0);
        $payload['bin_id'] = (int)($existing['bin_id'] ?? 0);

        $errors = $this->validateMediaPayload($payload, $mediaId, false);
        if (!empty($errors)) {
            return $this->response(['status' => 'error', 'errors' => $errors], 400);
        }

        if (isset($_FILES['file_data']) && isset($_FILES['file_data']['tmp_name']) && is_uploaded_file($_FILES['file_data']['tmp_name'])) {
            $fileInfo = $this->handleUpload($_FILES['file_data']);
            if (isset($fileInfo['errors'])) {
                return $this->response(['status' => 'error', 'errors' => $fileInfo['errors']], 400);
            }

            $payload = array_merge($payload, $fileInfo);
            $this->removeStoredFile($existing['file_path'] ?? null);
        } else {
            $payload['file_path'] = $existing['file_path'];
            $payload['file_size'] = $existing['file_size'];
            $types = $this->resolveFileTypesByName((string) ($payload['file_name'] ?? $existing['file_name']));
            $payload = array_merge($payload, $types);
        }

        if (!$this->binModel->editMedia($payload, $mediaId)) {
            return $this->response(['status' => 'error', 'errors' => ['Failed to update file.']], 500);
        }

        return $this->response(['status' => 'success', 'message' => 'File updated successfully.']);
    }

    public function removeMedia()
    {
        $payload = $this->requestData();
        $mediaId = isset($payload['media_id']) ? (int) $payload['media_id'] : 0;

        if ($mediaId <= 0) {
            return $this->response(['status' => 'error', 'errors' => ['Valid media ID is required.']], 400);
        }

        $existing = $this->binModel->getMediaById($mediaId);
        if (!$existing) {
            return $this->response(['status' => 'error', 'errors' => ['File not found.']], 404);
        }

        $bin = $this->binModel->getBinById((int)($existing['bin_id'] ?? 0));
        if (!$bin) {
            return $this->response(['status' => 'error', 'errors' => ['Bin not found.']], 404);
        }

        if (!$this->canManageGroupContent((int)($bin['group_id'] ?? 0), (int)($existing['added_by'] ?? 0))) {
            return $this->response(['status' => 'error', 'errors' => ['You are not allowed to delete this file.']], 403);
        }

        if (!$this->binModel->removeMedia($mediaId)) {
            return $this->response(['status' => 'error', 'errors' => ['Failed to remove file.']], 500);
        }

        $this->removeStoredFile($existing['file_path'] ?? null);
        return $this->response(['status' => 'success', 'message' => 'File removed successfully.']);
    }

    private function requestData(): array
    {
        if (!empty($this->data) && is_array($this->data)) {
            return $this->data;
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        return [];
    }

    private function validateBinCreate(array $payload): array
    {
        $errors = [];

        $groupId = isset($payload['group_id']) ? (int) $payload['group_id'] : 0;
        $name = trim((string) ($payload['name'] ?? ''));

        if ($groupId <= 0) {
            $errors[] = 'Valid group ID is required.';
        }
        if ($name === '') {
            $errors[] = 'Bin name is required.';
        }

        if ($groupId > 0 && $name !== '' && $this->binModel->checkUniqueBinName($groupId, $name)) {
            $errors[] = 'Bin name must be unique within this group.';
        }

        return $errors;
    }

    private function validateBinUpdate(array $payload, int $binId): array
    {
        $errors = [];
        $groupId = isset($payload['group_id']) ? (int) $payload['group_id'] : 0;
        $name = trim((string) ($payload['name'] ?? ''));

        if ($binId <= 0) {
            $errors[] = 'Valid bin ID is required.';
        }
        if ($groupId <= 0) {
            $errors[] = 'Valid group ID is required.';
        }
        if ($name === '') {
            $errors[] = 'Bin name is required.';
        }

        if ($binId > 0 && $groupId > 0 && $name !== '' && $this->binModel->checkUniqueBinName($groupId, $name, $binId)) {
            $errors[] = 'Bin name must be unique within this group.';
        }

        return $errors;
    }

    private function validateMediaPayload(array $payload, ?int $mediaId, bool $requireUpload): array
    {
        $errors = [];
        $groupId = isset($payload['group_id']) ? (int) $payload['group_id'] : 0;
        $binId = isset($payload['bin_id']) ? (int) $payload['bin_id'] : 0;
        $fileName = trim((string) ($payload['file_name'] ?? ''));

        if ($groupId <= 0) {
            $errors[] = 'Valid group ID is required.';
        }
        if ($binId <= 0) {
            $errors[] = 'Valid bin ID is required.';
        }
        if ($fileName === '') {
            $errors[] = 'File name is required.';
        }

        if ($requireUpload) {
            if (!isset($_FILES['file_data']) || !isset($_FILES['file_data']['tmp_name']) || !is_uploaded_file($_FILES['file_data']['tmp_name'])) {
                $errors[] = 'Please upload a file.';
            }
        }

        if ($binId > 0 && $fileName !== '' && $this->binModel->checkUniqueFileName($binId, $fileName, $mediaId)) {
            $errors[] = 'A file with this name already exists in the selected bin.';
        }

        return $errors;
    }

    private function handleUpload(array $file): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['errors' => ['File upload failed.']];
        }

        $projectRoot = dirname(__DIR__, 2);
        $targetDir = $projectRoot . '/public/uploads/filebank';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return ['errors' => ['Could not prepare upload directory.']];
        }

        $originalName = (string) ($file['name'] ?? 'upload.bin');
        $cleanOriginalName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $cleanOriginalName = $cleanOriginalName ?: 'upload.bin';

        $storedName = uniqid('fb_', true) . '_' . $cleanOriginalName;
        $absolutePath = $targetDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            return ['errors' => ['Could not save uploaded file.']];
        }

        $relativePath = 'uploads/filebank/' . $storedName;
        $types = $this->resolveFileTypesByName($originalName);

        return array_merge($types, [
            'file_url' => $relativePath,
            'file_path' => $relativePath,
            'file_size' => (int) ($file['size'] ?? 0),
        ]);
    }

    private function resolveFileTypesByName(string $name): array
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $mediaType = 'other';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $mediaType = 'image';
        } elseif (in_array($ext, ['mp4', 'webm', 'mov', 'avi'], true)) {
            $mediaType = 'video';
        } elseif ($ext === 'pdf') {
            $mediaType = 'pdf';
        } elseif (in_array($ext, ['doc', 'docx', 'txt', 'rtf', 'odt', 'xlsx', 'xls', 'ppt', 'pptx', 'zip'], true)) {
            $mediaType = 'doc';
        }

        $binType = 'other';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $binType = 'image';
        } elseif (in_array($ext, ['mp4', 'webm', 'mov', 'avi'], true)) {
            $binType = 'video';
        } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'xlsx', 'xls', 'ppt', 'pptx', 'zip'], true)) {
            $binType = 'doc';
        }

        return [
            'media_file_type' => $mediaType,
            'bin_file_type' => $binType,
        ];
    }

    private function removeStoredFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $projectRoot = dirname(__DIR__, 2);
        $absolutePath = $projectRoot . '/public/' . ltrim($relativePath, '/');
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function normalizeFileNameWithExtension(string $inputName, string $referenceName): string
    {
        $cleanName = trim($inputName);
        $cleanReference = trim($referenceName);

        $base = $cleanName !== '' ? $cleanName : ($cleanReference !== '' ? pathinfo($cleanReference, PATHINFO_FILENAME) : 'file');
        $base = trim($base);
        if ($base === '') {
            $base = 'file';
        }

        $currentExt = strtolower((string)pathinfo($base, PATHINFO_EXTENSION));
        if ($currentExt !== '') {
            return $base;
        }

        $referenceExt = strtolower((string)pathinfo($cleanReference, PATHINFO_EXTENSION));
        if ($referenceExt !== '') {
            return $base . '.' . $referenceExt;
        }

        return $base;
    }

    private function canManageGroupContent(int $groupId, ?int $ownerUserId = null): bool
    {
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($currentUserId <= 0 || $groupId <= 0) {
            return false;
        }

        $group = $this->groupModel->getById($groupId);
        if (!$group) {
            return false;
        }

        if ($this->groupModel->isGroupAdmin($groupId, $currentUserId)) {
            return true;
        }

        return $ownerUserId !== null && (int)$ownerUserId === $currentUserId;
    }
}
