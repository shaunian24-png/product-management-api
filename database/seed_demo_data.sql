-- =====================================================================
--  OPTIONAL demo data (for your screenshots)
--  Run AFTER aiven_setup.sql.
--
--  Login you can use right away:
--      email:    admin@example.com
--      password: Admin@12345
--
--  This is a public demo password - change it or delete the user once your
--  lab is checked:   DELETE FROM users WHERE email = 'admin@example.com';
--  (You can also just click "Register" in the React app and make your own account.)
-- =====================================================================

INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`)
VALUES ('admin', 'admin@example.com', '$2y$10$tYsMGvECH8N1MWdC2BJlo.RPMspTVEd0udssxzwXMLhymnnd6cprq', 'admin');

INSERT INTO `products` (`product_name`, `description`, `price`, `quantity`) VALUES
('Wireless Mouse',      '2.4GHz ergonomic mouse with USB receiver',  499.00, 35),
('Mechanical Keyboard', 'Blue-switch keyboard, full size',          1899.50, 12),
('USB-C Hub 7-in-1',    'HDMI, 3x USB 3.0, SD/microSD, PD charging', 1250.00, 20),
('Notebook (A5)',       '100 sheets, dotted',                         85.00, 150);

SELECT id, product_name, price, quantity, created_at FROM `products`;
