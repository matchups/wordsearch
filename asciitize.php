<?php
function asciitize ($rawword) {
	$oneword = "";
	$bad = "";
	$twobyte = " [150,32]h -
               [153,72]' [153,97]' [153,115]' -
               [181,110]n -
               [194,128] [194,129] [194,130] [194,131] [194,132] [194,133] [194,134] [194,135] [194,136] [194,137] [194,138] [194,139] -
							   [194,140] [194,141] [194,142] [194,143] [194,144] [194,145] [194,146] [194,147] [194,148] [194,149] -
								 [194,150] [194,151] [194,152] [194,153] [194,154] [194,155] [194,156] [194,157] [194,158] [194,159] -
								 [194,161]! [194,162]c [194,163] [194,164] [194,165]Y [194,166] [194,167]# [194,168]\" [194,169](C) -
                 [194,170]a [194,171]\" [194,172]- [194,173] [194,174] [194,175] [194,176] [194,177]+- [194,178]2 [194,179]3 -
                 [194,180]' [194,181] [194,182] [194,183] [194,184] [194,185]1 [194,186] [194,187]\" [194,188]1/4 [194,189]1/2 [194,190]3/4 [194,191]? -
               [195,128]A [195,129]A [195,130]A [195,131]A [195,132]A [195,133]A [195,134]AE [195,135]C [195,136]E [195,137]E [195,138]E [195,139]E -
                 [195,140]I [195,141]I [195,142]I [195,143]I [195,144]D [195,145]N [195,146]O [195,147]O [195,148]O [195,149]O -
                 [195,150]O [195,151]* [195,152]0 [195,153]U [195,154]U [195,155]U [195,156]U [195,157]Y [195,158]Th [195,159]ss [196,161]g [196,163]g ss -
                 [195,160]a [195,161]a [195,162]a [195,163]a [195,164]a [195,165]a [195,166]ae [195,167]c [195,168]e [195,169]e -
                 [195,170]e [195,171]e [195,172]i [195,173]i [195,174]i [195,175]i [195,176]o [195,177]n [195,178]o [195,179]o -
                 [195,180]o [195,181]o [195,182]o [195,183]/ [195,184]o [195,185]u [195,186]u [195,187]u [195,188]u [195,189]y [195,190] [195,191]y -
               [196,128]A [196,129]a [196,130]A [196,131]a [196,132]A [196,133]a [196,134]C [196,135]c [196,136]C [196,137]c [196,138]C [196,139]C -
                 [196,140]C [196,141]c [196,142]D [196,143]d [196,144]D [196,145]d [196,146]E [196,147]e [196,148]E [196,149]e -
                 [196,150]E [196,151]e [196,152]E [196,153]e [196,154]E [196,155]e [196,156]G [196,157]g [196,158]G [196,159]g -
                 [196,160]G [196,161]g [196,162]G [196,163]g [196,164]H [196,165]h [196,166]H [196,167]h [196,168]I [196,169]i -
                 [196,170]I [196,171]i [196,172]I [196,173]i [196,174]I [196,175]i [196,176]I [196,177]i [196,178]IJ [196,179]ij -
                 [196,180]J [196,181]j [196,182]K [196,183]k [196,184]k [196,185]L [196,186]l [196,187]L [196,188]l [196,189]' [196,190]' [196,191]L -
               [197,128]l [197,129]L [197,130]l [197,131]N [197,132]n [197,133]N [197,134]n [197,135]N [197,136]n [197,137]'n [197,138]NG [197,139]n -
                 [197,140]O [197,141]o [197,142]O [197,143]o [197,144]O [197,145]o [197,146]OE [197,147]oe [197,148]R [197,149]r -
                 [197,150]R [197,151]r [197,152]R [197,153]r [197,154]S [197,155]s [197,156]S [197,157]s [197,158]S [197,159]s -
                 [197,160]S [197,161]s [197,162]T [197,163]t [197,164]T [197,165]' [197,166]T [197,167]t [197,168]U [197,169]u -
                 [197,170]U [197,171]u [197,172]U [197,173]u [197,174]U [197,175]u [197,176]U [197,177]u [197,178]U [197,179]u -
                 [197,180]W [197,181]w [197,182]Y [197,183]y [197,184]Y [197,185]Z [197,186]z [197,187]Z [197,188]a [197,189]Z [197,190]z [197,191]s -
               [198,133]b [198,134]? [198,142]. [198,143]. [198,144]E [198,146]f [198,149]? [198,153]k [198,155]? -
							   [198,161]o [198,168]? [198,175]U [198,176]u [198,182]z [198,191]? -
               [199,128]| [199,129] [199,130]? [199,131]! [199,134]dz [199,137]lj [199,140]nj [199,142]a [199,144]i [199,146]o [199,147]U [199,148]u -
                 [199,150]U [199,152]u [199,154]u [199,156]u [199,157]a [199,163]ae [199,165]g [199,167]g [199,169]k -
                 [199,170]O [199,171]o [199,180]G [199,181]g [199,189]ae [199,191]o -
               [200,131]a [200,152]S [200,153]s [200,154]T [200,155]t [200,156]? [200,157]. [200,159]h [200,167]a [200,171]o [200,175]o [200,179]y -
               [201,144]a [201,145]a [201,147]? [201,148]a [201,149]? [201,150]d [201,151]d [201,152] [201,153]a [201,155]a -
                 [201,161]g [201,162]G [201,163]gh [201,164]? [201,165]? [201,168]i [201,169]i -
                 [201,170]i [201,172]l [201,174]? [201,175]? [201,178]? [201,181]? [201,184]r [201,185]r [201,190]r -
               [202,128]? [202,129]? [202,130]s [202,131]ch [202,135]t [202,137]u [202,138]u [202,139]v -
							   [202,141]? [202,142]? [202,145]z [202,146]? [202,148]? -
							   [202,152]O [202,153]? [202,159]? [202,162]? -
                 [202,176]h [202,178]j [202,179]r [202,183]w [202,184]y [202,185]' [202,186]'' [202,187]` [202,188]' [202,189]' [202,190]' [202,191]' -
               [203,128] [203,129] [203,136]' [203,138]' [203,144]: [203,152]' [203,153]' [203,154]' [203,155]. [203,161]l [203,163]x [203,164]' [203,138]' -
               [204,128]' [204,129]e [204,130] [204,131]a [204,132]u [204,133] [204,134]e [204,135] [204,136] [204,138] [204,139] -
                 [204,140]B [204,141]i [204,147]l [204,149] -
                 [204,156] [204,157] [204,158] [204,159] [204,160] [204,161] [204,163]x [204,164] [204,165] [204,166] [204,167]B [204,168]d [204,169] -
                 [204,170]B [204,172] [204,174] [204,175] [204,176] [204,177]o [204,178] [204,180]- [204,181] [204,182]B [204,184]/ [204,186] -
               [205,130] [205,142] [205,143] [205,151] [205,152] [205,160]n [205,161] [205,187]? -
               [206]+2 [207]+2 -
               [208,129]E [208,132]C [208,134]I [208,144]A [208,145] [208,147] [208,148] [208,149]E -
							   [208,150] [208,151] [208,152] [208,154]K [208,155]? [208,156]M [208,157]H [208,158]O [208,159]II -
                 [208,160]P [208,161]C [208,162]T [208,164]H [208,165]X [208,167]? [208,168] -
                 [208,173]? [208,175] [208,176]a [208,177] [208,178] [208,179]r -
                 [208,180] [208,181]e [208,182]? [208,183] [208,184] [208,185] [208,186] [208,187] [208,188]M [208,189]? -
                 [208,190]o [208,191] -
               [209,128]p [209,129]? [209,130]? [209,131]? [209,132]? [209,133]x [209,134]? [209,135]? [209,136] [209,137] [209,138] [209,139] -
                 [209,140] [209,141] [209,142] [209,143] [209,144]e [209,145]e [209,148] -
                 [209,150]i [209,151]i [209,152]j [209,153] [209,154] [209,155]h [209,156] [209,158]y [209,159] [209,167] [209,179]? -
               [210]+2 -
               [211,136] [211,143]l [211,147]a [211,149]ae [211,151]e [211,152] [211,153] [211,167]o [211,169]o -
							 [212,181]? [212,190]? -
               [213]+2 [214]+2 [215]+2 [216]+2 [217]+2 [218]+2 [219]+2 -
               [224]+3 -
               [225,128]+3 [225,131]+3 [225,144]+3 [225,145]+3 [225,146]+3 [225,147]+3 [225,148]+3 [225,149]+3 [225,150]+3 -
                 [225,154]+3 [225,168]+3 [225,169]+3 -
                 [225,180]+ [225,180,133]D [225,180,135]E [225,180,143]O [225,180,153]? -
                 [225,181]+ [225,181,137]e [225,181,144]me [225,181,146] [225,181,171]ue [225,181,187] -
                 [225,182]+ [225,182,152]s -
                 [225,184]+ [225,184,135]b [225,184,139]d [225,184,141]d [225,184,142]D [225,184,143]D [225,184,145]d [225,184,147]d [225,184,149]e [225,184,151]e -
                   [225,184,161]g [225,184,163]h [225,184,164]H [225,184,165]h [225,184,166]H [225,184,168]H [225,184,169]h -
                   [225,184,170]H [225,184,171]h [225,184,177]k [225,184,178]K [225,184,179]k -
                   [225,184,180]K [225,184,181]k [225,184,183]l [225,184,187]l [225,184,189]l [225,184,191]m -
                 [225,185]+ [225,185,129]m [225,185,131]m [225,185,133]n [225,185,135]n [225,185,137]n [225,185,147]o -
                   [225,185,151]p [225,185,153]r [225,185,154]R [225,185,155]r [225,185,159]r -
                   [225,185,161]s [225,185,162]S [225,185,163]s -
                   [225,185,171]t [225,185,172]T [225,187,131]e [225,187,138]I [225,185,173]t [225,185,175]t [225,185,179]u -
                 [225,186]+ [225,186,128]W [225,186,129]w [225,186,131]w -
                   [225,186,143]y [225,186,146]Z [225,186,147]z [225,186,148]Z [225,186,149]z [225,186,150]h [225,186,158]SS -
                   [225,186,161]a [225,186,163]a [225,186,164]A [225,186,165]a [225,186,167]a [225,186,168]A [225,186,169]a -
                   [225,186,171]a [225,186,173]a [225,186,175]a [225,186,177]a [225,186,179]a -
                   [225,186,181]a [225,186,183]a [225,186,185]e [225,186,187]e [225,186,189]e [225,186,191]e -
                 [225,187]+ [225,187,129]e [225,187,131]e [225,187,133]e [225,187,135]e [225,187,137]i [225,187,139]i -
                   [225,187,140]O [225,187,141]o [225,187,143]o [225,187,145]o [225,187,147]o [225,187,149]o -
                   [225,187,151]o [225,187,153]o [225,187,154]O [225,187,155]o [225,187,157]o [225,187,159]o -
                   [225,187,161]o [225,187,163]o [225,187,165]u [225,187,167]u [225,187,169]u -
                   [225,187,171]u [225,187,173]u [225,187,175]u [225,187,177]u [225,187,179]y -
                   [225,187,181]y [225,187,183]y [225,187,185]y [225,187,189]? -
                   [225,190]+ [225,190,179]a [225,191]+3 -
               [226,128]+ [226,128,139]. -
                   [226,128,140].  [226,128,141] [226,128,144]- [226,128,145]- [226,128,146]- [226,128,147]- [226,128,148]- [226,128,149]- -
                   [226,128,150]l [226,128,152]' [226,128,153]' [226,128,154], [226,128,155]' [226,128,156]\" [226,128,157]\" -
                   [226,128,158]\" [226,128,160]- [226,128,161] [226,128,162] [226,128,166]... [226,128,167]- [226,128,178]' [226,128,179]\" -
									 [226,128,185]\" [226,128,186]\" [226,128,188]!! -
                 [226,129]+ [226,129,160] [226,129,176]0 [226,129,177]1 [226,129,178]2 [226,129,179]3 -
                 [226,129,180]4 [226,129,181]5 [226,129,182]6 [226,129,183]7 [226,129,184]8 [226,129,185]9 [226,129,186] [226,129,187]- [226,129,191]n -
                 [226,130]+ [226,130,128]0 [226,130,129]1 [226,130,130]2 [226,130,131]3 [226,130,132]4 [226,130,133]5 [226,130,134]6 [226,130,135]7 [226,130,136]8 [226,130,137]9 -
                 [226,130,144]a [226,130,163]F [226,130,169]W [226,130,172] [226,130,181]C -
                 [226,131]+3 -
                 [226,132]+ [226,132,147]? [226,132,150]o [226,132,152]? [226,132,162] -
								 [226,133]+ [226,133,139]& [226,133,147]1/3 [226,133,148]2/3 [226,133,155]1/8 -
	                 [226,133,160]I [226,133,161]II [226,133,162]III [226,133,163]IV [226,133,164]V [226,133,165]VI [226,133,166]VII [226,133,167]VIII [226,133,168]IX [226,133,169]X -
	                 [226,133,170]XI [226,133,171]XII [226,133,172]L [226,133,173]C [226,133,174]D [226,133,175]M -
	                 [226,133,180] -
                 [226,134]+ [226,134,145] [226,134,146]- [226,134,147] [226,134,148]<-> -
                 [226,135]+ [226,135,148]<=> -
                 [226,136]+ [226,136,128]A [226,136,130] [226,136,131]E [226,136,133]? [226,136,134]. [226,136,145]. [226,136,146]- -
                   [226,136,153]- [226,136,154]? [226,136,158] [226,136,160] [226,136,165] [226,136,180] [226,136,189]~ -
                 [226,137]+ [226,137,159]= [226,137,160]= [226,137,161]= -
								 [226,138]+ [226,138,191] -
                 [226,139]+ [226,139,133]- [226,139,134]* -
                 [226,145]+ [226,145,161]? [226,147]+ [226,147,141]X [226,147,167]? -
								 [226,150]+ [226,150,179]? -
                 [226,151]+ [226,151,134]* [226,151,135]* [226,151,139]? [226,151,143]* -
                 [226,152]+ [226,152,133]* [226,152,134]* [226,152,137]* [226,152,160]* [226,152,174]? -
                 [226,153]+ [226,153,128]? [226,153,130]? [226,153,160] [226,153,161] [226,153,165]. [226,153,170]. [226,153,171] [226,153,173]. [226,153,175]# -
								 [226,154]+ [226,154,165]? -
                 [226,156]+ [226,156,149]* [226,156,169]* -
                 [226,157]+ [226,157,164] [226,159]+ [226,159,168]\" [226,159,169]\" [226,172]+ [226,172,177] [226,177]+ [226,177,170]k -
								 [226,184]+ [226,184,151]- -
               [227]+3 [228]+3 [229]+3 [230]+3 [231]+3 [232]+3 [233]+3 -
               [234,153]+ [234,153,145] [234,156]+ [234,156,163] [234,157]+ [234,157,176]9 -
							   [234,158]+ [234,158,137]: [234,158,140]' [234,158,145]n [234,158,159] -
                 [234,179]+ [234,179,160] [234,181]+ [234,181,172] [234,183]+ [234,183,184] [234,184]+ [234,184,176] [234,185]+ [234,185,140] -
               [235]+3 [236]+3 [237]+3 -
               [239,137]+ [239,137,168]? [239,172]+ [239,172,128]ff [239,172,129]i [239,172,130]fl [239,172,131]ffi -
							   [239,184]+ [239,184,143] [239,184,160] [239,184,161] [239,185]+ [239,185,159]# [239,187]+ [239,187,191]: -
                 [239,188]+ [239,188,129]! [239,188,131]# [239,188,134]& [239,188,136] [239,188,137]) [239,188,138]* [239,188,139]& -
                   [239,188,141]- [239,188,142]. [239,188,143]/ [239,188,154]: [239,188,159]? [239,188,191]_ -
                 [239,189]+ [239,189,153]y [239,189,156]| [239,189,158] -
               [240,144]+4 [240,146]+4 [240,157]+4 -";
	for ($here = 0; $here < strlen ($rawword); $here++) {
		$char = substr ($rawword, $here, 1);
		$ord = ord ($char);
		if ($ord < 128) {
      $oneword = $oneword . $char;
    } else {
      $counter = 1;
      do {
        if ($counter == 1) {
          $unicode = "[$ord]";
        } else {
          $next = ord ($nc = substr ($rawword, ++$here, 1));
          $char = $char . $nc;
          $unicode = str_replace ("]", ",$next]", $unicode);
        }
        if (($index = strpos ($twobyte, $unicode)) > 0) {
          // Get characters after matching text up to, but not including, space
          $new = explode (' ', substr ($twobyte, $index + strlen ($unicode)))[0];
          if (preg_match ('/\+([234])/', $new, $matches)) {
            $new = ''; // used for when a whole subsection is blank, such as [227,129] which are Japanese
            $here += $matches[1] - $counter;
          }
        } else if ($counter == 1) { // first character by itself is optional in table
          $new = '+';
        } else {
          if (($next = substr ($rawword, ++$here, 1)) > '') {
            $next = $next . "=" . ord ($next);
          } else {
            $next = "EOS";
          }
          throw new Exception ("Unrecognized character $char $unicode at position $here -- next is $next.");
        }
        $counter++;
      } while ($new == '+');
      $oneword = $oneword . $new;
    }
	}
	return $oneword;
}
?>
