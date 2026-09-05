# Augustus Media Backend Task

Laravel API for a high-traffic social feed similar to Twitter or Instagram.

The implementation focuses on the required domain only: users can register, login, follow other users, create/delete posts, like/unlike posts, and fetch a newest-first feed from followed users. The feed avoids N+1 queries, uses cursor pagination, caches hot reads in Redis, and dispatches follower notifications through a queue.

## Stack

- Laravel 12, PHP 8.3 FPM
- MySQL 8.4
- Redis for cache and queue
- Laravel Sanctum bearer tokens
- Nginx reverse proxy
- Laravel Reverb for WebSocket broadcasting
- Docker Compose with app, nginx, mysql, redis, queue, reverb, and DbGate services

## Quick Start

```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed --force
```

On Windows PowerShell, replace the first command with:

```powershell
Copy-Item .env.example .env
```

The API runs at:

```text
http://localhost:8080
```

Local database UI:

```text
DbGate: http://localhost:8081
```

Local Reverb WebSocket endpoint:

```text
ws://localhost:6001
```

DbGate login:

```text
username: augustus
password: secret
```

DbGate includes preconfigured connections for both MySQL and Redis.

MySQL credentials:

```text
server: mysql
username: augustus
password: secret
database: augustus
```

Redis connection:

```text
server: redis
port: 6379
```

Default seed credentials:

```text
demo@example.com / password
augustus-news@example.com / password
```

## Postman

Import this file into Postman:

```text
postman/Augustus Media Feed API.postman_collection.json
```

No separate Postman environment is required. The collection stores `base_url`, `token`, `post_id`, `cursor`, and other reusable values in collection variables. Run `Auth / Login Demo` to store the bearer token automatically.

