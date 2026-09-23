-- TheMerchant: bootstrap PostgreSQL / Supabase.
-- Generated from the repository migrations by scripts/export-supabase-schema.php.
-- Run the entire file in the Supabase SQL Editor, using the database owner.
-- Requires an empty (or absent) laravel schema. Existing tables cause a rollback.
-- Does not delete, rename, or import the tables/data in public.
-- This creates the current application structure; it does not implement Auth,
-- Storage, payment providers, email delivery, or application business rules.
-- Keep laravel out of the Supabase Data API exposed schemas.
-- Laravel environment: DB_CONNECTION=pgsql, DB_SCHEMA=laravel, DB_SSLMODE=require.
-- Migration records are inserted only after all tables have been created.
-- Do not use migrate:fresh or migrate:rollback on a populated application database.

BEGIN;
SET LOCAL lock_timeout = '5s';
CREATE SCHEMA IF NOT EXISTS laravel;
SET LOCAL search_path TO laravel;
create table "migrations" ("id" serial not null primary key, "migration" varchar(255) not null, "batch" integer not null);

-- 2026_01_01_000001_create_users_table
create table "users" ("id" bigserial not null primary key, "name" varchar(120) not null, "email" varchar(150) not null, "email_verified_at" timestamp(0) without time zone null, "password" varchar(255) not null, "role" varchar(255) check ("role" in ('buyer', 'seller', 'admin')) not null default 'buyer', "status" varchar(255) check ("status" in ('active', 'suspended')) not null default 'active', "remember_token" varchar(100) null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "users" add constraint "users_email_unique" unique ("email");
create index "users_role_index" on "users" ("role");

-- 2026_01_01_000002_create_seller_profiles_table
create table "seller_profiles" ("id" bigserial not null primary key, "user_id" bigint not null, "bio" text null, "reputation_score" decimal(3, 2) not null default '5', "total_reviews" integer not null default '0', "total_sales" integer not null default '0', "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "seller_profiles" add constraint "seller_profiles_user_id_foreign" foreign key ("user_id") references "users" ("id") on delete cascade;
alter table "seller_profiles" add constraint "seller_profiles_user_id_unique" unique ("user_id");

-- 2026_01_01_000003_create_games_table
create table "games" ("id" bigserial not null primary key, "name" varchar(100) not null, "slug" varchar(120) not null, "cover_image" varchar(255) null, "active" boolean not null default '1', "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "games" add constraint "games_slug_unique" unique ("slug");

-- 2026_01_01_000004_create_categories_table
create table "categories" ("id" bigserial not null primary key, "game_id" bigint null, "name" varchar(100) not null, "slug" varchar(120) not null, "type" varchar(255) check ("type" in ('cosmetic', 'service')) not null default 'cosmetic', "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "categories" add constraint "categories_game_id_foreign" foreign key ("game_id") references "games" ("id") on delete set null;

-- 2026_01_01_000005_create_listings_and_images_tables
create table "listings" ("id" bigserial not null primary key, "seller_id" bigint not null, "game_id" bigint not null, "category_id" bigint not null, "title" varchar(150) not null, "slug" varchar(180) not null, "description" text not null, "price" decimal(10, 2) not null, "status" varchar(255) check ("status" in ('rascunho', 'publicado', 'pausado', 'vendido', 'bloqueado')) not null default 'publicado', "views_count" integer not null default '0', "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "listings" add constraint "listings_seller_id_foreign" foreign key ("seller_id") references "users" ("id") on delete cascade;
alter table "listings" add constraint "listings_game_id_foreign" foreign key ("game_id") references "games" ("id") on delete cascade;
alter table "listings" add constraint "listings_category_id_foreign" foreign key ("category_id") references "categories" ("id") on delete cascade;
create index "listings_game_id_category_id_status_index" on "listings" ("game_id", "category_id", "status");
create index "listings_price_index" on "listings" ("price");
alter table "listings" add constraint "listings_slug_unique" unique ("slug");
create index "listings_status_index" on "listings" ("status");
create table "listing_images" ("id" bigserial not null primary key, "listing_id" bigint not null, "image_path" varchar(255) not null, "is_primary" boolean not null default '0', "display_order" smallint not null default '0', "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "listing_images" add constraint "listing_images_listing_id_foreign" foreign key ("listing_id") references "listings" ("id") on delete cascade;

-- 2026_01_01_000006_create_carts_and_items_tables
create table "carts" ("id" bigserial not null primary key, "user_id" bigint not null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "carts" add constraint "carts_user_id_foreign" foreign key ("user_id") references "users" ("id") on delete cascade;
alter table "carts" add constraint "carts_user_id_unique" unique ("user_id");
create table "cart_items" ("id" bigserial not null primary key, "cart_id" bigint not null, "listing_id" bigint not null, "quantity" integer not null default '1', "unit_price" decimal(10, 2) not null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "cart_items" add constraint "cart_items_cart_id_foreign" foreign key ("cart_id") references "carts" ("id") on delete cascade;
alter table "cart_items" add constraint "cart_items_listing_id_foreign" foreign key ("listing_id") references "listings" ("id") on delete cascade;

-- 2026_01_01_000007_create_orders_and_items_tables
create table "orders" ("id" bigserial not null primary key, "order_number" varchar(32) not null, "buyer_id" bigint not null, "total_amount" decimal(10, 2) not null, "status" varchar(255) check ("status" in ('pendente', 'pago', 'em_andamento', 'concluido', 'cancelado')) not null default 'pendente', "notes" text null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "orders" add constraint "orders_buyer_id_foreign" foreign key ("buyer_id") references "users" ("id") on delete cascade;
alter table "orders" add constraint "orders_order_number_unique" unique ("order_number");
create index "orders_status_index" on "orders" ("status");
create table "order_items" ("id" bigserial not null primary key, "order_id" bigint not null, "listing_id" bigint not null, "seller_id" bigint not null, "unit_price" decimal(10, 2) not null, "quantity" integer not null default '1', "delivery_status" varchar(255) check ("delivery_status" in ('aguardando_pagamento', 'em_entrega', 'entregue')) not null default 'aguardando_pagamento', "delivered_at" timestamp(0) without time zone null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "order_items" add constraint "order_items_order_id_foreign" foreign key ("order_id") references "orders" ("id") on delete cascade;
alter table "order_items" add constraint "order_items_listing_id_foreign" foreign key ("listing_id") references "listings" ("id") on delete cascade;
alter table "order_items" add constraint "order_items_seller_id_foreign" foreign key ("seller_id") references "users" ("id") on delete cascade;

-- 2026_01_01_000008_create_payments_table
create table "payments" ("id" bigserial not null primary key, "order_id" bigint not null, "gateway" varchar(50) not null, "transaction_id" varchar(100) not null, "status" varchar(50) not null, "amount" decimal(10, 2) not null, "idempotency_key" varchar(128) not null, "payload" json null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "payments" add constraint "payments_order_id_foreign" foreign key ("order_id") references "orders" ("id") on delete cascade;
create index "payments_transaction_id_index" on "payments" ("transaction_id");
alter table "payments" add constraint "payments_idempotency_key_unique" unique ("idempotency_key");

-- 2026_01_01_000009_create_reviews_table
create table "reviews" ("id" bigserial not null primary key, "order_id" bigint not null, "order_item_id" bigint not null, "buyer_id" bigint not null, "seller_id" bigint not null, "rating" smallint not null, "comment" text null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "reviews" add constraint "reviews_order_id_foreign" foreign key ("order_id") references "orders" ("id") on delete cascade;
alter table "reviews" add constraint "reviews_order_item_id_foreign" foreign key ("order_item_id") references "order_items" ("id") on delete cascade;
alter table "reviews" add constraint "reviews_buyer_id_foreign" foreign key ("buyer_id") references "users" ("id") on delete cascade;
alter table "reviews" add constraint "reviews_seller_id_foreign" foreign key ("seller_id") references "users" ("id") on delete cascade;
alter table "reviews" add constraint "reviews_order_item_id_unique" unique ("order_item_id");

-- 2026_01_01_000010_create_reports_table
create table "reports" ("id" bigserial not null primary key, "reporter_id" bigint not null, "listing_id" bigint null, "reason" varchar(100) not null, "details" text not null, "status" varchar(255) check ("status" in ('aberta', 'em_analise', 'procedente', 'improcedente')) not null default 'aberta', "moderator_id" bigint null, "resolution_notes" text null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);
alter table "reports" add constraint "reports_reporter_id_foreign" foreign key ("reporter_id") references "users" ("id") on delete cascade;
alter table "reports" add constraint "reports_listing_id_foreign" foreign key ("listing_id") references "listings" ("id") on delete set null;
alter table "reports" add constraint "reports_moderator_id_foreign" foreign key ("moderator_id") references "users" ("id") on delete set null;
create index "reports_status_index" on "reports" ("status");

-- 2026_09_21_102914_create_sessions_table
create table "sessions" ("id" varchar(255) not null, "user_id" bigint null, "ip_address" varchar(45) null, "user_agent" text null, "payload" text not null, "last_activity" integer not null);
alter table "sessions" add primary key ("id");
create index "sessions_user_id_index" on "sessions" ("user_id");
create index "sessions_last_activity_index" on "sessions" ("last_activity");

-- 2026_09_21_102917_create_cache_table
create table "cache" ("key" varchar(255) not null, "value" text not null, "expiration" integer not null);
alter table "cache" add primary key ("key");
create table "cache_locks" ("key" varchar(255) not null, "owner" varchar(255) not null, "expiration" integer not null);
alter table "cache_locks" add primary key ("key");

-- 2026_09_21_102920_create_jobs_table
create table "jobs" ("id" bigserial not null primary key, "queue" varchar(255) not null, "payload" text not null, "attempts" smallint not null, "reserved_at" integer null, "available_at" integer not null, "created_at" integer not null);
create index "jobs_queue_index" on "jobs" ("queue");

-- 2026_09_23_000001_separate_selling_permissions_from_users
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

-- Register the exact migrations represented above.
INSERT INTO migrations (migration, batch) VALUES
    ('2026_01_01_000001_create_users_table', 1),
    ('2026_01_01_000002_create_seller_profiles_table', 1),
    ('2026_01_01_000003_create_games_table', 1),
    ('2026_01_01_000004_create_categories_table', 1),
    ('2026_01_01_000005_create_listings_and_images_tables', 1),
    ('2026_01_01_000006_create_carts_and_items_tables', 1),
    ('2026_01_01_000007_create_orders_and_items_tables', 1),
    ('2026_01_01_000008_create_payments_table', 1),
    ('2026_01_01_000009_create_reviews_table', 1),
    ('2026_01_01_000010_create_reports_table', 1),
    ('2026_09_21_102914_create_sessions_table', 1),
    ('2026_09_21_102917_create_cache_table', 1),
    ('2026_09_21_102920_create_jobs_table', 1),
    ('2026_09_23_000001_separate_selling_permissions_from_users', 1);

COMMIT;

-- The following result confirms the tables created in the application schema.
SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'laravel' ORDER BY tablename;
