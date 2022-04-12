/* 	Adds a column for qualification formats:
	0 = single: used for 2x2x2 Cube, 3x3x3 Cube, 3x3x3 Blindfolded, 4x4x4 Blindfolded,
		5x5x5 Blindfolded, Pyraminx, Skewb
	1 = top X registered: used for 3x3x3 Fewest Moves and 3x3x3 Multi-Blind
	2 = average/mean: used for all others	*/

use wc;
ALTER TABLE events ADD qual_format tinyint(4);
UPDATE events SET qual_format = competition_format;
UPDATE events SET qual_format = 0 WHERE id="222" OR id="333" OR id="pyram" OR id="skewb";
UPDATE events SET qual_format = 1 WHERE id="333mbf";
UPDATE events SET qual_format = 2 WHERE id="666" OR id="777";

COMMIT;