## Tests

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
```

The PHPUnit suite uses SQLite in-memory, array cache, and sync queue mode so tests are isolated from the local MySQL seed.

## Seed Data

The seeder is configurable through `config/seeding.php`, with `.env` overrides for local smoke tests and larger query-plan checks.

```bash
docker compose exec -e SEED_USERS=10000 -e SEED_POSTS=100000 -e SEED_FOLLOWS=200000 -e SEED_LIKES=500000 app php artisan db:seed --force
```

Defaults:

```text
SEED_USERS=10000
SEED_POSTS=100000
SEED_FOLLOWS=200000
SEED_LIKES=500000
SEED_CHUNK_SIZE=1000
```

The seed creates a demo user, a high-followed "Augustus News" user, skewed follow data, and a viral post so feed and like queries have realistic hot rows.

## API

Unauthenticated:

```text
POST /register
POST /login
GET  /posts/{post}
```

Authenticated with `Authorization: Bearer <token>`:

```text
GET    /feed
POST   /follow/{user}
DELETE /follow/{user}
POST   /posts
DELETE /posts/{post}
POST   /posts/{post}/like
DELETE /posts/{post}/like
GET    /user
```

Delete endpoints return a JSON envelope with `success: true` instead of an empty `204` response, so clients can handle every controller response the same way.

`GET /feed` accepts:

```text
per_page: 1..50, default 20
cursor: returned by meta.next_cursor or meta.previous_cursor
```

Feed pagination defaults, cache TTLs, notification chunk size, retry count, and broadcast toggle live in `config/feed.php`.

Successful controller responses use the same envelope:

```json
{
  "success": true,
  "message": "Feed fetched successfully.",
  "data": {},
  "meta": {}
}
```

Validation, auth, authorization, and not-found API errors use the same response family with `success: false` and an `errors` object when field errors exist.

`likes_count` is stored on the post row and kept current by a `LikeObserver`. `is_liked` is added for the current viewer with one batched lookup for the returned page, not with one query per post.

Example feed response:

```json
{
  "success": true,
  "message": "Feed fetched successfully.",
  "data": [
    {
      "id": 1,
      "content": "A fast-moving regional story is gathering a huge response across the network.",
      "author": {
        "id": 2,
        "name": "Augustus News"
      },
      "likes_count": 10000,
      "is_liked": true,
      "created_at": "2026-09-03T21:08:13.000000Z"
    }
  ],
  "meta": {
    "per_page": 20,
    "next_cursor": "eyJjcmVhdGVkX2F0Ijoi...",
    "previous_cursor": null,
    "has_more_pages": true
  }
}
```

## Schema and Indexes

`users`

- Required fields only: `id`, `name`, `email`, `password`, timestamps.
- Unique index on `email`.

`posts`

- `id`, `user_id`, `content`, `likes_count`, timestamps.
- Foreign key `user_id -> users.id`.
- Composite index `(user_id, created_at, id)` supports fetching posts for followed authors in reverse chronological order.
- `likes_count` is denormalized to avoid counting likes during feed reads.

`follows`

- `follower_id`, `followed_id`, `created_at`.
- Composite primary key `(follower_id, followed_id)` prevents duplicate follows and supports "who do I follow?" lookups.
- Reverse index `(followed_id, follower_id)` supports follower notification fan-out.

`likes`

- `post_id`, `user_id`, `created_at`.
- Composite primary key `(post_id, user_id)` prevents duplicate likes and supports deleting or checking one user's like for one post.
- Composite index `(user_id, post_id)` supports the feed page lookup for "which of these posts did the current user like?"

`notifications`

- Laravel database notification records written asynchronously by the queue worker.

## Feed Design

This version uses fan-out on read and avoids a large join in the application query by filtering posts with a followed-users subquery:

```sql
SELECT posts.*
FROM posts
WHERE posts.user_id IN (
    SELECT follows.followed_id
    FROM follows
    WHERE follows.follower_id = ?
)
ORDER BY posts.created_at DESC, posts.id DESC
LIMIT ?
```

The API uses cursor pagination instead of offset pagination. This avoids deep-page offset scans and remains stable while new posts are inserted.

N+1 prevention:

- Authors are eager loaded with `author:id,name`.
- `likes_count` is read directly from `posts.likes_count`.
- The current user's liked state is loaded with one `likes where user_id = ? and post_id in (...)` query for the page.

## Caching

Redis cache is used for:

- First feed page per user and page size: `feed:first-page:user:{id}:per-page:{n}:v{version}`, 30 second TTL by default.

Invalidation:

- The first page response cache covers the hottest read path without maintaining one Redis key per post.
- `like` and `unlike` forget the current user's first feed page because `is_liked` changes for that viewer.
- `follow` and `unfollow` forget the current user's first feed page because feed membership changes.
- New posts do not invalidate every follower's feed cache. At high scale that fan-out invalidation is expensive, so this version uses a short TTL unless a future fan-out-on-write feed table is introduced.

## Queue Notifications

Post creation dispatches `NotifyFollowersOfNewPost` after the post row is created.

The job:

- Reads follower ids in chunks of 1000.
- Inserts database notifications in bulk.
- Uses deterministic notification ids derived from `(post_id, follower_id)` so retries are idempotent.
- Uses the `(followed_id, follower_id)` index to avoid scanning the full follows table.
- Broadcasts a Reverb event only when `feed.notifications.broadcast_enabled` is `true`.

Realtime broadcasting is off by default:

```text
NOTIFICATION_BROADCAST_ENABLED=false
```

When it is enabled, the job stores the database notification first, then dispatches `NewPostNotificationBroadcasted`.

The event:

- Broadcasts once per follower chunk.
- Sends the same payload to each private channel `users.{id}.notifications`.
- Uses Laravel channel authorization in `routes/channels.php`, so a user can only subscribe to their own notification channel.

The `reverb` Docker service runs Laravel Reverb:

```text
ws://localhost:6001
```

For a separate Angular frontend, install Echo and Pusher JS:

```bash
npm install laravel-echo pusher-js
```

Example Angular-side setup:

```ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

(window as any).Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'reverb',
  key: 'augustus-local-key',
  wsHost: 'localhost',
  wsPort: 6001,
  forceTLS: false,
  enabledTransports: ['ws'],
  authEndpoint: 'http://localhost:8080/broadcasting/auth',
  auth: {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    },
  },
});

echo.private(`users.${currentUser.id}.notifications`)
  .listen('.new-post-notification', (notification) => {
    console.log(notification);
  });
