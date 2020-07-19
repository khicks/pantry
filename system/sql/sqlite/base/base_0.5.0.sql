CREATE TABLE IF NOT EXISTS "app_config" (
    "kv_key" varchar(32) NOT NULL,
    "kv_value" varchar(128) NOT NULL,
    PRIMARY KEY ("kv_key")
);

CREATE TABLE IF NOT EXISTS "app_metadata" (
    "kv_key" varchar(32) NOT NULL,
    "kv_value" varchar(128) NOT NULL,
    PRIMARY KEY ("kv_key")
);

CREATE TABLE IF NOT EXISTS "courses" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "title" varchar(32) NOT NULL,
    "slug" varchar(32) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "cuisines" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "title" varchar(32) NOT NULL,
    "slug" varchar(32) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "images" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "mime_type" varchar(20) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "recipes" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "updated" datetime NOT NULL,
    "title" varchar(64) NOT NULL,
    "slug" varchar(40) NOT NULL,
    "blurb" varchar(100) NOT NULL,
    "description" mediumtext NOT NULL,
    "servings" tinyint(4) NOT NULL,
    "prep_time" mediumint(6) NOT NULL,
    "cook_time" mediumint(6) NOT NULL,
    "ingredients" mediumtext NOT NULL,
    "directions" mediumtext NOT NULL,
    "source" varchar(2084) NOT NULL,
    "visibility_level" tinyint(1) NOT NULL,
    "default_permission_level" tinyint(1) NOT NULL,
    "featured" bit(1) NOT NULL,
    "author_id" char(36) DEFAULT NULL,
    "course_id" char(36) DEFAULT NULL,
    "cuisine_id" char(36) DEFAULT NULL,
    "image_id" char(36) DEFAULT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "recipes_permissions" (
    "id" char(36) NOT NULL,
    "recipe_id" char(36) NOT NULL,
    "user_id" char(36) NOT NULL,
    "level" tinyint(1) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "sessions" (
    "id" char(36) NOT NULL,
    "updated" datetime NOT NULL,
    "session_data" mediumtext NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "two_factor_keys" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "user_id" char(36) NOT NULL,
    "two_factor_key" char(64) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "two_factor_logins" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "user_id" char(36) NOT NULL,
    "verification_code" char(6) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "two_factor_sessions" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "user_id" char(36) NOT NULL,
    "secret" char(128) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "user_sessions" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "updated" datetime NOT NULL,
    "session_id" char(36) NOT NULL,
    "user_id" char(36) NOT NULL,
    "ip_address" varchar(45) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "users" (
    "id" char(36) NOT NULL,
    "created" datetime NOT NULL,
    "username" varchar(32) NOT NULL,
    "password" varchar(255) NOT NULL,
    "is_admin" bit(1) NOT NULL,
    "is_disabled" bit(1) NOT NULL,
    "last_login" datetime DEFAULT NULL,
    "first_name" varchar(64) NOT NULL,
    "last_name" varchar(64) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE INDEX "user_sessions_created" ON "user_sessions" ("created");
CREATE INDEX "user_sessions_updated" ON "user_sessions" ("updated");
CREATE INDEX "user_sessions_session_id" ON "user_sessions" ("session_id");
CREATE INDEX "user_sessions_user_id" ON "user_sessions" ("user_id");
CREATE INDEX "user_sessions_ip_address" ON "user_sessions" ("ip_address");
CREATE INDEX "users_username" ON "users" ("username");
CREATE INDEX "users_created" ON "users" ("created");
CREATE INDEX "users_is_admin" ON "users" ("is_admin");
CREATE INDEX "users_is_disabled" ON "users" ("is_disabled");
CREATE INDEX "users_last_login" ON "users" ("last_login");
CREATE INDEX "users_first_name" ON "users" ("first_name");
CREATE INDEX "users_last_name" ON "users" ("last_name");
CREATE INDEX "two_factor_keys_user_id" ON "two_factor_keys" ("user_id");
CREATE INDEX "recipes_slug" ON "recipes" ("slug");
CREATE INDEX "recipes_created" ON "recipes" ("created");
CREATE INDEX "recipes_updated" ON "recipes" ("updated");
CREATE INDEX "recipes_author_id" ON "recipes" ("author_id");
CREATE INDEX "recipes_course_id" ON "recipes" ("course_id");
CREATE INDEX "recipes_cuisine_id" ON "recipes" ("cuisine_id");
CREATE INDEX "recipes_featured" ON "recipes" ("featured");
CREATE INDEX "recipes_title" ON "recipes" ("title");
CREATE INDEX "recipes_visibility_level" ON "recipes" ("visibility_level");
CREATE INDEX "sessions_updated" ON "sessions" ("updated");
CREATE INDEX "recipes_permissions_recipe_id" ON "recipes_permissions" ("recipe_id");
CREATE INDEX "recipes_permissions_user_id" ON "recipes_permissions" ("user_id");
CREATE INDEX "courses_slug" ON "courses" ("slug");
CREATE INDEX "two_factor_sessions_check_idx" ON "two_factor_sessions" ("id","user_id","secret");
CREATE INDEX "two_factor_logins_check_idx" ON "two_factor_logins" ("user_id","verification_code");
CREATE INDEX "two_factor_logins_created" ON "two_factor_logins" ("created");
CREATE INDEX "two_factor_logins_user_id" ON "two_factor_logins" ("user_id");
CREATE INDEX "cuisines_slug" ON "cuisines" ("slug");
