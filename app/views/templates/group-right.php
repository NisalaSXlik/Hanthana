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
    $groupSettingsHref = rtrim(BASE_PATH, '/') . '/index.php?controller=GroupSettings&action=index';

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
    }

    // $activeGroupIdForReport = 0;
    // if (isset($groupId) && (int)$groupId > 0) {
    //     $activeGroupIdForReport = (int)$groupId;
    // } elseif (!empty($manageQuery['group_id'])) {
    //     $activeGroupIdForReport = (int)$manageQuery['group_id'];
    // }
?>

<div class="right">
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
            <?php endif; ?>
        </div>
    </div>
</div>