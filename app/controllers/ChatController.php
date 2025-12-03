<?php
require_once __DIR__ . '/../models/MessageModel.php';
require_once __DIR__ . '/../models/FriendModel.php';

class ChatController {
    private MessageModel $messageModel;
    private FriendModel $friendModel;

    public function __construct() {
        $this->messageModel = new MessageModel();
        $this->friendModel = new FriendModel();
    }

    public function listConversations(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('GET');

        $conversations = $this->messageModel->getUserConversations($userId);
        $this->jsonResponse(['data' => $conversations]);
    }

    public function fetchMessages(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('GET');

        $conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);
        if (!$conversationId) {
            $this->errorResponse('conversation_id is required', 422);
        }

        $afterId = filter_input(INPUT_GET, 'after_id', FILTER_VALIDATE_INT);
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50;

        if (!$this->messageModel->userCanAccessConversation($conversationId, $userId)) {
            $this->errorResponse('Conversation not found', 404);
        }

        $messages = $this->messageModel->getMessages($conversationId, $userId, $afterId ?: null, min($limit, 200));
        $this->messageModel->markConversationRead($conversationId, $userId);

        $this->jsonResponse(['data' => $messages]);
    }

    public function sendMessage(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('POST');

        $payloadSource = $this->isJsonRequest() ? $this->getJsonBody() : $_POST;
        $conversationId = isset($payloadSource['conversation_id']) ? (int)$payloadSource['conversation_id'] : null;
        $content = isset($payloadSource['content']) ? trim((string)$payloadSource['content']) : '';

        if (!$conversationId) {
            $this->errorResponse('conversation_id is required', 422);
        }

        $hasAttachment = isset($_FILES['attachment'])
            && is_array($_FILES['attachment'])
            && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($content === '' && !$hasAttachment) {
            $this->errorResponse('Message content or attachment required', 422);
        }

        $fileMeta = [];
        $messageType = 'text';

        if ($hasAttachment) {
            try {
                $processed = $this->handleAttachmentUpload($_FILES['attachment']);
                $fileMeta = [
                    'file_url' => $processed['file_url'],
                    'file_name' => $processed['file_name'],
                    'file_size' => $processed['file_size'],
                ];
                $messageType = $processed['message_type'];
            } catch (RuntimeException $e) {
                $this->errorResponse($e->getMessage(), 422);
            }
        }

        try {
            $message = $this->messageModel->createMessage(
                $conversationId,
                $userId,
                $content,
                $messageType,
                $fileMeta
            );
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), 404);
        }

        $this->jsonResponse(['data' => $message], 201);
    }

    public function markRead(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('POST');

        $payload = $this->getJsonBody();
        $conversationId = isset($payload['conversation_id']) ? (int)$payload['conversation_id'] : null;

        if (!$conversationId) {
            $this->errorResponse('conversation_id is required', 422);
        }

        if (!$this->messageModel->userCanAccessConversation($conversationId, $userId)) {
            $this->errorResponse('Conversation not found', 404);
        }

        $this->messageModel->markConversationRead($conversationId, $userId);
        $this->jsonResponse(['status' => 'ok']);
    }

    public function searchFriends(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('GET');

        $rawTerm = filter_input(INPUT_GET, 'term', FILTER_UNSAFE_RAW);
        $term = trim((string)$rawTerm);
        if (strlen($term) < 2) {
            $this->jsonResponse(['data' => []]);
        }

        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 8;
        $results = $this->friendModel->searchAcceptedFriends($userId, $term, min($limit, 20));
        $this->jsonResponse(['data' => $results]);
    }

    public function startConversation(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('POST');

        $payload = $this->getJsonBody();
        $friendUserId = isset($payload['friend_user_id']) ? (int)$payload['friend_user_id'] : 0;

        if ($friendUserId <= 0) {
            $this->errorResponse('friend_user_id is required', 422);
        }

        if ($friendUserId === $userId) {
            $this->errorResponse('You cannot start a conversation with yourself', 422);
        }

        $friendship = $this->friendModel->getFriendship($userId, $friendUserId);
        if (!$friendship || $friendship['status'] !== 'accepted') {
            $this->errorResponse('You can only message confirmed friends', 403);
        }

        try {
            $conversation = $this->messageModel->ensureDirectConversation($userId, $friendUserId);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), 400);
        }

        $this->jsonResponse(['data' => $conversation], 201);
    }

    public function fetchSharedMedia(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('GET');

        $conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);
        if (!$conversationId) {
            $this->errorResponse('conversation_id is required', 422);
        }

        if (!$this->messageModel->userCanAccessConversation($conversationId, $userId)) {
            $this->errorResponse('Conversation not found', 404);
        }

        $sharedMedia = $this->messageModel->getSharedMedia($conversationId);
        $this->jsonResponse(['data' => $sharedMedia]);
    }

    public function fetchFileStructure(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('GET');

        $conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);
        $folderId = filter_input(INPUT_GET, 'folder_id', FILTER_UNSAFE_RAW);

        if (!$conversationId) {
            $this->errorResponse('conversation_id is required', 422);
        }

        if (!$this->messageModel->userCanAccessConversation($conversationId, $userId)) {
            $this->errorResponse('Conversation not found', 404);
        }

        $structure = $this->messageModel->getFileStructure($conversationId, $folderId);
        $this->jsonResponse(['data' => $structure]);
    }

    public function createFolder(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('POST');

        $payload = $this->getJsonBody();
        $conversationId = isset($payload['conversation_id']) ? (int)$payload['conversation_id'] : 0;
        $parentFolderId = isset($payload['parent_folder_id']) ? ($payload['parent_folder_id'] === 'root' ? null : (int)$payload['parent_folder_id']) : null;
        $folderName = isset($payload['folder_name']) ? trim((string)$payload['folder_name']) : '';

        if (!$conversationId || !$folderName) {
            $this->errorResponse('conversation_id and folder_name are required', 422);
        }

        if (!$this->messageModel->userCanAccessConversation($conversationId, $userId)) {
            $this->errorResponse('Conversation not found', 404);
        }

        $folderId = $this->messageModel->createChatFolder($conversationId, $parentFolderId, $folderName, $userId);
        $this->jsonResponse(['data' => ['folder_id' => $folderId]], 201);
    }

    public function uploadFile(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('POST');

        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $folderId = isset($_POST['folder_id']) ? ($_POST['folder_id'] === 'root' ? null : (int)$_POST['folder_id']) : null;

        if (!$conversationId) {
            $this->errorResponse('conversation_id is required', 422);
        }

        if (!$this->messageModel->userCanAccessConversation($conversationId, $userId)) {
            $this->errorResponse('Conversation not found', 404);
        }

        if (!isset($_FILES['file'])) {
            $this->errorResponse('file is required', 422);
        }

        try {
            $uploadedFile = $this->handleAttachmentUpload($_FILES['file']);
            $fileId = $this->messageModel->saveChatFile(
                $conversationId,
                $folderId,
                $uploadedFile['file_name'],
                $uploadedFile['file_url'],
                $uploadedFile['file_size'],
                $userId
            );
            $this->jsonResponse(['data' => ['file_id' => $fileId]], 201);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function deleteFolder(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('POST');

        $payload = $this->getJsonBody();
        $folderId = isset($payload['folder_id']) ? (int)$payload['folder_id'] : 0;

        if (!$folderId) {
            $this->errorResponse('folder_id is required', 422);
        }

        $this->messageModel->deleteChatFolder($folderId, $userId);
        $this->jsonResponse(['status' => 'ok']);
    }

    public function deleteFile(): void {
        $userId = $this->requireAuth();
        $this->enforceMethod('POST');

        $payload = $this->getJsonBody();
        $fileId = isset($payload['file_id']) ? (int)$payload['file_id'] : 0;

        if (!$fileId) {
            $this->errorResponse('file_id is required', 422);
        }

        $this->messageModel->deleteChatFile($fileId, $userId);
        $this->jsonResponse(['status' => 'ok']);
    }

    private function handleAttachmentUpload(array $file): array {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('No attachment detected.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->translateUploadError($file['error']));
        }

        $maxSize = 15 * 1024 * 1024; // 15 MB
        if ($file['size'] > $maxSize) {
            throw new RuntimeException('Attachments must be smaller than 15 MB.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }
        $mime = $mime ?: ($file['type'] ?? 'application/octet-stream');
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $messageType = $this->determineAttachmentType($mime, $extension);

        $directory = $this->getAttachmentDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to prepare uploads directory.');
        }

        $filename = sprintf('%s_%s', date('YmdHis'), bin2hex(random_bytes(6)));
        if ($extension) {
            $filename .= '.' . $extension;
        }

        $destination = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to store attachment.');
        }

        return [
            'file_name' => $file['name'],
            'file_size' => (int)$file['size'],
            'file_url' => 'uploads/chat_attachments/' . $filename,
            'message_type' => $messageType,
        ];
    }

    private function determineAttachmentType(string $mime, string $extension): string {
        $mime = strtolower($mime);
        $extension = strtolower($extension);

        if (strpos($mime, 'image/') === 0) {
            return 'image';
        }

        if (strpos($mime, 'video/') === 0) {
            return 'video';
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
        if (in_array($extension, $allowedExtensions, true)) {
            return 'file';
        }

        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip',
            'application/x-rar-compressed',
        ];

        if (in_array($mime, $allowedMimes, true)) {
            return 'file';
        }

        throw new RuntimeException('Unsupported attachment type.');
    }

    private function getAttachmentDirectory(): string {
        return __DIR__ . '/../../public/uploads/chat_attachments';
    }

    private function translateUploadError(int $error): string {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'The selected file is too large.';
            case UPLOAD_ERR_PARTIAL:
                return 'The file upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Server configuration error: missing temporary directory.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Unable to write the uploaded file.';
            case UPLOAD_ERR_EXTENSION:
                return 'A server extension stopped the upload.';
            default:
                return 'Unknown upload error occurred.';
        }
    }

    private function isJsonRequest(): bool {
        $contentType = $_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
        return stripos($contentType, 'application/json') === 0;
    }

    private function requireAuth(): int {
        if (!isset($_SESSION['user_id'])) {
            $this->errorResponse('Unauthorized', 401);
        }

        return (int)$_SESSION['user_id'];
    }

    private function enforceMethod(string $method): void {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== strtoupper($method)) {
            $this->errorResponse('Method not allowed', 405);
        }
    }

    private function getJsonBody(): array {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return is_array($decoded) ? $decoded : [];
            }
        }

        return $_POST ?: [];
    }

    private function jsonResponse(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    private function errorResponse(string $message, int $status = 400): void {
        $this->jsonResponse(['error' => $message], $status);
    }
}
