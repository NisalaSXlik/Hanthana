<?php
require_once __DIR__ . '/../core/Database.php';

class ChannelModel {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function searchJoinedGroupChannels(int $userId, string $term, int $limit = 10) {
        $sql =
           "SELECT
                c.channel_id AS friend_user_id,
                c.name /*c.description,*/ AS username,
                c.display_picture AS profile_picture,
                c.name AS full_name,
                'group' AS conversation_type
            FROM Channel c
            INNER JOIN GroupMember gm ON c.group_id = gm.group_id
                WHERE gm.user_id = :user
                AND gm.status = 'active'
                AND c.name LIKE :term
            ORDER BY c.name ASC
            LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $like = '%' . $term . '%';
        $stmt->bindValue(':user', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':term', $like, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>