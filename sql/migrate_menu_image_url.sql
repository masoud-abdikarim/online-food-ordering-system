-- Run once if you see: Data too long for column 'image_url'
-- mysql -u root -p kaah < sql/migrate_menu_image_url.sql

ALTER TABLE menuitem MODIFY COLUMN image_url MEDIUMTEXT NULL;
