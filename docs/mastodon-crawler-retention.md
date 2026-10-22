# Mastodon crawler retention

`doctrine:migrations:migrate` creates two retention slots (`primary` and `secondary`). The migration deliberately does not contain a Mastodon status ID.

To bootstrap the known existing crawler post after migration, use the application database connection and set only the approved known ID:

```sql
UPDATE na_mastodon_announcement
SET current_status_id = '117464091562457705', updated_at = UTC_TIMESTAMP()
WHERE account = '<primary-or-secondary-slot>'; -- use the verified podcastindex.social account slot
```

The ID above is the current retained `voidzero` post on `podcastindex.social`; verify it before applying the bootstrap SQL. Do not populate a slot with an ID that was not explicitly verified as a crawler-owned status. The other slot can be bootstrapped the same way after its ID is confirmed.

Each crawler announcement (live or episode) publishes first. Only after Mastodon returns a status ID is that ID stored. The old stored ID is then deleted; IDs are deleted only from this table, so unrelated account posts are never selected. A failed publish leaves the previous ID untouched. A failed delete remains in `pending_deletion_ids` and is retried by the next crawler run or manually with:

```bash
bin/console mastodon:cleanup
```

The Mastodon application token needs `write:statuses` and `write:media` only. No read scope is required. The cleanup command treats HTTP 404 as already deleted.

The database row is locked while replacing the current ID, which serializes concurrent crawler invocations per account. The external publish and delete calls cannot participate in the database transaction: a crash after publishing but before persistence can leave an untracked orphan post, and a crash after persistence can leave a pending deletion. The latter is recoverable by the cleanup command; the former requires manual review because deleting an untracked post would violate the ownership guarantee.

The publisher also takes a per-account MySQL advisory lock around publish, record, and deletion, so concurrent workers cannot let an older POST replace and delete a newer one. The `primary` and `secondary` slots identify the configured endpoint/token pair, not a Mastodon account discovered at runtime. If either domain or token changes, review or clear that slot manually before enabling publishing; the application cannot prove that an existing stored ID belongs to the new identity.
