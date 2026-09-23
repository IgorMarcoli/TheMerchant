-- TheMerchant: update the previous Laravel schema, preserving accounts and data.
-- Alternative to the new bootstrap. Execute this entire file only once.
-- Requires the previous 13 migrations in schema laravel.
BEGIN;
SET LOCAL lock_timeout = '5s';
SET LOCAL search_path TO laravel;
LOCK TABLE migrations IN EXCLUSIVE MODE;
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_09_23_000001_separate_selling_permissions_from_users') THEN
        RAISE EXCEPTION 'Seller account migration already applied';
    END IF;
END
$$;
alter table "users" add column "is_admin" boolean not null default '0';
alter table "seller_profiles" add column "status" varchar(255) check ("status" in ('pending', 'approved', 'suspended')) not null default 'pending';
create index "seller_profiles_status_index" on "seller_profiles" ("status");
UPDATE users SET is_admin = true WHERE role = 'admin';
INSERT INTO seller_profiles (user_id, status, created_at, updated_at)
            SELECT id, 'approved', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP FROM users
            WHERE role = 'seller' AND NOT EXISTS
            (SELECT 1 FROM seller_profiles WHERE seller_profiles.user_id = users.id);
UPDATE seller_profiles SET status = 'approved' WHERE user_id IN
            (SELECT id FROM users WHERE role IN ('seller', 'admin'));
drop index "users_role_index";
alter table "users" drop column "role";

INSERT INTO migrations (migration, batch) SELECT '2026_09_23_000001_separate_selling_permissions_from_users', COALESCE(MAX(batch), 0) + 1 FROM migrations;
COMMIT;
