USE wc_dev;
UPDATE events SET priceC = 5 WHERE id="666";
UPDATE events SET priceC = 5 WHERE id="333ft";
UPDATE events SET priceB = 5 WHERE id="333ft";
UPDATE registration_frames SET date_time_dev="2019-02-11 00:00:00" WHERE id="B";
/*UPDATE registration_frames SET date_time_prod="2019-02-11 00:00:00" WHERE id="B";*/
/*ALTER TABLE news DROP COLUMN body_es;
ALTER TABLE news DROP COLUMN title_es;*/

CREATE TABLE residencies (id int NOT NULL AUTO_INCREMENT, user_id int, residency varchar(255), PRIMARY KEY(id));

COMMIT;
