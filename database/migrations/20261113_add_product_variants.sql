CREATE TABLE IF NOT EXISTS product_variant (
    variant_id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    variant_name VARCHAR(80) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (variant_id),
    UNIQUE KEY unique_product_variant_name (product_id, variant_name),
    CONSTRAINT fk_product_variant_product
        FOREIGN KEY (product_id) REFERENCES product (product_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE product_variant
    MODIFY variant_name VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

ALTER TABLE sale_item
    ADD COLUMN IF NOT EXISTS variant_name VARCHAR(80) NULL AFTER product_id;

ALTER TABLE product
    ADD COLUMN IF NOT EXISTS default_variant_name VARCHAR(80) NOT NULL DEFAULT 'Default' AFTER price;
