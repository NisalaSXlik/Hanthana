<?php
    $groupHomeQuery = [];
    $fileBankQuery = [];
    $channelQuery = [];
    $membersQuery = [];
    $manageQuery = [];

    if (isset($groupId) && (int)$groupId > 0) {
        $groupHomeQuery['group_id'] = (int)$groupId;
        $fileBankQuery['group_id'] = (int)$groupId;
        $channelQuery['group_id'] = (int)$groupId;
        $membersQuery['group_id'] = (int)$groupId;
        $manageQuery['group_id'] = (int)$groupId;
    } elseif (isset($_GET['group_id']) && (int)$_GET['group_id'] > 0) {
        $groupHomeQuery['group_id'] = (int)$_GET['group_id'];
        $fileBankQuery['group_id'] = (int)$_GET['group_id'];
        $channelQuery['group_id'] = (int)$_GET['group_id'];
        $membersQuery['group_id'] = (int)$_GET['group_id'];
        $manageQuery['group_id'] = (int)$_GET['group_id'];
    } elseif (isset($_SESSION['current_group_id']) && (int)$_SESSION['current_group_id'] > 0) {
        $groupHomeQuery['group_id'] = (int)$_SESSION['current_group_id'];
        $fileBankQuery['group_id'] = (int)$_SESSION['current_group_id'];
        $channelQuery['group_id'] = (int)$_SESSION['current_group_id'];
        $membersQuery['group_id'] = (int)$_SESSION['current_group_id'];
        $manageQuery['group_id'] = (int)$_SESSION['current_group_id'];
    } elseif (isset($_SESSION['user_id'])) {
        $fileBankQuery['user_id'] = (int)$_SESSION['user_id'];
    }

    $groupHomeHref = rtrim(BASE_PATH, '/') . '/index.php?controller=Group&action=index';
    $fileBankHref = rtrim(BASE_PATH, '/') . '/index.php?controller=FileBank&action=index';
    $channelHref = rtrim(BASE_PATH, '/') . '/index.php?controller=ChannelPage&action=index';
    $membersHref = rtrim(BASE_PATH, '/') . '/index.php?controller=Group&action=members';
    $groupReportsHref = rtrim(BASE_PATH, '/') . '/index.php?controller=GroupReports&action=index';
    $manageHref = rtrim(BASE_PATH, '/') . '/index.php?controller=Group&action=manage';
    $governanceHref = rtrim(BASE_PATH, '/') . '/index.php?controller=Group&action=governance';
    $groupSettingsHref = rtrim(BASE_PATH, '/') . '/index.php?controller=GroupSettings&action=index';

    $groupPrivacy = strtolower(trim((string)($group['privacy_status'] ?? 'public')));

    if (!empty($groupHomeQuery)) {
        $groupHomeHref .= '&' . http_build_query($groupHomeQuery);
    }
    if (!empty($fileBankQuery)) {
        $fileBankHref .= '&' . http_build_query($fileBankQuery);
    }
    if (!empty($channelQuery)) {
        $channelHref .= '&' . http_build_query($channelQuery);
    }
    if (!empty($membersQuery)) {
        $membersHref .= '&' . http_build_query($membersQuery);
    }
    if (!empty($manageQuery)) {
        $groupReportsHref .= '&' . http_build_query($manageQuery);
    }
    if (!empty($manageQuery)) {
        $manageHref .= '&' . http_build_query($manageQuery);
        $groupSettingsHref .= '&' . http_build_query($manageQuery);
        $governanceHref .= '&' . http_build_query($manageQuery);
    }
?>

<div class="right">
    <?php if ($isJoined): ?>
        <div class="group-details">
            <h4>Group Navigation</h4>
            <div class="detail-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; margin-top: 0.5rem;">
                <a href="<?php echo $groupHomeHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                    <i class="uil uil-home" style="font-size: 1.4rem;"></i>
                    <span style="font-size: 0.82rem; font-weight: 500;">Home</span>
                </a>
                <a href="<?php echo $fileBankHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                    <i class="uil uil-folder" style="font-size: 1.4rem;"></i>
                    <span style="font-size: 0.82rem; font-weight: 500;">File Bank</span>
                </a>
                <a href="<?php echo $channelHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                    <i class="uil uil-channel" style="font-size: 1.4rem;"></i>
                    <span style="font-size: 0.82rem; font-weight: 500;">Channels</span>
                </a>
                <a href="<?php echo $membersHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                    <i class="uil uil-users-alt" style="font-size: 1.4rem;"></i>
                    <span style="font-size: 0.82rem; font-weight: 500;">Members</span>
                </a>

                <?php if (!empty($isAdmin)): ?>
                <a href="<?php echo $groupReportsHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                    <i class="uil uil-exclamation-circle" style="font-size: 1.4rem;"></i>
                    <span style="font-size: 0.82rem; font-weight: 500;">Moderation</span>
                </a>
                <a href="<?php echo $manageHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                    <i class="uil uil-sliders-v" style="font-size: 1.4rem;"></i>
                    <span style="font-size: 0.82rem; font-weight: 500;">Requests</span>
                </a>
                <a href="<?php echo $groupSettingsHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                    <i class="uil uil-cog" style="font-size: 1.4rem;"></i>
                    <span style="font-size: 0.82rem; font-weight: 500;">Settings</span>
                </a>
                <a href="<?php echo $governanceHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                    <i class="uil uil-balance-scale" style="font-size: 1.4rem;"></i>
                    <span style="font-size: 0.82rem; font-weight: 500;">Governance</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="about-grid">
            <div class="about-card about-overview">
                <h3>About This Group</h3>
                <p><?php echo htmlspecialchars($group['description'] ?? 'This group does not have a description yet.'); ?></p>
            
                <ul class="about-detail-list">
                    <li>
                        <i class="uil uil-shield-check"></i>
                        <span>Privacy</span>
                        <strong><?php echo ucfirst(htmlspecialchars($group['privacy_status'] ?? 'public')); ?></strong>
                    </li>
                    <li>
                        <i class="uil uil-calendar-alt"></i>
                        <span>Created</span>
                        <strong><?php echo htmlspecialchars(date('F Y', strtotime($group['created_at']))); ?></strong>
                    </li>
                    <li>
                        <i class="uil uil-users-alt"></i>
                        <span>Members</span>
                        <strong><?php echo (int)($group['member_count'] ?? 0); ?></strong>
                    </li>
                    <li>
                        <i class="uil uil-compass"></i>
                        <span>Focus</span>
                        <strong><?php echo htmlspecialchars($group['focus'] ?? 'General'); ?></strong>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>