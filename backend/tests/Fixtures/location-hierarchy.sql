/* TEST FIXTURE — not cahyadsn national data. */
CREATE TABLE IF NOT EXISTS wilayah (kode varchar(13) NOT NULL, nama varchar(100) NOT NULL);
INSERT INTO wilayah (kode, nama)
VALUES
('01','Test Province'),
('01.02','Kabupaten Test Regency'),
('01.02.03','Test District'),
('01.02.03.2001','Test Desa');