# Augustus Media Backend Task

Laravel API for a high-traffic social feed similar to Twitter or Instagram.

The implementation focuses on the required domain only: users can register, login, follow other users, create/delete posts, like/unlike posts, and fetch a newest-first feed from followed users. The feed avoids N+1 queries, uses cursor pagination, caches hot reads in Redis, and dispatches follower notifications through a queue.

## Stack

- Laravel 12, PHP 8.3 FPM
- MySQL 8.4
- Redis for cache and queue
- Laravel Sanctum bearer tokens
- Nginx reverse proxy
- Docker Compose with app, nginx, mysql, redis, and queue services

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

Default seed credentials:

```text
demo@example.com / password
augustus-news@example.com / password
```

## Postman

Import these files into Postman:

```text
postman/Augustus Media Feed API.postman_collection.json
postman/Augustus Local.postman_environment.json
```

Select the `Augustus Local` environment, then run `Auth / Login Demo` to store the bearer token automatically.

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

Feed pagination defaults and cache TTLs live in `config/feed.php`.

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

`likes_count` and `is_liked` are query attributes returned from the current database state through Eloquent `withCount` and `withExists`.

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

- `id`, `user_id`, `content`, timestamps.
- Foreign key `user_id -> users.id`.
- Composite index `(user_id, created_at, id)` supports fetching posts for followed authors in reverse chronological order.

`follows`

- `follower_id`, `followed_id`, `created_at`.
- Composite primary key `(follower_id, followed_id)` prevents duplicate follows and supports "who do I follow?" lookups.
- Reverse index `(followed_id, follower_id)` supports follower notification fan-out.

`likes`

- `post_id`, `user_id`, `created_at`.
- Composite primary key `(post_id, user_id)` prevents duplicate likes and supports batched like counts by post.
- MySQL's foreign key index on `user_id` is used for batched "did current user like these posts?" lookups.

`notifications`

- Laravel database notification records written asynchronously by the queue worker.

## Feed Design

This version uses fan-out on read:

```sql
SELECT posts.*
FROM posts
JOIN follows ON follows.followed_id = posts.user_id
WHERE follows.follower_id = ?
ORDER BY posts.created_at DESC, posts.id DESC
LIMIT ?
```

The API uses cursor pagination instead of offset pagination. This avoids deep-page offset scans and remains stable while new posts are inserted.

N+1 prevention:

- Authors are eager loaded with `author:id,name`.
- Like counts and the current user's liked state are selected with Eloquent aggregate/existence attributes.

## Caching

Redis cache is used for:

- Default first feed page: `feed:first-page:user:{id}:v1`, 30 second TTL.

Invalidation:

- Like summaries are selected with the feed query. The first page response cache covers the hottest read path without maintaining one Redis key per post.
- `like` and `unlike` forget the current user's first feed page because `is_liked` changes.
- `follow` and `unfollow` forget the current user's first feed page because membership changes.
- New posts do not invalidate every follower's feed cache. At high scale that becomes expensive, so follower feeds rely on the short TTL unless a future fan-out-on-write feed table is introduced.

## Queue Notifications

Post creation dispatches `NotifyFollowersOfNewPost` with `afterCommit()` so followers are notified only after the post transaction commits.

The job:

- Reads follower ids in chunks of 1000.
- Inserts database notifications in bulk.
- Uses deterministic notification ids derived from `(post_id, follower_id)` so retries are idempotent.
- Uses the `(followed_id, follower_id)` index to avoid scanning the full follows table.

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
selected through indexed `likedBy` count and exists subqueries
no Redis round trip and no PHP-side like summary loop
```

These numbers are good enough for the assignment implementation. The feed query still performs a top-N sort across candidate posts from followed users, which is the expected trade-off for fan-out on read.

## Trade-offs

- No microservices: the task is about relational feed design, query planning, cache strategy, and Laravel code quality.
- No precomputed feed table in the first version: fan-out on read keeps writes simple and avoids storage amplification before it is proven necessary.
- No per-post like-count cache: the extra Redis reads/writes are not worth it for a cursor page capped at 50 posts, especially while the first feed page is already cached.
- No denormalized `likes_count` on `posts`: it would turn viral posts into hot write rows.
- No extra database indexes beyond the measured access paths: redundant indexes slow writes and increase storage.
- No cache entry for every cursor page: the first page carries the highest repeated-read value.
- No complex distributed cache locks yet: one cached first page per user is enough for this scope.

## Scaling Path

For the stated 5M users and 500k posts/day target, this code is a clean starting point rather than the final production architecture.

Next steps at scale:

- Add read replicas for feed and post reads.
- Move Redis to a managed cluster and add observability around hit rate and hot keys.
- Split queue workers by priority, with separate lanes for notifications and any future feed materialization work.
- Introduce a hybrid feed model: fan-out on write for normal users into a `feed_items` table or Redis sorted set, while keeping fan-out on read for celebrity accounts with very high follower counts.
- Partition or shard high-growth tables such as `posts`, `likes`, and `notifications` by time or id range when single-node indexes no longer fit memory.
- Add backpressure and rate limits for high-volume authors and like storms.
- Store approximate counters for extremely hot posts, periodically reconciled from the authoritative likes table.
