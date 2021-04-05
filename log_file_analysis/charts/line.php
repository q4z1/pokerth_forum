<?php
header("Content-type: image/png");

	include("../config/chart_defs.php");
  include("../inc/c-pchart/src/BaseDraw.php");
  include("../inc/c-pchart/src/Draw.php");
  include("../inc/c-pchart/src/Image.php");
  include("../inc/c-pchart/src/Data.php");

	$x1 = 0;
	$y1 = 0;
	$x2 = 10;
	$y2 = 0;
	$weight = 10;

	$myPicture = new CpChart\Image($x2,$weight);
	switch($_GET['player']) {
		case 1:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_1_1,"G"=>FARBE_1_2,"B"=>FARBE_1_3,"Weight"=>$weight));
			break;
		case 2:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_2_1,"G"=>FARBE_2_2,"B"=>FARBE_2_3,"Weight"=>$weight));
			break;
		case 3:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_3_1,"G"=>FARBE_3_2,"B"=>FARBE_3_3,"Weight"=>$weight));
			break;
		case 4:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_4_1,"G"=>FARBE_4_2,"B"=>FARBE_4_3,"Weight"=>$weight));
			break;
		case 5:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_5_1,"G"=>FARBE_5_2,"B"=>FARBE_5_3,"Weight"=>$weight));
			break;
		case 6:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_6_1,"G"=>FARBE_6_2,"B"=>FARBE_6_3,"Weight"=>$weight));
			break;
		case 7:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_7_1,"G"=>FARBE_7_2,"B"=>FARBE_7_3,"Weight"=>$weight));
			break;
		case 8:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_8_1,"G"=>FARBE_8_2,"B"=>FARBE_8_3,"Weight"=>$weight));
			break;
		case 9:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_9_1,"G"=>FARBE_9_2,"B"=>FARBE_9_3,"Weight"=>$weight));
			break;
		case 10:
			$myPicture->drawLine($x1,$y1,$x2,$y2,array("R"=>FARBE_10_1,"G"=>FARBE_10_2,"B"=>FARBE_10_3,"Weight"=>$weight));
		default:
	}
	
	$myPicture->Stroke();

?>