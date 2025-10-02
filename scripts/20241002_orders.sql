-- orders: معلومات الطلب
CREATE TABLE IF NOT EXISTS orders (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_name TEXT    NOT NULL,
  customer_email TEXT   NOT NULL,
  customer_phone TEXT   NOT NULL,
  shipping_addr  TEXT   NOT NULL,
  subtotal       REAL   NOT NULL DEFAULT 0,
  vat            REAL   NOT NULL DEFAULT 0,
  shipping_cost  REAL   NOT NULL DEFAULT 0,
  grand_total    REAL   NOT NULL DEFAULT 0,
  status         TEXT   NOT NULL DEFAULT 'placed', -- placed|paid|shipped|delivered|canceled
  created_at     TEXT   NOT NULL DEFAULT (datetime('now')),
  paid_at        TEXT
);

-- order_items: تفاصيل الأصناف في الطلب
CREATE TABLE IF NOT EXISTS order_items (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id   INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id INTEGER NOT NULL,
  product_name TEXT  NOT NULL,
  unit_price REAL   NOT NULL,
  qty        INTEGER NOT NULL DEFAULT 1,
  line_total REAL   NOT NULL
);