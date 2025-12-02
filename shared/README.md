## Database upgrade for group posts

The backend now expects three schema additions when working with group-specific posts and poll votes. Run the check/apply steps below against every environment (local, staging, production) before testing the new UI.

### Required schema
- `Post.group_post_type` – `ENUM('discussion','question','resource','poll','event','assignment')` stored right after `author_id`
- `Post.metadata` – `JSON NULL`, used to store poll answers, attachments, etc.
- `GroupPostPollVote` – stores one vote per (`post_id`,`user_id`) for poll-type group posts

### How to verify
```sql
SHOW COLUMNS FROM Post LIKE 'group_post_type';
SHOW COLUMNS FROM Post LIKE 'metadata';
SHOW TABLES LIKE 'GroupPostPollVote';
```
All three statements should return a row. If any return empty results, apply the migration.

### Apply the migration (Windows PowerShell example)
```powershell
mysql.exe -u <db_user> -p<db_password> <db_name> < "D:\\Downloads\\Hanthana-new-views (1)\\Hanthana-new-views\\DB\\migrations\\2025_11_17_group_post_columns.sql"
```
Notes:
- Keep the path quoted because of the space in `Hanthana-new-views (1)`.
- When your password contains special characters, omit it from the command (`-p`) and type it interactively when prompted.
- phpMyAdmin / MySQL Workbench users can simply open the migration file and execute it there instead of using the CLI.

### Post-migration validation
```sql
SELECT group_post_type, COUNT(*) AS rows FROM Post WHERE is_group_post = 1 GROUP BY group_post_type;
SELECT COUNT(*) FROM GroupPostPollVote;
```
The first query ensures older group posts were backfilled to `discussion`/`event`/`poll` automatically. The second confirms the new poll vote table is ready (0 is fine on fresh installs).

Once these checks pass, the UI should start rendering the richer card layouts because `GroupPostModel` will detect the new columns and hydrate the metadata JSON.
