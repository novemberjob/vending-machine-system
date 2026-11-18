INSERT INTO products (name, price, quantity_available) VALUES
  ('Coke',  3.990, 10),
  ('Pepsi', 6.885, 10),
  ('Water', 0.500, 10)
ON DUPLICATE KEY UPDATE price = VALUES(price), quantity_available = VALUES(quantity_available);
