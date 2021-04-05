<?php
//error_reporting(E_ALL);
error_reporting(0);
ini_set('display_errors', 1);
//die(test.php);
//

if(isset($_GET['ID'])) {

	$log_file = "../upload/".$_GET['ID'].".pdb";
	
	if(file_exists($log_file)) {
		//header("Content-type: image/png");
		header("Last-Modified: ".date(DATE_RFC822));
		include("../config/chart_defs.php");
		include("../functions.php"); 
    include("../inc/c-pchart/src/BaseDraw.php");
		include("../inc/c-pchart/src/Draw.php");
		include("../inc/c-pchart/src/Image.php");
		include("../inc/c-pchart/src/Data.php");
    require_once('../inc/jpgraph/src/jpgraph.php');
    require_once('../inc/jpgraph/src/jpgraph_line.php');

		$db = new PDO("sqlite:".$log_file);

		$hand_cash = get_hand_cash($db,$_GET['UniqueGameID']);
		$player_list = get_player_list($db,$_GET['UniqueGameID']);
		$total_start_cash = 0;
		for($i=0;$i<count($hand_cash)-1;$i++) {
			if(count($hand_cash[$i])>0) $total_start_cash+=max(0,$hand_cash[$i][0]);
		}
//die("<pre>".var_export($hand_cash,true)."</pre>");
		$winner_found = false;
		for($i=0;$i<count($player_list[0]);$i++) {
			for($j=0;$j<count($hand_cash[$i]);$j++) {
				if($j==count($hand_cash[$i])-1 & $hand_cash[$i][$j] > 0) $winner_found = true;
				if($hand_cash[$i][$j] < 0) $hand_cash[$i][$j] = VOID;
			}
		}

		if(!$winner_found) {
			for($i=0;$i<count($player_list[0]);$i++) {
				array_pop($hand_cash[$i]);
			}
		}
    
		if(isset($_GET['width']) && is_numeric($_GET['width']) && $_GET['width'] > 0) {
			$width = intval($_GET['width']);
		} else { 
			$width = 500;
		}

    if($_GET['stacked']){
	
      
      // Create the graph. These two calls are always required
      $dplot = array();
      
      $graph = new Graph($width,240);
      //$graph->clearTheme();
      $graph->SetScale("textlin");
      //$graph->SetShadow();
      $graph->img->SetMargin(40,30,20,40);

      // Create the linear plots for each category
      for($i=0;$i<count($player_list[0]);$i++) {
          $dplot[$i] = new LinePLot($hand_cash[$player_list[0][$i]-1]);
          $j = $i+1;
          $dplot[$i]->SetFilled(true);
        }
      
      // Create the accumulated graph
      $accplot = new AccLinePlot($dplot);

      // Add the plot to the graph
      $graph->Add($accplot);

      $graph->xaxis->SetTextTickInterval(49);
      $graph->SetMargin(70,25,15,40);
      $graph->xaxis->title->Set('Hand');
      $graph->xaxis->title->SetMargin(0);
      $graph->yaxis->title->Set('Player Cash $');
      $graph->yaxis->title->SetMargin(25);
      // Display the graph
      $graph->Stroke();

      die();
		} 
 	
		//Dataset definition
		$myData = new CpChart\Data();
		for($i=0;$i<count($player_list[0]);$i++) {
			$myData->addPoints($hand_cash[$player_list[0][$i]-1],($i+1));
		}

		//ausgewählten spieler hervorheben
		if($_GET['player']) {
			for($i=0;$i<count($player_list[0]);$i++) {
				if($player_list[0][$i] == $_GET['player']) {
					$myData->setSerieWeight($player_list[2][$i],2);
				}
			}
		}
	//die("<pre>".var_export($myData,true)."</pre>");
	
		$myData->setPalette(1,array("R"=>FARBE_1_1,"G"=>FARBE_1_2,"B"=>FARBE_1_3,"Alpha"=>100));
		$myData->setPalette(2,array("R"=>FARBE_2_1,"G"=>FARBE_2_2,"B"=>FARBE_2_3,"Alpha"=>100));
		$myData->setPalette(3,array("R"=>FARBE_3_1,"G"=>FARBE_3_2,"B"=>FARBE_3_3,"Alpha"=>100));
		$myData->setPalette(4,array("R"=>FARBE_4_1,"G"=>FARBE_4_2,"B"=>FARBE_4_3,"Alpha"=>100));
		$myData->setPalette(5,array("R"=>FARBE_5_1,"G"=>FARBE_5_2,"B"=>FARBE_5_3,"Alpha"=>100));
		$myData->setPalette(6,array("R"=>FARBE_6_1,"G"=>FARBE_6_2,"B"=>FARBE_6_3,"Alpha"=>100));
		$myData->setPalette(7,array("R"=>FARBE_7_1,"G"=>FARBE_7_2,"B"=>FARBE_7_3,"Alpha"=>100));
		$myData->setPalette(8,array("R"=>FARBE_8_1,"G"=>FARBE_8_2,"B"=>FARBE_8_3,"Alpha"=>100));
		$myData->setPalette(9,array("R"=>FARBE_9_1,"G"=>FARBE_9_2,"B"=>FARBE_9_3,"Alpha"=>100));
		$myData->setPalette(10,array("R"=>FARBE_10_1,"G"=>FARBE_10_2,"B"=>FARBE_10_3,"Alpha"=>100));
	
	
		$myData->setAxisName(0,"Player Cash $");
	
		$myData->addPoints($hand_cash[10],"handID");
		$myData->setSerieDescription("handID","Hand");
		$myData->setAbscissa("handID"); 
		$myData->setAbscissaName("Hand");


	
		$myPicture = new CpChart\Image($width,240,$myData);

		$myPicture->drawRoundedFilledRectangle(1,2,$width-2,238,5,array("R"=>240,"G"=>240,"B"=>240,"Alpha"=>100));
		$myPicture->drawRoundedRectangle(0,0,$width-1,239,5,array("R"=>50,"G"=>100,"B"=>29,"Alpha"=>30));
		$myPicture->setGraphArea(60,20,$width-20,200);
		$myPicture->drawFilledRectangle(60,20,$width-20,200,array("R"=>255,"G"=>255,"B"=>255,"Alpha"=>100));
		$myPicture->setFontProperties(array("FontName"=>"../inc/pChart2/tahoma.ttf","FontSize"=>8,"R"=>50,"G"=>100,"B"=>29,"Alpha"=>50));
		if(count($hand_cash[0])>10) $labelSkip = 9;
		else $labelSkip = 0;


	
// 		$text = "SID: ".SID."<br>session_id(): ".session_id()."<br>COOKIE: ".$_COOKIE["PHPSESSID"];
// 		$text = $total_start_cash;
	
// 		$myPicture->drawText(60,115,$text);
// 		$myPicture->drawText(60,150,$_SESSION['test']);
// 		$myPicture->drawText(60,180,$_GET['PHPSESSION']);


    $myPicture->drawScale(
      array(
        "GridR"=>200,
        "GridG"=>200,
        "GridB"=>200,
        "CycleBackground"=>TRUE,
        "LabelSkip"=>$labelSkip,
        "GridTicks"=>10,
        "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_MANUAL,"ManualScale"=>array(0=>array("Min"=>0,"Max"=>$total_start_cash))));
    $myPicture->drawLineChart();

	
		$myPicture->Stroke();
 		//$myPicture->render("test.png");

		//die(var_export($myPicture, true));

	}else{
		die("log-file does not exist!");
	}

}

?>