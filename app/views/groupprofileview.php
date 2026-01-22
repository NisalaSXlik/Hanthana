<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> - Hanthane</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php (function() { include __DIR__ . '/templates/left-sidebar.php'; })(); ?>

            <div class="middle">
                <div class="profile-header">
                    <div class="profile-cover">
                        <img id="groupCoverImage" src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($group['cover_image'] ?? '', 'uploads/group_cover/default_group_cover.jpg')); ?>" alt="Profile Cover">
                        <?php if ($isCreator || $isAdmin): ?>
                        <button class="edit-cover-btn">
                            <i class="uil uil-camera"></i> Edit Cover
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-dp-container">
                            <div class="profile-dp">
                                <img id="groupDpImage" src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($group['display_picture'] ?? '', 'uploads/group_dp/default_group_dp.jpg')); ?>" alt="Profile DP">
                                <?php if ($isCreator || $isAdmin): ?>
                                <button class="edit-dp-btn">
                                    <i class="uil uil-camera"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-details">
                            <p class="profile-name"><?php echo htmlspecialchars($group['name']); ?></p>
                            <p class="profile-handle">@<?php echo htmlspecialchars($group['tag']); ?></p>
                            <div class="profile-stats">
                                <div class="stat">
                                    <strong><?php echo htmlspecialchars($group['post_count'] ?? '0'); ?></strong>
                                    <span>Posts</span>
                                </div>
                                <div class="stat">
                                    <strong><?php echo htmlspecialchars($group['member_count'] ?? '0'); ?></strong>
                                    <span>Members</span>
                                </div>
                            </div>
                            <p class="profile-bio"><?php if (!empty($group['description'])) echo htmlspecialchars($group['description']); ?></p>
                            <div class="profile-actions">
                                <?php if (!$isCreator): ?>
                                    <?php if ($isJoined): ?>
                                        <button class="btn btn-danger leave-btn">Leave</button>
                                    <?php else: ?>
                                        <?php
                                            $membershipState = $membershipState ?? 'none';
                                            $joinLabel = 'Join';
                                            $joinDisabledAttr = '';
                                            $joinExtraClass = '';
                                            $joinPendingFlag = '0';

                                            if ($membershipState === 'pending' || !empty($hasPendingRequest)) {
                                                $joinLabel = 'Request sent';
                                                $joinDisabledAttr = 'disabled';
                                                $joinExtraClass = ' request-sent';
                                                $joinPendingFlag = '1';
                                            }
                                        ?>
                                        <button
                                            class="btn btn-primary join-btn<?php echo $joinExtraClass; ?>"
                                            data-pending="<?php echo $joinPendingFlag; ?>"
                                            data-membership="<?php echo htmlspecialchars($membershipState); ?>"
                                            <?php if ($joinDisabledAttr) echo $joinDisabledAttr; ?>><?php echo $joinLabel; ?></button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button class="btn btn-secondary invite-btn">Invite</button>
                                <button type="button"
                                        class="report-trigger"
                                        data-report-type="group"
                                        data-target-id="<?php echo (int)$group['group_id']; ?>"
                                        data-target-label="<?php echo htmlspecialchars('group ' . ($group['name'] ?? ''), ENT_QUOTES); ?>">
                                    <i class="uil uil-exclamation-circle"></i>
                                    Report Group
                                </button>

                                <?php if ($isCreator || $isAdmin): ?>
                                <div class="dropdown-container">
                                    <button class="btn btn-icon" id="groupOptionsBtn">
                                        <i class="uil uil-ellipsis-h"></i>
                                    </button>
                                    <div class="dropdown-menu" id="groupOptionsMenu" style="display: none;">
                                        <a href="#" class="dropdown-item" id="editGroupOption">
                                            <i class="uil uil-edit"></i>
                                            <span>Edit Group</span>
                                        </a>
                                        <a href="#" class="dropdown-item" id="manageRequestsOption">
                                            <i class="uil uil-user-check"></i>
                                            <span>Manage Requests</span>
                                        </a>
                                        <?php if ($isCreator): ?>
                                        <a href="#" class="dropdown-item delete-option" id="deleteGroupOption">
                                            <i class="uil uil-trash-alt"></i>
                                            <span>Delete Group</span>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="profile-tabs">
                        <ul>
                            <li class="active">
                                <a href="#" data-tab="posts">Posts</a>
                            </li>
                            <li>
                                <a href="#" data-tab="about">About</a>
                            </li>
                            <li>
                                <a href="#" data-tab="files">Files</a>
                            </li>
                            <li>
                                <a href="#" data-tab="events">Events</a>
                            </li>
                            <li>
                                <a href="#" data-tab="members">Members</a>
                            </li>
                            <li>
                                <a href="#" data-tab="photos">Photos</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="group-content">
                    <div class="tab-content active" id="posts-content">
                        <div class="create-post">
                            <div class="post-input">
                                <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($_SESSION['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg')); ?>" alt="Your Avatar">
                                <input type="text" placeholder="Share something with the group..." readonly id="quickPostTrigger" style="cursor: pointer;">
                            </div>
                            <div class="post-options">
                                <button class="option" id="photoQuickBtn">
                                    <i class="uil uil-image"></i>
                                    <span>Photo</span>
                                </button>
                                <button class="option" id="pollQuickBtn">
                                    <i class="uil uil-chart-pie"></i>
                                    <span>Poll</span>
                                </button>
                                <button class="option" id="questionQuickBtn">
                                    <i class="uil uil-question-circle"></i>
                                    <span>Question</span>
                                </button>
                                <button class="option" id="resourceQuickBtn">
                                    <i class="uil uil-book-alt"></i>
                                    <span>Resource</span>
                                </button>
                            </div>
                        </div>

                        <div class="posts-feed">
                            <?php if (!empty($groupPosts)): ?>
                                <?php foreach ($groupPosts as $index => $post): ?>
                                    <?php
                                        $postType = $post['group_post_type'] ?? 'discussion';
                                        $postMetadata = is_array($post['metadata']) ? $post['metadata'] : [];
                                        $postId = (int)$post['post_id'];
                                        $badgeIcons = ['discussion' => '💬', 'question' => '❓', 'resource' => '📚', 'poll' => '📊', 'event' => '📅', 'assignment' => '📋'];
                                        $badgeLabels = ['discussion' => 'Discussion', 'question' => 'Question', 'resource' => 'Resource', 'poll' => 'Poll', 'event' => 'Event', 'assignment' => 'Assignment'];
                                    ?>
                                    <div class="feed group-post-card <?php echo $postType; ?>-post group-post-clickable" id="post-<?php echo $postId; ?>" data-post-id="<?php echo $postId; ?>" data-post-index="<?php echo $index; ?>">
                                        <!-- Post Type Badge -->
                                        <div class="post-type-badge <?php echo $postType; ?>-badge">
                                            <?php echo $badgeIcons[$postType] . ' ' . $badgeLabels[$postType]; ?>
                                        </div>

                                        <div class="head">
                                            <div class="user">
                                                <div class="profile-picture">
                                                    <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($post['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg')); ?>" alt="<?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>">
                                                </div>
                                                <div class="info">
                                                    <h3><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h3>
                                                    <small><?php echo htmlspecialchars($post['created_at'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                            <i class="uil uil-ellipsis-h"></i>
                                        </div>

                                        <!-- Post Content -->
                                        <div class="post-body">
                                            <?php if (!empty($post['content']) && $postType === 'discussion'): ?>
                                                <div class="caption">
                                                    <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($post['image_url']) && $postType === 'discussion'): ?>
                                                <div class="photo post-image">
                                                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post image">
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($postType === 'question'): ?>
                                                <!-- Question Post -->
                                                <div class="question-content">
                                                    <?php if (!empty($postMetadata['category'])): ?>
                                                        <span class="question-category"><?php echo htmlspecialchars($postMetadata['category']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($post['content'])): ?>
                                                        <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($post['image_url'])): ?>
                                                    <div class="post-image">
                                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post image">
                                                    </div>
                                                <?php endif; ?>

                                            <?php elseif ($postType === 'resource'): ?>
                                                <!-- Resource Post -->
                                                <?php
                                                    $resourceTypeLabel = $postMetadata['resource_type'] ?? ($postMetadata['type'] ?? '');
                                                    $resourceLink = $postMetadata['resource_link'] ?? ($postMetadata['link'] ?? '');
                                                    $resourceDownloadUrl = !empty($postMetadata['file_path']) ? BASE_PATH . ltrim($postMetadata['file_path'], '/') : '';
                                                ?>
                                                <div class="resource-content">
                                                    <h3 class="resource-title"><?php echo htmlspecialchars($postMetadata['title'] ?? 'Untitled Resource'); ?></h3>
                                                    <?php if (!empty($resourceTypeLabel)): ?>
                                                        <span class="resource-type-label"><?php echo htmlspecialchars($resourceTypeLabel); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($post['content'])): ?>
                                                        <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                    <?php endif; ?>
                                                    <div class="resource-actions">
                                                        <?php if (!empty($resourceDownloadUrl)): ?>
                                                            <a href="<?php echo htmlspecialchars($resourceDownloadUrl); ?>" class="resource-download" download target="_blank" aria-label="Download resource file">
                                                                <i class="uil uil-download-alt"></i>
                                                                <span>Download File</span>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (!empty($resourceLink)): ?>
                                                            <a href="<?php echo htmlspecialchars($resourceLink); ?>" class="resource-link" target="_blank" rel="noopener noreferrer" aria-label="Open external resource link">
                                                                <i class="uil uil-external-link-alt"></i>
                                                                <span>Open Link</span>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                            <?php elseif ($postType === 'poll'): ?>
                                                <!-- Poll Post -->
                                                <div class="poll-content">
                                                    <?php if (!empty($post['content'])): ?>
                                                        <p class="post-text poll-question"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                    <?php endif; ?>
                                                    <div class="poll-options" data-post-id="<?php echo $postId; ?>">
                                                        <?php
                                                        $options = $postMetadata['options'] ?? [];
                                                        $votes = $postMetadata['votes'] ?? array_fill(0, count($options), 0);
                                                        $totalVotes = array_sum($votes);
                                                        $userPollVote = isset($post['user_poll_vote']) ? (int)$post['user_poll_vote'] : -1;
                                                        $hasVoted = $userPollVote >= 0;
                                                        foreach ($options as $index => $optionText):
                                                            $voteCount = (int)($votes[$index] ?? 0);
                                                            $percentage = $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100) : 0;
                                                            $isSelected = $hasVoted && $index === $userPollVote;
                                                        ?>
                                                            <div class="poll-option <?php echo $isSelected ? 'selected' : ''; ?>" data-option-index="<?php echo $index; ?>">
                                                                <button class="poll-option-btn" type="button" aria-label="Vote for <?php echo htmlspecialchars($optionText); ?>">
                                                                    <span class="option-text"><?php echo htmlspecialchars($optionText); ?></span>
                                                                    <div class="option-stats">
                                                                        <span class="option-percentage"><?php echo $percentage; ?>%</span>
                                                                        <span class="option-votes"><?php echo $voteCount; ?> vote<?php echo $voteCount === 1 ? '' : 's'; ?></span>
                                                                    </div>
                                                                    <div class="option-progress" style="width: <?php echo $percentage; ?>%"></div>
                                                                </button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="poll-footer" data-post-id="<?php echo $postId; ?>">
                                                        <button type="button" class="poll-total-votes" data-post-id="<?php echo $postId; ?>">
                                                            <i class="uil uil-users-alt"></i>
                                                            <span><?php echo $totalVotes; ?> total vote<?php echo $totalVotes === 1 ? '' : 's'; ?></span>
                                                        </button>
                                                        <?php if (!empty($postMetadata['duration'])): ?>
                                                            <span class="poll-duration">Ends in <?php echo (int)$postMetadata['duration']; ?> days</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="poll-voters-panel" id="poll-voters-<?php echo $postId; ?>" data-post-id="<?php echo $postId; ?>" hidden>
                                                        <div class="poll-voters-content">
                                                            <div class="poll-voters-placeholder">
                                                                Click total votes to view voter details
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php elseif ($postType === 'event'): ?>
                                                <!-- Event Post -->
                                                <div class="event-content">
                                                    <h3 class="event-title"><?php echo htmlspecialchars($postMetadata['title'] ?? ($post['event_title'] ?? 'Untitled Event')); ?></h3>
                                                    <?php if (!empty($post['content'])): ?>
                                                        <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                    <?php endif; ?>
                                                    <div class="event-details">
                                                        <?php if (!empty($postMetadata['date']) || !empty($post['event_date'])): ?>
                                                            <div class="event-detail">
                                                                <i class="uil uil-calendar-alt"></i>
                                                                <span><?php echo date('l, F j, Y', strtotime($postMetadata['date'] ?? $post['event_date'])); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($postMetadata['time']) || !empty($post['event_time'])): ?>
                                                            <div class="event-detail">
                                                                <i class="uil uil-clock"></i>
                                                                <span><?php echo htmlspecialchars($postMetadata['time'] ?? $post['event_time']); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($postMetadata['location']) || !empty($post['event_location'])): ?>
                                                            <div class="event-detail">
                                                                <i class="uil uil-map-marker"></i>
                                                                <span><?php echo htmlspecialchars($postMetadata['location'] ?? $post['event_location']); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                            <?php elseif ($postType === 'assignment'): ?>
                                                <!-- Assignment Post -->
                                                <div class="assignment-content">
                                                    <h3 class="assignment-title"><?php echo htmlspecialchars($postMetadata['title'] ?? 'Untitled Assignment'); ?></h3>
                                                    <?php if (!empty($post['content'])): ?>
                                                        <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                    <?php endif; ?>
                                                    <div class="assignment-details">
                                                        <?php if (!empty($postMetadata['deadline'])): ?>
                                                            <div class="assignment-detail deadline">
                                                                <i class="uil uil-clock"></i>
                                                                <span>Due: <?php echo date('M j, Y g:i A', strtotime($postMetadata['deadline'])); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($postMetadata['points'])): ?>
                                                            <div class="assignment-detail points">
                                                                <i class="uil uil-award"></i>
                                                                <span><?php echo (int)$postMetadata['points']; ?> points</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Post Actions -->
                                        <div class="action-buttons compact">
                                            <div class="interaction-buttons compact">
                                                <button class="action-button compact upvote-btn" data-post-id="<?php echo $postId; ?>">
                                                    <i class="uil uil-arrow-up <?php echo (isset($post['user_vote']) && $post['user_vote'] === 'upvote') ? 'liked' : ''; ?>" aria-hidden="true"></i>
                                                    <span class="action-count"><?php echo (int)($post['upvote_count'] ?? 0); ?></span>
                                                </button>
                                                <button class="action-button compact downvote-btn" data-post-id="<?php echo $postId; ?>">
                                                    <i class="uil uil-arrow-down <?php echo (isset($post['user_vote']) && $post['user_vote'] === 'downvote') ? 'liked' : ''; ?>" aria-hidden="true"></i>
                                                    <span class="action-count"><?php echo (int)($post['downvote_count'] ?? 0); ?></span>
                                                </button>
                                                <button class="action-button compact comment-btn load-comments-btn" data-post-id="<?php echo $postId; ?>">
                                                    <i class="uil uil-comment" aria-hidden="true"></i>
                                                    <span class="action-count"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
                                                </button>
                                            </div>
                                            <button class="action-button compact bookmark-btn" data-post-id="<?php echo $postId; ?>" aria-label="Bookmark post">
                                                <i class="uil <?php echo (!empty($post['is_bookmarked'])) ? 'uil-bookmark-full bookmarked' : 'uil-bookmark'; ?>" aria-hidden="true"></i>
                                            </button>
                                            <button type="button"
                                                    class="action-button compact report-trigger"
                                                    data-report-type="post"
                                                    data-target-id="<?php echo $postId; ?>"
                                                    data-target-label="<?php echo htmlspecialchars('post in ' . ($group['name'] ?? 'group'), ENT_QUOTES); ?>">
                                                <i class="uil uil-exclamation-circle" aria-hidden="true"></i>
                                                <span>Report</span>
                                            </button>
                                        </div>

                                        <?php if (!empty($post['comment_count'])): ?>
                                            <div class="comments load-comments-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                                View all <?php echo (int)$post['comment_count']; ?> comments
                                            </div>
                                        <?php else: ?>
                                            <div class="comments load-comments-btn" data-post-id="<?php echo (int)$post['post_id']; ?>" style="display:none;">
                                                View all 0 comments
                                            </div>
                                        <?php endif; ?>

                                        <div id="comments-post-<?php echo (int)$post['post_id']; ?>" class="comment-section" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                            <div class="comment-header">
                                                <h3>Comments</h3>
                                                <button class="close-comments" type="button">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div id="comments-container-<?php echo (int)$post['post_id']; ?>" class="comments-container" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                                <div class="comments-loading">Click to load comments</div>
                                            </div>
                                            
                                            <div class="add-comment-form">
                                                <div class="comment-input-container">
                                                    <?php
                                                    $currentUserAvatar = MediaHelper::resolveMediaPath($_SESSION['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg');
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($currentUserAvatar); ?>" alt="Your Avatar" class="current-user-avatar">
                                                    <div class="comment-input-wrapper">
                                                        <textarea class="comment-input" placeholder="Write a comment..." data-post-id="<?php echo (int)$post['post_id']; ?>"></textarea>
                                                        <button class="comment-submit-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">Post Comment</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="feeds">
                                    <div class="feed">
                                        <div class="caption"><p>No posts yet. Create one to get started.</p></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-content" id="about-content">
                        <div class="about-grid">
                            <div class="about-card about-overview">
                                <h3>About This Group</h3>
                                <p><?php echo htmlspecialchars($group['description'] ?? 'This group does not have a description yet.'); ?></p>
                            </div>

                            <div class="about-card about-details">
                                <h4>Key Details</h4>
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
                                    <li>
                                        <i class="uil uil-tag"></i>
                                        <span>Group Tag</span>
                                        <strong><?php echo htmlspecialchars($group['tag'] ?? 'N/A'); ?></strong>
                                    </li>
                                </ul>
                            </div>

                            <?php if (!empty($groupRulesList)): ?>
                            <div class="about-card about-rules">
                                <h4>Group Rules</h4>
                                <ul class="rules-list">
                                    <?php foreach ($groupRulesList as $rule): ?>
                                        <li><i class="uil uil-check-circle"></i><?php echo htmlspecialchars($rule); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Files tab -->
                    <div class="tab-content" id="files-content">
                        <div class="files-list">
                            <?php if (!empty($groupResourceBuckets)): ?>
                                <?php
                                    $resourceLabels = [
                                        'notes' => 'Lecture Notes',
                                        'slides' => 'Slides & Presentations',
                                        'document' => 'Documents',
                                        'link' => 'External Links',
                                        'video' => 'Videos',
                                        'book' => 'Books & Papers',
                                        'other' => 'Other Resources'
                                    ];
                                    $resourceIcons = [
                                        'notes' => 'uil-notes',
                                        'slides' => 'uil-presentation',
                                        'document' => 'uil-file-alt',
                                        'link' => 'uil-link-h',
                                        'video' => 'uil-video',
                                        'book' => 'uil-book',
                                        'other' => 'uil-file-info'
                                    ];
                                    $resourceColorClasses = [
                                        'notes' => 'files-theme-notes',
                                        'slides' => 'files-theme-slides',
                                        'document' => 'files-theme-document',
                                        'link' => 'files-theme-link',
                                        'video' => 'files-theme-video',
                                        'book' => 'files-theme-book',
                                        'other' => 'files-theme-other'
                                    ];
                                ?>
                                <?php foreach ($groupResourceBuckets as $typeKey => $resources): ?>
                                    <?php
                                        $label = $resourceLabels[$typeKey] ?? ucfirst($typeKey);
                                        $icon = $resourceIcons[$typeKey] ?? 'uil-file-info';
                                        $itemCount = count($resources);
                                        $themeClass = $resourceColorClasses[$typeKey] ?? 'files-theme-document';
                                    ?>
                                    <div class="file-category <?php echo $themeClass; ?>">
                                        <div class="file-category-header">
                                            <div class="file-category-icon">
                                                <i class="uil <?php echo $icon; ?>"></i>
                                            </div>
                                            <div>
                                                <p class="file-category-label"><?php echo htmlspecialchars($label); ?></p>
                                                <small><?php echo $itemCount; ?> item<?php echo $itemCount === 1 ? '' : 's'; ?></small>
                                            </div>
                                        </div>
                                        <div class="file-category-items">
                                            <?php foreach ($resources as $resource): ?>
                                                <?php
                                                    $downloadUrl = !empty($resource['file_path']) ? BASE_PATH . ltrim($resource['file_path'], '/') : '';
                                                    $externalLink = $resource['link'] ?? '';
                                                    $uploadedAt = !empty($resource['uploaded_at']) ? date('M j, Y g:i A', strtotime($resource['uploaded_at'])) : '';
                                                    $resourceTitle = $resource['title'] ?? 'Shared Resource';
                                                    $resourceDescription = $resource['description'] ?? '';
                                                ?>
                                                <div class="file-item">
                                                    <div class="file-meta">
                                                        <i class="uil <?php echo $icon; ?>"></i>
                                                        <div class="file-info">
                                                            <div class="file-title-row">
                                                                <strong><?php echo htmlspecialchars($resourceTitle); ?></strong>
                                                                <span class="file-type-chip"><?php echo htmlspecialchars($label); ?></span>
                                                            </div>
                                                            <?php if (!empty($resourceDescription)): ?>
                                                                <p class="file-description"><?php echo nl2br(htmlspecialchars($resourceDescription)); ?></p>
                                                            <?php endif; ?>
                                                            <small>Uploaded <?php echo $uploadedAt ? htmlspecialchars($uploadedAt) : 'recently'; ?> by <?php echo htmlspecialchars($resource['uploader_name'] ?? 'Member'); ?></small>
                                                            <div class="file-actions">
                                                                <?php if (!empty($downloadUrl)): ?>
                                                                    <a href="<?php echo htmlspecialchars($downloadUrl); ?>" download target="_blank">
                                                                        <i class="uil uil-download-alt"></i> Download
                                                                    </a>
                                                                <?php endif; ?>
                                                                <?php if (!empty($externalLink)): ?>
                                                                    <a href="<?php echo htmlspecialchars($externalLink); ?>" target="_blank" rel="noopener noreferrer">
                                                                        <i class="uil uil-external-link-alt"></i> Open Link
                                                                    </a>
                                                                <?php endif; ?>
                                                                <?php if (empty($downloadUrl) && empty($externalLink)): ?>
                                                                    <span class="file-missing-note">No file or link attached</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="file-empty">
                                    <i class="uil uil-folder-slash"></i>
                                    <p>No resources shared yet</p>
                                    <small>Upload lecture notes, slides, videos or helpful links via the Resource post type.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Events tab -->
                    <div class="tab-content" id="events-content">
                        <div class="events-list">
                            <?php if (!empty($groupEvents)): ?>
                                <div class="events-grid">
                                <?php foreach ($groupEvents as $event): ?>
                                    <?php
                                        $evDate = !empty($event['date']) ? strtotime($event['date']) : null;
                                        $evMonth = $evDate ? strtoupper(date('M', $evDate)) : 'TBD';
                                        $evDay = $evDate ? date('j', $evDate) : '--';
                                        $evDateLabel = $evDate ? date('M j, Y', $evDate) : 'Date TBA';
                                        $evTime = !empty($event['time']) ? $event['time'] : '';
                                        $evLocation = !empty($event['location']) ? $event['location'] : '';
                                        $description = !empty($event['description']) ? $event['description'] : 'More details coming soon.';
                                    ?>
                                    <div class="event-card">
                                        <div class="event-date">
                                            <div class="month"><?php echo htmlspecialchars($evMonth); ?></div>
                                            <div class="date-number"><?php echo htmlspecialchars($evDay); ?></div>
                                        </div>
                                        <div class="event-info">
                                            <h4><?php echo htmlspecialchars($event['title'] ?? 'Untitled Event'); ?></h4>
                                            <p class="meta"><?php echo htmlspecialchars($evDateLabel); ?><?php echo $evTime ? ' · ' . htmlspecialchars($evTime) : ''; ?><?php echo $evLocation ? ' · ' . htmlspecialchars($evLocation) : ''; ?></p>
                                            <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
                                        </div>
                                        <div class="event-actions">
                                            <?php
                                                $isInterested = !empty($event['interested']);
                                                $buttonIcon = $isInterested ? 'uil-check' : 'uil-star';
                                                $buttonClass = 'btn btn-primary event-interest-btn' . ($isInterested ? ' interested' : '');
                                            ?>
                                            <button
                                                class="<?php echo $buttonClass; ?>"
                                                data-post-id="<?php echo (int)$event['post_id']; ?>"
                                                data-group-id="<?php echo (int)$groupId; ?>"
                                                data-event-title="<?php echo htmlspecialchars($event['title'] ?? 'Untitled Event'); ?>"
                                                data-event-date="<?php echo htmlspecialchars($event['date'] ?? ''); ?>"
                                                data-event-time="<?php echo htmlspecialchars($event['time'] ?? ''); ?>"
                                                data-event-location="<?php echo htmlspecialchars($evLocation ?: ''); ?>"
                                                data-event-description="<?php echo htmlspecialchars($description); ?>"
                                            >
                                                <i class="uil <?php echo $buttonIcon; ?>"></i>
                                                Interested
                                            </button>
                                            <small><?php echo htmlspecialchars($evLocation ?: 'Location TBA'); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="events-empty">
                                    <i class="uil uil-calendar-slash"></i>
                                    <p>No upcoming events</p>
                                    <small>Create an Event post to let members know what's happening next.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Members tab -->
                    <div class="tab-content" id="members-content">
                        <div class="members-grid">
                            <?php if ($isAdmin && !empty($pendingRequests)): ?>
                                <div class="pending-requests">
                                    <h4>Pending Join Requests</h4>
                                    <?php foreach ($pendingRequests as $req): ?>
                                        <?php $reqUserId = (int)$req['user_id']; ?>
                                        <div class="request-item" data-user-id="<?php echo $reqUserId; ?>">
                                            <div class="requester">
                                                <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($req['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg')); ?>" alt="<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>">
                                                <div class="requester-info">
                                                    <strong><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></strong>
                                                    <small>@<?php echo htmlspecialchars($req['username']); ?> · <?php echo htmlspecialchars(date('M j, H:i', strtotime($req['requested_at']))); ?></small>
                                                </div>
                                            </div>
                                            <div class="request-actions">
                                                <button class="btn btn-primary approve-request" data-user-id="<?php echo $reqUserId; ?>">Approve</button>
                                                <button class="btn btn-secondary reject-request" data-user-id="<?php echo $reqUserId; ?>">Reject</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($groupMembers)): ?>
                                <?php foreach ($groupMembers as $member): ?>
                                    <?php
                                        $memberId = isset($member['user_id']) ? (int)$member['user_id'] : (int)($member['id'] ?? 0);
                                        $profileUrl = rtrim(BASE_PATH, '/') . '/index.php?controller=Profile&action=view&user_id=' . $memberId;
                                        $dp = MediaHelper::resolveMediaPath($member['profile_picture'] ?? '', 'uploads/user_dp/default_user_dp.jpg');
                                    ?>
                                    <a class="member-link" href="<?php echo htmlspecialchars($profileUrl); ?>">
                                        <div class="member-card">
                                            <div class="member-dp">
                                                <img src="<?php echo htmlspecialchars($dp); ?>" alt="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>">
                                            </div>
                                            <div class="member-info">
                                                <p class="member-name"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></p>
                                                <small class="member-username">@<?php echo htmlspecialchars($member['username'] ?? ''); ?></small>
                                                <?php if (!empty($member['role'])): ?>
                                                    <span class="member-role"><?php echo htmlspecialchars(ucfirst($member['role'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-members-centered">
                                    <i class="uil uil-users-alt" style="font-size:36px;color:var(--color-primary)"></i>
                                    <p>No members yet — be the first to join this group.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Photos tab -->
                    <div class="tab-content" id="photos-content">
                        <div class="photos-grid">
                            <?php if (!empty($groupPhotos)): ?>
                                <?php foreach ($groupPhotos as $photo): ?>
                                    <div class="photo-item">
                                        <img src="<?php echo htmlspecialchars($photo['file_url'] ?? $photo['thumbnail_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($photo['file_name'] ?? 'Photo'); ?>">
                                        <div class="photo-meta">
                                            <small><?php echo htmlspecialchars($photo['uploaded_at'] ?? ''); ?> by <?php echo htmlspecialchars($photo['uploader_name'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Dummy photos grid -->
                                <div class="photo-item">
                                    <img src="./images/gpvpost_content1.jpg" alt="Photo 1">
                                    <div class="photo-meta"><small>2 days ago · by Alex</small></div>
                                </div>
                                <div class="photo-item">
                                    <img src="./images/gpvpost_content2.jpg" alt="Photo 2">
                                    <div class="photo-meta"><small>1 week ago · by Sam</small></div>
                                </div>
                                <div class="photo-item">
                                    <img src="./images/gpvpost_content3.jpg" alt="Photo 3">
                                    <div class="photo-meta"><small>3 weeks ago · by Priya</small></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right">
                <div class="group-details">
                    <h4>Group Details</h4>
                    <div class="detail-list">
                        <div class="detail-item">
                            <i class="uil uil-user"></i>
                            <span><?php echo ucfirst(htmlspecialchars($group['privacy_status'])) . ' Group'; ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-compass"></i>
                            <span><?php echo htmlspecialchars($group['focus'] ?? 'No focus'); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-home"></i>
                            <span><?php echo htmlspecialchars($group['tag'] ?? 'No tag'); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-calendar-alt"></i>
                            <span>Created <?php echo date('F Y', strtotime($group['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <?php
                    $friendRequests = $incomingFriendRequests ?? [];
                    include __DIR__ . '/templates/friend-requests.php';
                ?>

                <!-- Top Collaborators -->
            </div>
        </div>

        <!-- Instagram-style Post View Modal -->
        <div class="post-view-modal" id="postViewModal" aria-hidden="true">
            <div class="post-view-overlay"></div>
            <button class="post-view-close" id="closePostModal" aria-label="Close">
                <i class="uil uil-times"></i>
            </button>
            <button class="post-view-nav post-view-prev" id="prevPost" aria-label="Previous post">
                <i class="uil uil-angle-left"></i>
            </button>
            <button class="post-view-nav post-view-next" id="nextPost" aria-label="Next post">
                <i class="uil uil-angle-right"></i>
            </button>
            <div class="post-view-content">
                <div class="post-view-image">
                    <img id="postViewImage" src="" alt="Post image">
                    <div class="post-view-text-content" id="postViewTextOnly" style="display: none;">
                        <p id="postViewTextContent"></p>
                    </div>
                </div>
                
                <div class="post-view-sidebar">
                    <div class="post-view-header">
                        <div class="user">
                            <div class="profile-picture">
                                <img id="postViewUserAvatar" src="" alt="User avatar">
                            </div>
                            <div class="info">
                                <h3 id="postViewUsername"></h3>
                                <small id="postViewTimestamp"></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="post-view-caption">
                        <p id="postViewCaption"></p>
                    </div>

                    <div class="post-view-actions">
                        <div class="modal-interactions">
                            <button type="button" class="modal-vote" id="postViewUpvoteBtn" data-vote-type="upvote" aria-label="Upvote">
                                <i class="uil uil-arrow-up"></i>
                                <span id="postViewUpvoteCount">0</span>
                            </button>
                            <button type="button" class="modal-vote" id="postViewDownvoteBtn" data-vote-type="downvote" aria-label="Downvote">
                                <i class="uil uil-arrow-down"></i>
                                <span id="postViewDownvoteCount">0</span>
                            </button>
                            <button type="button" class="modal-comments-toggle" id="postViewCommentToggle" aria-label="View comments">
                                <i class="uil uil-comment-dots"></i>
                                <span id="postViewCommentCount">0</span>
                            </button>
                        </div>
                    </div>

                    <div class="post-view-comments active" id="postViewCommentsPanel">
                        <div class="comments-header">
                            <h4>Comments</h4>
                            <span id="postViewCommentBadge">0</span>
                        </div>
                        <div class="comments-list" id="postViewCommentsList">
                            <div class="comments-loading">Loading comments...</div>
                        </div>
                        <form id="postViewCommentForm" class="comment-form">
                            <textarea id="postViewCommentInput" placeholder="Add a comment..." rows="2"></textarea>
                            <button type="submit" id="postViewCommentSubmit">Post</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Group Modal -->
    <div id="editGroupModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Group</h3>
                <button class="modal-close" id="closeEditGroupModal">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="editGroupForm" class="modal-body" enctype="multipart/form-data">
                <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                <div class="form-group">
                    <label for="editGroupName">Group Name</label>
                    <input type="text" id="editGroupName" name="name" maxlength="255" value="<?php echo htmlspecialchars($group['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editGroupTag">Group Tag</label>
                    <input type="text" id="editGroupTag" name="tag" maxlength="50" value="<?php echo htmlspecialchars($group['tag']); ?>">
                </div>
                <div class="form-group">
                    <label for="editGroupDescription">Description</label>
                    <textarea id="editGroupDescription" name="description" rows="3"><?php echo htmlspecialchars($group['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="editGroupFocus">Focus/Category</label>
                    <input type="text" id="editGroupFocus" name="focus" maxlength="100" value="<?php echo htmlspecialchars($group['focus']); ?>">
                </div>
                <div class="form-group">
                    <label for="editGroupPrivacy">Privacy</label>
                    <select id="editGroupPrivacy" name="privacy_status">
                        <option value="public" <?php if ($group['privacy_status'] === 'public') echo 'selected'; ?>>Public</option>
                        <option value="private" <?php if ($group['privacy_status'] === 'private') echo 'selected'; ?>>Private</option>
                        <option value="secret" <?php if ($group['privacy_status'] === 'secret') echo 'selected'; ?>>Secret</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editGroupRules">Group Rules</label>
                    <textarea id="editGroupRules" name="rules" rows="3"><?php echo htmlspecialchars($group['rules']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Cover Photo</label><br>
                    <label for="editGroupCover" class="image-upload-label"><i class="uil uil-image"></i> Choose Cover Photo</label>
                    <input type="file" id="editGroupCover" name="cover_image" accept="image/*">
                    <img id="coverPreview" class="image-preview" src="<?php
                        if (!empty($group['cover_image'])) {
                            $coverPath = htmlspecialchars($group['cover_image']);
                            echo BASE_PATH . ltrim($coverPath, '/');
                        } else {
                            echo '';
                        }
                    ?>" alt="Cover Preview" <?php if (empty($group['cover_image'])) echo 'style="display:none;"'; ?> >
                </div>
                <div class="form-group">
                    <label>Display Picture</label><br>
                    <label for="editGroupDP" class="image-upload-label"><i class="uil uil-user"></i> Choose Display Picture</label>
                    <input type="file" id="editGroupDP" name="display_picture" accept="image/*">
                    <img id="dpPreview" class="image-preview" src="<?php
                        if (!empty($group['display_picture'])) {
                            $dpPath = htmlspecialchars($group['display_picture']);
                            echo BASE_PATH . ltrim($dpPath, '/');
                        } else {
                            echo '';
                        }
                    ?>" alt="DP Preview" <?php if (empty($group['display_picture'])) echo 'style="display:none;"'; ?> >
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelEditGroupBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteGroupModal" class="confirm-modal">
        <div class="modal-content">
            <h3>Delete Group?</h3>
            <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($group['name']); ?></strong>? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="cancelDeleteBtn">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Create Post Modal -->
    <div id="createPostModal" class="modal-overlay" style="display:none;">
        <div class="modal-content create-post-modal">
            <div class="modal-header">
                <h3>Create Post</h3>
                <button class="modal-close" id="closeCreatePostModal">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="createGroupPostForm" class="modal-body" enctype="multipart/form-data">
                <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                <input type="hidden" name="post_type" id="selectedPostType" value="discussion">
                
                <!-- Post Type Selection -->
                <div class="post-type-selector">
                    <button type="button" class="post-type-btn active" data-type="discussion">
                        <i class="uil uil-comment-alt-notes"></i>
                        <span>Discussion</span>
                    </button>
                    <button type="button" class="post-type-btn" data-type="question">
                        <i class="uil uil-question-circle"></i>
                        <span>Question</span>
                    </button>
                    <button type="button" class="post-type-btn" data-type="resource">
                        <i class="uil uil-book-alt"></i>
                        <span>Resource</span>
                    </button>
                    <button type="button" class="post-type-btn" data-type="poll">
                        <i class="uil uil-chart-pie"></i>
                        <span>Poll</span>
                    </button>
                    <button type="button" class="post-type-btn" data-type="event">
                        <i class="uil uil-calendar-alt"></i>
                        <span>Event</span>
                    </button>
                    <button type="button" class="post-type-btn" data-type="assignment">
                        <i class="uil uil-file-check-alt"></i>
                        <span>Assignment</span>
                    </button>
                </div>

                <!-- Common Fields -->
                <div class="form-group">
                    <label for="postContent">Content</label>
                    <textarea id="postContent" name="content" rows="4" placeholder="Share your thoughts..." required></textarea>
                </div>

                <!-- Question-specific fields -->
                <div id="questionFields" class="conditional-fields" style="display:none;">
                    <div class="form-group">
                        <label for="questionCategory">Category</label>
                        <select id="questionCategory" name="question_category">
                            <option value="general">General</option>
                            <option value="technical">Technical</option>
                            <option value="assignment">Assignment Help</option>
                            <option value="exam">Exam Prep</option>
                            <option value="project">Project Discussion</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="questionTags">Tags (comma separated)</label>
                        <input type="text" id="questionTags" name="tags" placeholder="e.g., java, arrays, data-structures">
                    </div>
                </div>

                <!-- Resource-specific fields -->
                <div id="resourceFields" class="conditional-fields" style="display:none;">
                    <div class="form-group">
                        <label for="resourceTitle">Resource Title</label>
                        <input type="text" id="resourceTitle" name="resource_title" placeholder="Name of the resource">
                    </div>
                    <div class="form-group">
                        <label for="resourceType">Resource Type</label>
                        <select id="resourceType" name="resource_type">
                            <option value="notes">Lecture Notes</option>
                            <option value="slides">Slides/Presentation</option>
                            <option value="document">Document</option>
                            <option value="link">External Link</option>
                            <option value="video">Video</option>
                            <option value="book">Book/Paper</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="resourceLink">Link (optional)</label>
                        <input type="url" id="resourceLink" name="resource_link" placeholder="https://...">
                    </div>
                </div>

                <!-- Poll-specific fields -->
                <div id="pollFields" class="conditional-fields" style="display:none;">
                    <div class="form-group">
                        <label for="pollQuestion">Poll Question</label>
                        <input type="text" id="pollQuestion" name="poll_question" placeholder="Ask a question...">
                    </div>
                    <div class="form-group">
                        <label>Options</label>
                        <input type="text" name="poll_option_1" placeholder="Option 1" class="mb-2">
                        <input type="text" name="poll_option_2" placeholder="Option 2" class="mb-2">
                        <input type="text" name="poll_option_3" placeholder="Option 3 (optional)" class="mb-2">
                        <input type="text" name="poll_option_4" placeholder="Option 4 (optional)">
                    </div>
                    <div class="form-group">
                        <label for="pollDuration">Duration (days)</label>
                        <input type="number" id="pollDuration" name="poll_duration" min="1" max="30" value="7">
                    </div>
                </div>

                <!-- Event-specific fields -->
                <div id="eventFields" class="conditional-fields" style="display:none;">
                    <div class="form-group">
                        <label for="eventTitle">Event Title</label>
                        <input type="text" id="eventTitle" name="event_title" placeholder="Event name">
                    </div>
                    <div class="form-group">
                        <label for="eventDate">Event Date</label>
                        <input type="date" id="eventDate" name="event_date">
                    </div>
                    <div class="form-group">
                        <label for="eventTime">Event Time</label>
                        <input type="time" id="eventTime" name="event_time">
                    </div>
                    <div class="form-group">
                        <label for="eventLocation">Location</label>
                        <input type="text" id="eventLocation" name="event_location" placeholder="Where will this take place?">
                    </div>
                </div>

                <!-- Assignment-specific fields -->
                <div id="assignmentFields" class="conditional-fields" style="display:none;">
                    <div class="form-group">
                        <label for="assignmentTitle">Assignment Title</label>
                        <input type="text" id="assignmentTitle" name="assignment_title" placeholder="Assignment name">
                    </div>
                    <div class="form-group">
                        <label for="assignmentDeadline">Deadline</label>
                        <input type="datetime-local" id="assignmentDeadline" name="assignment_deadline">
                    </div>
                    <div class="form-group">
                        <label for="assignmentPoints">Points</label>
                        <input type="number" id="assignmentPoints" name="assignment_points" min="0" placeholder="e.g., 100">
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="form-group">
                    <button type="button" class="upload-btn" id="uploadImageBtn">
                        <i class="uil uil-image"></i> Add Image
                    </button>
                    <input type="file" id="postImageInput" name="image" accept="image/*" style="display:none;">
                    <div id="imagePreviewContainer" style="display:none; position:relative; margin-top:10px;">
                        <img id="imagePreview" src="" alt="Preview" style="max-width:100%; max-height:300px; border-radius:8px;">
                        <button type="button" id="removeImageBtn" style="position:absolute; top:10px; right:10px; background:rgba(0,0,0,0.6); color:white; border:none; border-radius:50%; width:30px; height:30px; cursor:pointer;">
                            <i class="uil uil-times"></i>
                        </button>
                    </div>
                </div>

                <!-- File Upload (for resources) -->
                <div id="fileUploadSection" class="form-group" style="display:none;">
                    <button type="button" class="upload-btn" id="uploadFileBtn">
                        <i class="uil uil-file"></i> Attach File
                    </button>
                    <input type="file" id="postFileInput" name="file" style="display:none;">
                    <div id="filePreviewContainer" style="display:none; margin-top:10px; padding:10px; background:var(--color-light); border-radius:8px;">
                        <i class="uil uil-file"></i>
                        <span id="fileName"></span>
                        <button type="button" id="removeFileBtn" style="margin-left:10px;">
                            <i class="uil uil-times"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelCreatePostBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitPostBtn">Post</button>
                </div>
            </form>
        </div>
    </div>

    <div class="calendar-popup" id="calendarPopup">
        <div class="calendar-popup-header">
            <h4>Events</h4>
            <span id="popup-date">--</span>
        </div>
        <div class="calendar-popup-body" id="calendarEvents">
            <div class="no-events">
                <i class="uil uil-calendar-slash"></i>
                <p>No events scheduled</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <script> const BASE_PATH = '<?php echo BASE_PATH; ?>'; </script>
    <script src="./js/calender.js"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/report.js"></script>
    <script src="./js/groupprofileview.js"></script>
    <script src="./js/group-post-create.js"></script>
    <script src="./js/group-poll-voting.js"></script>
    <script src="./js/group-post-interactions.js"></script>
    <script>
        const GROUP_ID = <?php echo $groupId; ?>;
        const IS_CREATOR = <?php echo $isCreator ? 'true' : 'false'; ?>;
        const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const HAS_PENDING_REQUEST = <?php echo !empty($hasPendingRequest) ? 'true' : 'false'; ?>;
        const MEMBERSHIP_STATE = '<?php echo isset($membershipState) ? addslashes($membershipState) : 'unknown'; ?>';
        
        // Pass group posts data to JavaScript for modal viewing
        window.GROUP_POSTS = <?php echo json_encode($groupPosts ?? []); ?>;
    </script>
</body>
</html>