```

`/broadcasting/auth` is protected by `auth:sanctum`, so Angular must send the same bearer token used for the API.

When multiple Reverb instances are needed, `REVERB_SCALING_ENABLED=true` makes Reverb use Redis Pub/Sub internally to share connections and broadcasts across those instances.

## Local Query Plan Notes

Measured on MySQL 8.4 with the default seed: 10,000 users, 100,000 posts, 200,000 follows, 500,000 likes.

Feed first page for demo user:

```text
follows PRIMARY lookup: follower_id=1, 266 rows
posts index lookup: posts_user_id_created_at_id_index, 12,390 candidate posts
top-N sort by posts.created_at desc, posts.id desc
actual time: about 24.5 ms
```

Cursor page after the first page:

```text
same join path with index condition on created_at/id cursor
actual time: about 31.1 ms
```

Like summary attributes for 20 feed posts:

```text
likes_count is already on the posts row
is_liked uses one indexed likes lookup for the 20 post ids
no Redis round trip and no per-post query
```

These numbers are good enough for the assignment implementation. The feed query still performs a top-N sort across candidate posts from followed users, which is the expected trade-off for fan-out on read.

## Trade-offs

- No microservices: the task is about relational feed design, query planning, cache strategy, and Laravel code quality.
- No precomputed feed table in the first version: fan-out on read keeps writes simple and avoids storage amplification before it is proven necessary.
- No per-post like-count cache: the extra Redis reads/writes are not worth it for a cursor page capped at 50 posts, especially while the first feed page is already cached.
- Denormalized `likes_count` on `posts` is used deliberately to keep feed reads fast. Very hot posts may need approximate counters or buffered counter updates later.
- No extra database indexes beyond the measured access paths: redundant indexes slow writes and increase storage.
- No cache entry for every cursor page: the first page carries the highest repeated-read value.
- No complex distributed cache locks yet: one cached first page per user is enough for this scope.

## Performance Challenge: 5M Users and 500k Posts/Day

For the stated 5M users and 500k posts/day target, this code is a clean starting point rather than the final production architecture.

How feed generation should scale:

- Keep the current fan-out-on-read query for the first version because it is simple, consistent, and works well for normal follow counts.
- Move to a hybrid feed once query cost grows: fan-out-on-write normal authors into a `feed_items` table or Redis sorted set, but keep celebrity/high-follower authors on fan-out-on-read and merge them into the page at read time.
- For a `feed_items` table, use `(user_id, created_at, post_id)` as the main read index. Queue workers would fill it from the follower chunks already supported by `(followed_id, follower_id)`.
- Keep cursor pagination everywhere. Offset pagination should not be used for deep pages because it forces the database to scan and discard rows.
- Keep feed reads on read replicas once write traffic grows.

How to avoid slow queries:

- Select only the fields needed for API responses.
- Keep author eager loading, and never resolve authors inside a loop.
- Keep `is_liked` as one indexed lookup per returned page.
- Keep like counts on the `posts` row instead of running `COUNT(*)` for every feed page.
- Use `EXPLAIN ANALYZE` on the feed query with realistic follow/post distributions, not only small local data.

Indexes to keep or add:

- Current: `posts(user_id, created_at, id)` for followed-author post lookup.
- Current: `follows(follower_id, followed_id)` primary key for feed membership.
- Current: `follows(followed_id, follower_id)` for notification and future feed fan-out.
- Current: `likes(post_id, user_id)` primary key for uniqueness.
- Current: `likes(user_id, post_id)` for current-viewer liked-state lookup.
- Future feed table: `feed_items(user_id, created_at, post_id)`.
- Future notification listing endpoint: `notifications(notifiable_type, notifiable_id, read_at, created_at)`.

When caching should be used:

- Cache the first feed page because it is the hottest repeated read.
- Keep the TTL short while using fan-out-on-read, so new posts and like counts are not stale for long.
- Do not cache every cursor page by default. Long-tail pages have lower reuse and create avoidable Redis churn.
- Add per-user feed materialization cache only when read traffic proves the database query is the bottleneck.
- Avoid invalidating every follower's cache on every new post; that turns one write into millions of cache operations for large accounts.

Other scale steps:

- Add read replicas for feed and post reads.
- Move Redis to a managed cluster and add observability around hit rate and hot keys.
- Split queue workers by priority, with separate lanes for notifications and any future feed materialization work.
- Partition or shard high-growth tables such as `posts`, `likes`, and `notifications` by time or id range when single-node indexes no longer fit memory.
- Add backpressure and rate limits for high-volume authors and like storms.
- Store approximate counters for extremely hot posts, periodically reconciled from the authoritative likes table.
