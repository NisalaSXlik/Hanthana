<?php
    $fileBankQuery = [];

    if (isset($groupId) && (int)$groupId > 0) {
        $fileBankQuery['group_id'] = (int)$groupId;
    } elseif (isset($_GET['group_id']) && (int)$_GET['group_id'] > 0) {
        $fileBankQuery['group_id'] = (int)$_GET['group_id'];
    } elseif (isset($_SESSION['current_group_id']) && (int)$_SESSION['current_group_id'] > 0) {
        $fileBankQuery['group_id'] = (int)$_SESSION['current_group_id'];
    } elseif (isset($_SESSION['user_id'])) {
        $fileBankQuery['user_id'] = (int)$_SESSION['user_id'];
    }

    $fileBankHref = rtrim(BASE_PATH, '/') . '/index.php?controller=FileBank&action=index';
    if (!empty($fileBankQuery)) {
        $fileBankHref .= '&' . http_build_query($fileBankQuery);
    }
?>

<div class="right">
    <div class="group-details">
        <h4>Quick Links</h4>
        <div class="detail-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; margin-top: 0.5rem;">
            <a href="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?controller=ChannelPage&action=index" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s;">
                <i class="uil uil-channel" style="font-size: 1.4rem;"></i>
                <span style="font-size: 0.82rem; font-weight: 500;">Channels</span>
            </a>
            <a href="<?php echo $fileBankHref; ?>" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s; background: var(--color-primary-light, #eef2ff);">
                <i class="uil uil-folder" style="font-size: 1.4rem;"></i>
                <span style="font-size: 0.82rem; font-weight: 500;">File Bank</span>
            </a>
            <a href="#" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s; opacity: 0.45;">
                <i class="uil uil-link" style="font-size: 1.4rem;"></i>
                <span style="font-size: 0.82rem; font-weight: 500;">Coming Soon</span>
            </a>
            <a href="#" class="detail-item" style="flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0.5rem; text-decoration: none; border: 1px solid var(--color-light); border-radius: var(--border-radius); text-align: center; gap: 0.4rem; transition: background 0.2s; opacity: 0.45;">
                <i class="uil uil-link" style="font-size: 1.4rem;"></i>
                <span style="font-size: 0.82rem; font-weight: 500;">Coming Soon</span>
            </a>
        </div>
    </div>
</div>