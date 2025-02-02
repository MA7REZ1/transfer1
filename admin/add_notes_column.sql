-- إضافة عمود admin_id لجدول complaint_responses
ALTER TABLE complaint_responses
ADD COLUMN admin_id INT NULL,
    ADD COLUMN admin_role VARCHAR(50) NULL,
    ADD FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE
SET NULL;
-- تحديث الردود الموجودة
UPDATE complaint_responses
SET admin_id = NULL,
    admin_role = NULL
WHERE admin_id IS NULL;