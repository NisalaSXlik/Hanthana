<?php
    class MediaHelper {
        public static function resolveMediaPath(?string $dbPath, string $defaultPath): string {
            if (empty($dbPath)) {
                return BASE_PATH . $defaultPath;
            }
            if (filter_var($dbPath, FILTER_VALIDATE_URL)) {
                return $dbPath;
            }
            return BASE_PATH . ltrim($dbPath, '/');
        }
}
?>