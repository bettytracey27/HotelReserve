CREATE TABLE destinations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  description TEXT,
  image_path VARCHAR(255)
);

CREATE TABLE hotels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  destination_id INT,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  amenities TEXT,
  image_path VARCHAR(255),
  FOREIGN KEY (destination_id) REFERENCES destinations(id)
);