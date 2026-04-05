<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

// Ensure session for ownership/UI logic
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}

$userModel = new UserModel;
$currentUser = $userModel->findById((int)$_SESSION['user_id']);

// Posts should arrive from the controller
if (!isset($posts)) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Discover&action=index');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover Feed | Hanthana</title>
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/discover.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/solid.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php $activeSidebar = 'discover'; include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <div class="feeds">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <?php
                            // Profile picture is already resolved by PostModel
                            $avatarUrl = MediaHelper::resolveMediaPath($post['profile_picture'], 'uploads/user_dp/default.png');
                            $fullName = trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? ''));
                            $displayName = $post['username'] ?? '';
                            
                            if ($displayName === '' || $displayName === null) {
                                $displayName = $fullName !== '' ? $fullName : 'Unknown';
                            }

                            $isOwner = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)($post['author_id'] ?? $post['user_id'] ?? 0);
                            $postContentForAttr = htmlspecialchars($post['content'] ?? '', ENT_QUOTES);
                            $reportLabel = !empty($post['group_id']) && !empty($post['group_name'])
                                ? 'post in ' . ($post['group_name'] ?? 'group')
                                : 'post by ' . $displayName;

                            // Calculate Post URL for card click
                            $authorUserId = (int)($post['author_id'] ?? $post['user_id'] ?? 0);
                            $postId = (int)$post['post_id'];
                            $isGroupPost = !empty($post['group_id']);
                            
                            if ($isGroupPost) {
                                $postUrl = BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#group-post-' . $postId;
                            } else {
                                $postUrl = BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#personal-post-' . $postId;
                            }
                            ?>
                            <div class="feed" data-post-id="<?php echo (int)$post['post_id']; ?>" data-post-content="<?php echo $postContentForAttr; ?>">
                                <div class="head">
                                    <div class="user">
                                        <a class="profile-picture" href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)($post['author_id'] ?? $post['user_id']); ?>">
                                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Profile">
                                        </a>
                                        <div class="info">
                                            <h3>
                                                <a href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)($post['author_id'] ?? $post['user_id']); ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation();"><?php echo htmlspecialchars($displayName); ?></a>
                                                <?php if (!empty($post['group_id']) && !empty($post['group_name'])): ?>
                                                    <span class="group-indicator" style="font-weight: normal; color: var(--color-gray); font-size: 0.9em;">
                                                        <i class="uil uil-angle-right"></i>
                                                        <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$post['group_id']; ?>" class="group-link" style="color: inherit; text-decoration: none; font-weight: 600;" onclick="event.stopPropagation();">
                                                            <?php echo htmlspecialchars($post['group_name']); ?>
                                                        </a>
                                                    </span>
                                                <?php endif; ?>
                                            </h3>
                                            <small>
                                                <?php if (!empty($post['group_id'])): ?>
                                                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$post['group_id']; ?>#post-<?php echo (int)$post['post_id']; ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation();">
                                                        <?php echo htmlspecialchars($post['created_at'] ?? ''); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)($post['author_id'] ?? $post['user_id']); ?>#post-<?php echo (int)$post['post_id']; ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation();">
                                                        <?php echo htmlspecialchars($post['created_at'] ?? ''); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php if ($isOwner): ?>
                                        <div class="post-menu">
                                            <button class="menu-trigger" aria-label="Post menu"><i class="uil uil-ellipsis-h"></i></button>
                                            <div class="menu">
                                                <button class="menu-item edit-post" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                                    <i class="uil uil-edit"></i> Edit
                                                </button>
                                                <button class="menu-item delete-post" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                                    <i class="uil uil-trash-alt"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <i class="uil uil-ellipsis-h"></i>
                                    <?php endif; ?>
                                </div>

                                <?php 
                                $isGroupPost = !empty($post['group_id']);
                                $postType = $isGroupPost ? ($post['group_post_type'] ?? 'discussion') : ($post['post_type'] ?? 'text');
                                $postMetadata = $post['metadata'] ?? [];
                                ?>

                                <?php if ($isGroupPost): ?>
                                    <!-- Group Post Rendering Logic -->
                                    <div class="group-post-content" style="margin-bottom: 1rem;">
                                        <?php if ($postType === 'discussion'): ?>
                                            <?php if (!empty($post['content'])): ?>
                                                <div class="caption" style="margin-bottom: 1rem;">
                                                    <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($post['image_url'])): ?>
                                                <?php $postImage = MediaHelper::resolveMediaPath($post['image_url'], ''); ?>
                                                <div class="photo post-image">
                                                    <img src="<?php echo htmlspecialchars($postImage); ?>" alt="Post image">
                                                </div>
                                            <?php endif; ?>
                                        
                                        <?php elseif ($postType === 'question'): ?>
                                            <div class="question-content">
                                                <?php if (!empty($postMetadata['category'])): ?>
                                                    <span class="question-category" style="background: var(--color-light); padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.8rem; color: var(--color-primary); margin-bottom: 0.5rem; display: inline-block;"><?php echo htmlspecialchars($postMetadata['category']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($post['content'])): ?>
                                                    <p class="post-text" style="font-weight: 500; font-size: 1.1rem; margin-bottom: 0.5rem;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($post['image_url'])): ?>
                                                <?php $postImage = MediaHelper::resolveMediaPath($post['image_url'], ''); ?>
                                                <div class="photo post-image">
                                                    <img src="<?php echo htmlspecialchars($postImage); ?>" alt="Post image">
                                                </div>
                                            <?php endif; ?>

                                        <?php elseif ($postType === 'resource'): ?>
                                            <?php
                                                $resourceTypeLabel = $postMetadata['resource_type'] ?? ($postMetadata['type'] ?? '');
                                                $resourceLink = $postMetadata['resource_link'] ?? ($postMetadata['link'] ?? '');
                                                $resourceDownloadUrl = !empty($postMetadata['file_path']) ? BASE_PATH . ltrim($postMetadata['file_path'], '/') : '';
                                            ?>
                                            <div class="resource-content" style="background: var(--color-light); padding: 1rem; border-radius: var(--card-border-radius); margin-bottom: 1rem;">
                                                <h3 class="resource-title" style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($postMetadata['title'] ?? 'Untitled Resource'); ?></h3>
                                                <?php if (!empty($resourceTypeLabel)): ?>
                                                    <span class="resource-type-label" style="background: var(--color-primary); color: white; padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.8rem; margin-bottom: 0.5rem; display: inline-block;"><?php echo htmlspecialchars($resourceTypeLabel); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($post['content'])): ?>
                                                    <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                                <?php endif; ?>
                                                <div class="resource-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                                    <?php if (!empty($resourceDownloadUrl)): ?>
                                                        <a href="<?php echo htmlspecialchars($resourceDownloadUrl); ?>" class="btn btn-primary" download target="_blank" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="event.stopPropagation();">
                                                            <i class="uil uil-download-alt"></i> Download
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($resourceLink)): ?>
                                                        <a href="<?php echo htmlspecialchars($resourceLink); ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="event.stopPropagation();">
                                                            <i class="uil uil-external-link-alt"></i> Open Link
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
                                                <div class="poll-options" data-post-id="<?php echo (int)$post['post_id']; ?>">
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
                                                <div class="poll-footer" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                                    <button type="button" class="poll-total-votes" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                                        <i class="uil uil-users-alt"></i>
                                                        <span><?php echo $totalVotes; ?> total vote<?php echo $totalVotes === 1 ? '' : 's'; ?></span>
                                                    </button>
                                                    <?php if (!empty($postMetadata['duration'])): ?>
                                                        <span class="poll-duration">Ends in <?php echo (int)$postMetadata['duration']; ?> days</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="poll-voters-panel" id="poll-voters-<?php echo (int)$post['post_id']; ?>" data-post-id="<?php echo (int)$post['post_id']; ?>" hidden>
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
                                                <div class="event-content-layout">
                                                    <div class="event-content-main">
                                                        <h3 class="event-title"><?php echo htmlspecialchars($postMetadata['title'] ?? ($post['event_title'] ?? 'Untitled Event')); ?></h3>
                                                        <?php
                                                            $eventDescription = trim((string)($postMetadata['description'] ?? ($post['content'] ?? '')));
                                                        ?>
                                                        <?php if ($eventDescription !== ''): ?>
                                                            <p class="post-text"><?php echo nl2br(htmlspecialchars($eventDescription)); ?></p>
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

                                                    <?php $eventImage = !empty($post['image_url']) ? MediaHelper::resolveMediaPath($post['image_url'], '') : ''; ?>
                                                    <?php if (!empty($eventImage)): ?>
                                                        <div class="event-side-image">
                                                            <img src="<?php echo htmlspecialchars($eventImage); ?>" alt="Event image">
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
                                <?php else: ?>
                                    <?php if (!empty($post['content'])): ?>
                                        <div class="caption compact-caption">
                                            <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($post['image_url'])): ?>
                                        <?php $postImage = MediaHelper::resolveMediaPath($post['image_url'], ''); ?>
                                        <div class="photo post-image">
                                            <img src="<?php echo htmlspecialchars($postImage); ?>" alt="Post image" onerror="this.style.display='none'; console.log('Failed to load image: <?php echo htmlspecialchars($post['image_url']); ?>');">
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div class="action-buttons">
                                    <div class="interaction-buttons">
                                        <?php
                                        $upClass = ($post['user_vote'] ?? '') === 'upvote' ? 'liked' : '';
                                        $downClass = ($post['user_vote'] ?? '') === 'downvote' ? 'liked' : '';
                                        ?>
                                        <div class="interaction-item upvote-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                            <i class="uil uil-arrow-up <?php echo $upClass; ?>" data-vote-type="upvote"></i>
                                            <span class="interaction-count"><?php echo (int)($post['upvote_count'] ?? 0); ?></span>
                                        </div>
                                        <div class="interaction-item downvote-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                            <i class="uil uil-arrow-down <?php echo $downClass; ?>" data-vote-type="downvote"></i>
                                            <span class="interaction-count"><?php echo (int)($post['downvote_count'] ?? 0); ?></span>
                                        </div>
                                    </div>
                                    <div class="comments-side">
                                        <button type="button" class="question-answer-link-btn load-comments-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                            <i class="uil uil-comment"></i>
                                            <?php echo (int)($post['comment_count'] ?? 0); ?> comments
                                        </button>
                                    </div>
                                    <div class="interaction-item bookmark-item">
                                        <button type="button" class="bookmark-btn <?php echo !empty($post['is_bookmarked']) ? 'bookmarked' : ''; ?>" data-post-id="<?php echo (int)$post['post_id']; ?>" aria-label="Save post">
                                            <i class="<?php echo !empty($post['is_bookmarked']) ? 'uis uis-bookmark bookmarked' : 'uil uil-bookmark'; ?>" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <?php if (!$isOwner): ?>
                                        <div class="interaction-item report-item">
                                            <button type="button"
                                                class="report-trigger"
                                                data-report-type="post"
                                                data-target-id="<?php echo (int)$post['post_id']; ?>"
                                                data-target-label="<?php echo htmlspecialchars($reportLabel, ENT_QUOTES); ?>">
                                                <i class="uil uil-exclamation-circle"></i>
                                                <span>Report</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>



                                <div id="comments-post-<?php echo (int)$post['post_id']; ?>" class="comment-section" data-post-id="<?php echo (int)$post['post_id']; ?>">
                                    <div class="comment-header">
                                        <h3>Comments</h3>
                                        <button class="close-comments" type="button">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    <div class="comments-container" id="comments-container-<?php echo (int)$post['post_id']; ?>">
                                        <div class="comments-loading">Click to load comments</div>
                                    </div>

                                    <div class="add-comment-form">
                                        <div class="comment-input-container">
                                            <?php
                                            $currentUserAvatar = MediaHelper::resolveMediaPath($currentUser['profile_picture'] ?? '', 'uploads/user_dp/default.png');
                                            ?>
                                            <img src="<?php echo htmlspecialchars($currentUserAvatar); ?>" alt="Your Avatar" class="current-user-avatar">
                                            <div class="comment-input-wrapper">
                                                <textarea class="comment-input" placeholder="Write a comment..." data-post-id="<?php echo (int)$post['post_id']; ?>"></textarea>
                                                <button type="button" class="comment-submit-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">Post Comment</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="feed">
                            <div class="caption"><p>No posts yet.</p></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right">
                <!-- Trending Hashtags Section -->
                <div class="trending-section">
                    <div class="section-header">
                        <h4>Trending Hashtags</h4>
                    </div>
                    <div class="trending-list">
                        <?php if (!empty($trendingHashtags)): ?>
                            <?php foreach ($trendingHashtags as $tag): ?>
                                <div class="trending-item">
                                    <div class="trending-content">
                                        <span class="trending-rank"><?php echo $tag['rank']; ?></span>
                                        <div class="trending-details">
                                            <h5>#<?php echo htmlspecialchars($tag['hashtag']); ?></h5>
                                            <p class="post-count"><?php echo $tag['count']; ?> posts</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No trending hashtags.</p>
                        <?php endif; ?>
                    </div>
                </div>
        
                <!-- Popular Groups Section -->
                <div class="suggested-section">
                    <div class="section-header">
                        <h4>Popular Groups</h4>
                    </div>
                    <div class="creator-list">
                        <?php if (!empty($popularGroups)): ?>
                            <?php foreach ($popularGroups as $group): ?>
                                <div class="creator-card" data-group-id="<?php echo (int)$group['group_id']; ?>">
                                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$group['group_id']; ?>" 
                                       class="creator-info" style="text-decoration:none;color:inherit;">
                                        <img src="<?php echo htmlspecialchars($group['display_picture'] ?? BASE_PATH . 'images/default_group.png'); ?>" 
                                             class="creator-avatar" alt="<?php echo htmlspecialchars($group['name']); ?>">
                                        <div class="creator-details">
                                            <h5><?php echo htmlspecialchars($group['name']); ?></h5>
                                            <p class="creator-bio"><?php echo (int)$group['member_count']; ?> members</p>
                                        </div>
                                    </a>

                                    <?php $isMember = !empty($group['is_member']); ?>
                                    <button
                                        class="follow-btn <?php echo $isMember ? 'followed' : ''; ?>"
                                        data-group-id="<?php echo (int)$group['group_id']; ?>"
                                        data-state="<?php echo $isMember ? 'joined' : 'idle'; ?>"
                                    >
                                        <?php echo $isMember ? 'Joined' : 'Join'; ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No popular groups found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <div id="editPostModal" class="post-modal" role="dialog" aria-modal="true" aria-labelledby="editPostTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editPostTitle">Edit Post</h3>
                <button class="close-modal edit-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="editPostContent">Content</label>
                    <textarea id="editPostContent" rows="5" placeholder="Update your post..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary cancel-edit">Cancel</button>
                <button class="btn btn-primary save-edit" disabled>Save</button>
            </div>
        </div>
    </div>

    <script src="./js/calender.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/poll.js"></script>
    <script src="./js/bookmark.js"></script>
    <script src="./js/report.js"></script>

</body>
</html>