<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/MediaHelper.php';

$friendRequests = $friendRequests ?? [];
$friendRequestCount = count($friendRequests);

?>
<div class="friend-requests">
    <h4>Friend Requests <span class="badge">(<?php echo $friendRequestCount; ?>)</span></h4>

    <div class="friend-requests-empty" style="<?php echo $friendRequestCount === 0 ? '' : 'display:none;'; ?>">
        <p>No pending friend requests</p>
    </div>

    <?php foreach ($friendRequests as $request): ?>
        <?php
            $displayName = trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? ''));
            if ($displayName === '') {
                $displayName = $request['username'] ?? 'Unknown User';
            }
            $usernameHandle = !empty($request['username']) ? '@' . $request['username'] : '';
            $requestedAt = !empty($request['requested_at']) ? date('M j, Y', strtotime($request['requested_at'])) : '';
            $friendshipId = (int)($request['friendship_id'] ?? 0);
            $avatarUrl = MediaHelper::resolveMediaPath($request['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg');
        ?>
    <div
        class="request"
        data-friendship-id="<?php echo $friendshipId; ?>"
        data-requester-id="<?php echo (int)($request['requester_id'] ?? 0); ?>"
        data-requester-name="<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>"
        data-requester-handle="<?php echo htmlspecialchars($usernameHandle, ENT_QUOTES); ?>"
        data-requester-avatar="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES); ?>"
    >
            <div class="info">
                <div class="profile-picture">
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($displayName); ?>">
                </div>
                <div>
                    <h5><?php echo htmlspecialchars($displayName); ?></h5>
                    <p>
                        <?php if ($usernameHandle !== ''): ?>
                            <span><?php echo htmlspecialchars($usernameHandle); ?></span>
                        <?php endif; ?>
                        <?php if ($requestedAt !== ''): ?>
                            <span><?php echo $usernameHandle !== '' ? ' • ' : ''; ?>Requested <?php echo htmlspecialchars($requestedAt); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="action">
                <button class="btn btn-primary accept-btn" type="button">Accept</button>
                <button class="btn decline-btn" type="button">Decline</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>
