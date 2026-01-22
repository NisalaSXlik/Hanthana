<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($displayName !== '' ? $displayName : $displayHandle); ?> - Profile</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/groupprofileview.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/events-page.css">
    <link rel="stylesheet" href="./css/userprofileview.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <div class="profile-header">
                    <div class="profile-cover">
                        <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($profileUser['cover_photo'] ?? '', 'uploads/user_cover/default.png')); ?>" alt="Profile Cover" id="profileCoverImage">
                        <?php if ($isOwner): ?>
                        <button type="button" class="btn btn-primary edit-cover-btn" id="triggerEditCover">
                            <i class="uil uil-camera"></i> Edit Cover
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-dp-container">
                            <div class="profile-dp">
                                <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($profileUser['profile_picture'] ?? '', 'uploads/user_dp/default.png')); ?>" alt="Profile Picture" id="profileAvatarImage">
                                <?php if ($isOwner): ?>
                                <button type="button" class="edit-dp-btn" id="triggerEditAvatar">
                                    <i class="uil uil-camera"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-details">
                            <p class="profile-name"><?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Unknown User'); ?></p>
                            <?php if ($displayHandle !== ''): ?>
                                <p class="profile-handle"><?php echo htmlspecialchars($displayHandle); ?></p>
                            <?php endif; ?>
                            <div class="profile-stats">
                                <button type="button" class="stat stat-link" data-tab-target="posts" aria-label="View posts section">
                                    <strong><?php echo $totalPostsCount; ?></strong>
                                    <span>Posts</span>
                                </button>
                                <button type="button" class="stat stat-link" data-friend-count-trigger>
                                    <strong data-friend-count="<?php echo $friendsCount; ?>"><?php echo $friendsCount; ?></strong>
                                    <span>Friends</span>
                                </button>
                                <button type="button" class="stat stat-link" data-tab-target="photos" aria-label="View photos section">
                                    <strong><?php echo count($photoPosts); ?></strong>
                                    <span>Photos</span>
                                </button>
                            </div>
                            <p class="profile-bio"><?php echo htmlspecialchars($bio); ?></p>
                            <div class="profile-meta">
                                <span><i class="uil uil-location-point"></i> <?php echo htmlspecialchars($location); ?></span>
                                <span><i class="uil uil-university"></i> <?php echo htmlspecialchars($university); ?></span>
                                <span><i class="uil uil-calendar-alt"></i> Joined <?php echo htmlspecialchars($joinedAt); ?></span>
                            </div>
                            <div class="profile-actions">
                                <?php if ($isOwner): ?>
                                    <button type="button" class="btn btn-primary" id="editProfileBtn"><i class="uil uil-edit"></i> Edit Profile</button>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="btn <?php echo $friendButtonVariant; ?> add-friend-btn"
                                        data-user-id="<?php echo $profileUserId; ?>"
                                        data-state="<?php echo htmlspecialchars($friendButtonState); ?>"
                                        <?php echo $friendButtonDisabled ? 'disabled' : ''; ?>
                                    >
                                        <i class="<?php echo htmlspecialchars($friendButtonIcon); ?>"></i>
                                        <span><?php echo htmlspecialchars($friendButtonLabel); ?></span>
                                    </button>
                                    <button class="btn btn-secondary" type="button"><i class="uil uil-message"></i> Message</button>
                                    <button
                                        type="button"
                                        class="btn report-trigger"
                                        data-report-type="user"
                                        data-target-id="<?php echo (int)$profileUserId; ?>"
                                        data-target-label="<?php echo htmlspecialchars('user ' . ($displayName !== '' ? $displayName : $displayHandle), ENT_QUOTES); ?>">
                                        <i class="uil uil-exclamation-circle"></i>
                                        Report User
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Privacy Indicator -->
                            <?php if (!$isOwner): ?>
                            <div class="privacy-indicator" style="margin-top: 1rem; text-align: center;">
                                <?php if ($profileVisibility === 'only_me'): ?>
                                    <div class="privacy-badge" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: var(--color-light); border-radius: 2rem; color: var(--color-gray);">
                                        <i class="uil uil-lock"></i>
                                        <span>Private Profile</span>
                                    </div>
                                <?php elseif ($profileVisibility === 'friends_only'): ?>
                                    <div class="privacy-badge" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: var(--color-light); border-radius: 2rem; color: var(--color-gray);">
                                        <i class="uil uil-users-alt"></i>
                                        <span>Friends Only</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profile-tabs">
                        <ul>
                            <li class="active"><a href="#" data-tab="posts">Personal Posts</a></li>
                            <li><a href="#" data-tab="group-posts">Group Posts</a></li>
                            <li><a href="#" data-tab="about">About</a></li>
                            <li><a href="#" data-tab="photos">Photos</a></li>
                        </ul>
                    </div>
                </div>

                <div class="group-content">
                    <!-- Personal Posts Tab -->
                    <div class="tab-content active" id="tab-posts">
                        <?php if ($postsArePrivate && !$isOwner): ?>
                            <div class="private-posts-message" style="text-align: center; padding: 3rem; background: var(--color-light); border-radius: 1rem; margin: 2rem 0;">
                                <i class="uil uil-lock" style="font-size: 3rem; color: var(--color-gray); margin-bottom: 1rem;"></i>
                                <h3 style="color: var(--color-dark); margin-bottom: 0.5rem;">
                                    <?php if ($postVisibility === 'only_me'): ?>
                                        Posts are private
                                    <?php else: ?>
                                        Posts are for friends only
                                    <?php endif; ?>
                                </h3>
                                <p style="color: var(--color-gray);">
                                    <?php if ($postVisibility === 'only_me'): ?>
                                        This user's posts are set to private. You cannot view their posts.
                                    <?php else: ?>
                                        You need to be friends with this user to view their posts.
                                        <?php if ($canSendFriendRequest): ?>
                                            <br><button 
                                                type="button"
                                                class="btn btn-primary add-friend-btn" 
                                                data-user-id="<?php echo $profileUserId; ?>" 
                                                data-state="none"
                                                style="margin-top: 1rem;">
                                                <i class="uil uil-user-plus"></i>
                                                <span>Send Friend Request</span>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php elseif (!empty($personalPosts)): ?>
                            <div class="posts-grid" aria-hidden="false">
                                <?php foreach ($personalPosts as $index => $post): ?>
                                    <a href="#" class="post-grid-item" data-post-index="<?php echo $index; ?>" data-post-type="personal" title="View post">
                                        <?php if (!empty($post['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post <?php echo (int)$post['post_id']; ?>">
                                        <?php else: ?>
                                            <div class="post-placeholder">
                                                <i class="uil uil-file-info-alt"></i>
                                                <span><?php echo htmlspecialchars(mb_strimwidth(strip_tags($post['content'] ?? ''), 0, 80, '...')); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!$postsArePrivate || $isOwner): ?>
                            <div class="feed">
                                <div class="caption"><p>No personal posts yet.</p></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Group Posts Tab -->
                    <div class="tab-content" id="tab-group-posts">
                        <?php if ($postsArePrivate && !$isOwner): ?>
                            <div class="private-posts-message" style="text-align: center; padding: 3rem; background: var(--color-light); border-radius: 1rem; margin: 2rem 0;">
                                <i class="uil uil-lock" style="font-size: 3rem; color: var(--color-gray); margin-bottom: 1rem;"></i>
                                <h3 style="color: var(--color-dark); margin-bottom: 0.5rem;">Group Posts are Private</h3>
                                <p style="color: var(--color-gray)">You don't have permission to view this user's group posts.</p>
                            </div>
                        <?php elseif (!empty($groupPosts)): ?>
                            <div class="posts-grid" aria-hidden="false">
                                <?php foreach ($groupPosts as $post): ?>
                                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=view&id=<?php echo (int)$post['group_id']; ?>#post-<?php echo (int)$post['post_id']; ?>" class="post-grid-item" title="View in <?php echo htmlspecialchars($post['group_name'] ?? 'group'); ?>">
                                        <div class="post-group-badge" title="Posted in <?php echo htmlspecialchars($post['group_name'] ?? 'Group'); ?>">
                                            <i class="uil uil-users-alt"></i>
                                        </div>
                                        <?php if (!empty($post['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post <?php echo (int)$post['post_id']; ?>">
                                        <?php else: ?>
                                            <div class="post-placeholder">
                                                <i class="uil uil-file-info-alt"></i>
                                                <span><?php echo htmlspecialchars(mb_strimwidth(strip_tags($post['content'] ?? ''), 0, 80, '...')); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="feed">
                                <div class="caption"><p>No group posts yet.</p></div>
                            </div>
                        <?php endif; ?>
                    </div>



                    <!-- About Tab -->
                    <div class="tab-content" id="tab-about">
                        <div class="about-section">
                            <h3>About</h3>
                            <p><?php echo htmlspecialchars($bio); ?></p>
                        </div>
                        <div class="about-section">
                            <h3>Details</h3>
                            <ul class="detail-list">
                                <?php if ($email): ?>
                                    <li><i class="uil uil-envelope"></i> <?php echo htmlspecialchars($email); ?></li>
                                <?php endif; ?>
                                <?php if ($phone): ?>
                                    <li><i class="uil uil-phone"></i> <?php echo htmlspecialchars($phone); ?></li>
                                <?php endif; ?>
                                <?php if ($dob): ?>
                                    <li><i class="uil uil-gift"></i> Born on <?php echo htmlspecialchars($dob); ?></li>
                                <?php endif; ?>
                                <li><i class="uil uil-location-point"></i> <?php echo htmlspecialchars($location); ?></li>
                                <li><i class="uil uil-university"></i> <?php echo htmlspecialchars($university); ?></li>
                                <li><i class="uil uil-calendar-alt"></i> Member since <?php echo htmlspecialchars($joinedAt); ?></li>
                            </ul>
                        </div>
                        <?php if (!empty($interestTags)): ?>
                            <div class="about-section">
                                <h3>Interests</h3>
                                <div class="interest-tags">
                                    <?php foreach ($interestTags as $tag): ?>
                                        <?php if ($tag !== ''): ?>
                                            <span class="interest-tag"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Photos Tab -->
                    <div class="tab-content" id="tab-photos">
                        <?php if ($postsArePrivate && !$isOwner): ?>
                            <div class="private-posts-message" style="text-align: center; padding: 3rem; background: var(--color-light); border-radius: 1rem; margin: 2rem 0;">
                                <i class="uil uil-lock" style="font-size: 3rem; color: var(--color-gray); margin-bottom: 1rem;"></i>
                                <h3 style="color: var(--color-dark); margin-bottom: 0.5rem;">
                                    <?php if ($postVisibility === 'only_me'): ?>
                                        Photos are private
                                    <?php else: ?>
                                        Photos are for friends only
                                    <?php endif; ?>
                                </h3>
                                <p style="color: var(--color-gray);">
                                    <?php if ($postVisibility === 'only_me'): ?>
                                        This user's photos are set to private. You cannot view their photos.
                                    <?php else: ?>
                                        You need to be friends with this user to view their photos.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php elseif (!empty($photoPosts)): ?>
                            <div class="photo-grid">
                                <?php foreach ($photoPosts as $post): ?>
                                    <div class="photo-item">
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Photo from post <?php echo (int)$post['post_id']; ?>">
                                        <?php if (!empty($post['created_at'])): ?>
                                            <span><?php echo htmlspecialchars(date('M j, Y', strtotime($post['created_at']))); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-message">No photos yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="right profile-sidebar">
                <div class="group-details">
                    <h4>Profile Snapshot</h4>
                    <div class="detail-list">
                        <div class="detail-item">
                            <i class="uil uil-user"></i>
                            <span><?php echo htmlspecialchars($displayName !== '' ? $displayName : $displayHandle); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-location-point"></i>
                            <span><?php echo htmlspecialchars($location); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="uil uil-calendar-alt"></i>
                            <span>Member since <?php echo htmlspecialchars($joinedAt); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Groups Summary -->
                <div class="user-groups-summary">
                    <div class="heading">
                        <h4>Group Activity</h4>
                    </div>
                    <div class="groups-stats">
                        <div class="stat-item">
                            <i class="uil uil-users-alt"></i>
                            <div>
                                <strong><?php echo $joinedGroupsCount; ?></strong>
                                <span>Groups Joined</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="uil uil-file-alt"></i>
                            <div>
                                <strong><?php echo $groupPostCount; ?></strong>
                                <span>Group Posts</span>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($userGroups)): ?>
                        <div class="group-list">
                            <?php foreach (array_slice($userGroups, 0, 3) as $group): ?>
                                <a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=view&id=<?php echo (int)$group['group_id']; ?>" 
                                   class="group-card">
                                    <div class="group-icon">
                                        <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($group['group_photo'] ?? '', 'uploads/group_photos/default.png')); ?>" 
                                             alt="<?php echo htmlspecialchars($group['group_name']); ?>">
                                    </div>
                                    <div class="group-info">
                                        <h5><?php echo htmlspecialchars($group['group_name']); ?></h5>
                                        <p><?php echo (int)$group['member_count']; ?> members</p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($userGroups) > 3): ?>
                            <a href="#" class="btn btn-secondary view-all-groups" data-tab-target="group-posts">
                                View all <?php echo count($userGroups); ?> groups
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="top-collaborators">
                    <div class="heading">
                        <h4>Contact</h4>
                    </div>
                    <div class="creator-list">
                        <?php if ($email): ?>
                        <div class="creator-card">
                            <div class="creator-info">
                                <i class="uil uil-envelope"></i>
                                <div class="creator-details">
                                    <h5>Email</h5>
                                    <p class="creator-bio"><?php echo htmlspecialchars($email); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($phone): ?>
                        <div class="creator-card">
                            <div class="creator-info">
                                <i class="uil uil-phone"></i>
                                <div class="creator-details">
                                    <h5>Phone</h5>
                                    <p class="creator-bio"><?php echo htmlspecialchars($phone); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="creator-card friend-count-card" data-friend-count-trigger role="button" tabindex="0">
                            <div class="creator-info">
                                <i class="uil uil-users-alt"></i>
                                <div class="creator-details">
                                    <h5>Friends</h5>
                                    <p class="creator-bio" data-friend-count-label><?php echo $friendsCount; ?> total friends</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php if ($isOwner): ?>
        <input type="file" id="directAvatarInput" name="profile_picture" accept="image/*" style="display:none;" aria-hidden="true">
        <input type="file" id="directCoverInput" name="cover_photo" accept="image/*" style="display:none;" aria-hidden="true">
    <?php endif; ?>

    <?php if ($isOwner): ?>
    <div id="editProfileModal" class="profile-edit-modal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button type="button" class="modal-close" id="closeEditProfileModal" aria-label="Close">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <form id="editProfileForm" class="modal-body" enctype="multipart/form-data">
                <div id="profileFormMessage" class="form-message" style="display:none;"></div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstNameInput">First Name</label>
                        <input type="text" id="firstNameInput" name="first_name" maxlength="100" value="<?php echo htmlspecialchars($profileUser['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lastNameInput">Last Name</label>
                        <input type="text" id="lastNameInput" name="last_name" maxlength="100" value="<?php echo htmlspecialchars($profileUser['last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="usernameInput">Username</label>
                        <input type="text" id="usernameInput" name="username" maxlength="100" value="<?php echo htmlspecialchars($profileUser['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="emailInput">Email</label>
                        <input type="email" id="emailInput" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phoneInput">Phone Number</label>
                        <input type="text" id="phoneInput" name="phone_number" maxlength="15" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="dobInput">Date of Birth</label>
                        <input type="date" id="dobInput" name="date_of_birth" value="<?php echo htmlspecialchars($dobValue); ?>">
                    </div>
                    <div class="form-group">
                        <label for="locationInput">Location</label>
                        <input type="text" id="locationInput" name="location" maxlength="150" value="<?php echo htmlspecialchars($profileUser['location'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="universityInput">University</label>
                        <input type="text" id="universityInput" name="university" maxlength="150" value="<?php echo htmlspecialchars($profileUser['university'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="bioInput">Bio</label>
                    <textarea id="bioInput" name="bio" rows="3" maxlength="500"><?php echo htmlspecialchars($profileUser['bio'] ?? ''); ?></textarea>
                </div>
                <div class="media-upload-group">
                    <div class="media-field">
                        <label for="profilePictureInput">Profile Picture</label>
                        <div class="media-preview">
                            <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($profileUser['profile_picture'] ?? '', 'uploads/user_dp/default.png')); ?>" alt="Profile preview" id="profilePicturePreview">
                        </div>
                        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*">
                    </div>
                    <div class="media-field">
                        <label for="coverPhotoInput">Cover Photo</label>
                        <div class="media-preview cover">
                            <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($profileUser['cover_photo'] ?? '', 'uploads/user_cover/default.png')); ?>" alt="Cover preview" id="coverPhotoPreview">
                        </div>
                        <input type="file" id="coverPhotoInput" name="cover_photo" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelEditProfileBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="friends-modal" id="friendListModal" aria-hidden="true">
        <div class="friends-modal__content" role="dialog" aria-modal="true" aria-labelledby="friendListTitle">
            <div class="friends-modal__header">
                <h2 id="friendListTitle">Friends <span data-friend-list-count>(<?php echo $friendListCount; ?>)</span></h2>
                <button type="button" class="friends-modal__close" data-close-friends-modal aria-label="Close friend list">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <div class="friends-modal__body">
                <?php if ($hasMoreFriends && !empty($friendList)): ?>
                    <p class="friends-modal__note" data-friends-note>Showing first <?php echo count($friendList); ?> friends.</p>
                <?php else: ?>
                    <p class="friends-modal__note" data-friends-note style="display:none;">Showing first <?php echo count($friendList); ?> friends.</p>
                <?php endif; ?>

                <ul class="friends-modal__list" data-friends-list data-friend-list-limit="<?php echo $friendListLimit; ?>">
                    <?php foreach ($friendList as $friend): ?>
                        <?php
                            $friendUserId = (int)($friend['friend_user_id'] ?? 0);
                            $friendName = trim(($friend['first_name'] ?? '') . ' ' . ($friend['last_name'] ?? ''));
                            if ($friendName === '') {
                                $friendName = $friend['username'] ?? 'Unknown User';
                            }
                            $friendHandle = !empty($friend['username']) ? '@' . $friend['username'] : '';
                            $friendAvatar = MediaHelper::resolveMediaPath($friend['profile_picture'] ?? '', 'uploads/user_dp/default.png');
                            $friendProfileUrl = rtrim(BASE_PATH, '/') . '/index.php?controller=Profile&action=view&user_id=' . $friendUserId;
                        ?>
                        <li class="friends-modal__item" data-friend-user-id="<?php echo $friendUserId; ?>">
                            <a class="friends-modal__link" href="<?php echo htmlspecialchars($friendProfileUrl); ?>">
                                <img class="friends-modal__avatar" src="<?php echo htmlspecialchars($friendAvatar); ?>" alt="<?php echo htmlspecialchars($friendName); ?>">
                                <div class="friends-modal__info">
                                    <span class="friends-modal__name"><?php echo htmlspecialchars($friendName); ?></span>
                                    <?php if ($friendHandle !== ''): ?>
                                        <span class="friends-modal__handle"><?php echo htmlspecialchars($friendHandle); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p class="friends-modal__empty" data-friends-empty style="<?php echo empty($friendList) ? '' : 'display:none;'; ?>">No friends to show yet.</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/templates/chat-clean.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <!-- Instagram-Style Post Modal -->
    <div id="postViewModal" class="post-view-modal" aria-hidden="true">
        <div class="post-view-overlay"></div>
        <button class="post-view-close" aria-label="Close">
            <i class="uil uil-times"></i>
        </button>
        <button class="post-view-prev" aria-label="Previous post">
            <i class="uil uil-angle-left"></i>
        </button>
        <button class="post-view-next" aria-label="Next post">
            <i class="uil uil-angle-right"></i>
        </button>
        <div class="post-view-content">
            <div class="post-view-image">
                <img src="" alt="Post image" id="postViewImage">
                <div class="post-view-text-content" id="postViewTextContent"></div>
            </div>
            <div class="post-view-sidebar">
                <div class="post-view-header">
                    <div class="user">
                        <div class="profile-picture">
                            <img src="" alt="Profile" id="postViewAvatar">
                        </div>
                        <div class="info">
                            <h3 id="postViewUsername"></h3>
                            <small id="postViewDate"></small>
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
                <div class="post-view-comments" id="postViewCommentsPanel">
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

    <script>
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
        const VIEWER_USER_ID = <?php echo $viewerId; ?>;
        const PROFILE_USER_ID = <?php echo $profileUserId; ?>;
        const POSTS_ARE_PRIVATE = <?php echo $postsArePrivate ? 'true' : 'false'; ?>;
        const IS_OWNER = <?php echo $isOwner ? 'true' : 'false'; ?>;
        window.PERSONAL_POSTS = <?php echo json_encode($personalPosts ?? []); ?>;
        window.GROUP_POSTS = <?php echo json_encode($groupPosts ?? []); ?>;
    </script>
    <script id="profilePostPayload" type="application/json">
        <?php echo json_encode([
            'personal' => $personalPosts ?? [],
            'group' => $groupPosts ?? []
        ], JSON_UNESCAPED_SLASHES); ?>
    </script>
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
    <script src="./js/userprofileview.js"></script>
</body>
</html>