ALTER TABLE inventory_item
    MODIFY current_stock DECIMAL(16, 8) NOT NULL DEFAULT 0.00000000,
    MODIFY reorder_level DECIMAL(16, 8) NOT NULL DEFAULT 0.00000000;

ALTER TABLE product_ingredient
    MODIFY quantity_used DECIMAL(16, 8) NOT NULL;

ALTER TABLE inventory_transaction
    MODIFY quantity DECIMAL(16, 8) NOT NULL;