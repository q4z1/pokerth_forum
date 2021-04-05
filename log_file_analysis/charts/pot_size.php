<?php
//header("Content-type: image/png");
header("Last-Modified: ".date(DATE_RFC822));

if(isset($_GET['ID'])) {

	$log_file = "../upload/".$_GET['ID'].".pdb";
		
	if(file_exists($log_file)) {
    include("../inc/c-pchart/src/BaseDraw.php");
 		include("../inc/c-pchart/src/Draw.php");
 		include("../inc/c-pchart/src/Image.php");
 		include("../inc/c-pchart/src/Data.php");
    require_once('../inc/jpgraph/src/jpgraph.php');
    require_once('../inc/jpgraph/src/jpgraph_bar.php');
		include("../functions.php");
    
		$db = new PDO("sqlite:".$log_file);

		$pot_size = get_pot_size($db,$_GET['UniqueGameID']);
	
		array_unshift($pot_size[0],0);
		array_unshift($pot_size[1],0);

		$total_start_cash = get_total_start_cash($db,$_GET['UniqueGameID']);
    
    //die("<pre>".var_export($total_start_cash,true)."</pre>");
    
		if($total_start_cash == 0) $total_start_cash = 100000;
		
		$blind_steps = get_blind_steps($db,$_GET['UniqueGameID']);
		$blind_steps[0][2] = 0;
		$blind_steps[] = array($blind_steps[count($blind_steps)-1][0],$blind_steps[count($blind_steps)-1][1],end($pot_size[1]));
 
		if(isset($_GET['width']) && is_numeric($_GET['width']) && $_GET['width'] > 0) {
			$width = $_GET['width'];
		} else {
			$width = 500;
		}
    

    $pot_size_new = array();
    foreach($pot_size[0] as $i => $size ){
      if($i > 0){
        $pot_size_new[$i] = 100000 - $size;
      }else{
        $pot_size_new[$i] = $size;
      }
    }
    //Some data

    //Create the graph. These two calls are always required
    $graph = new Graph($width,240);
    $graph->SetScale('linlin');
    $graph->SetMargin(70,25,15,40);
    $graph->xaxis->title->Set('Hand');
    $graph->xaxis->title->SetMargin(-5);
    $graph->yaxis->title->Set('Pot Size $');
    $graph->yaxis->title->SetMargin(25);
    //$graph->xscale->SetGrace(100);
    //$graph->xscale->ticks->Set(10,count($pot_size_new));
    //Create the linear plot
    $bar1 = new BarPlot($pot_size_new);
    $bar1->SetColor('green');
    
    //$bar1->SetMargin(15);

    //Add the plot to the graph
    $graph->Add($bar1);

    //Display the graph
    $graph->Stroke();
    
    die();
    
		// Dataset definition
// 		$myData = new CpChart\Data();
// 		$myData->addPoints($pot_size_new,1);
// 		$myData->setAxisName(0,"Pot Size $");
	
// 		$myData->addPoints($pot_size[1],"handID");
// 		$myData->setSerieDescription("handID","Hand");
// 		$myData->setAbscissa("handID"); 
// 		$myData->setAbscissaName("Hand");
// 		$myPicture = new CpChart\Image($width,240,$myData);
// 		$myPicture->drawRoundedFilledRectangle(1,2,$width-2,238,5,array("R"=>240,"G"=>240,"B"=>240,"Alpha"=>100));
// 		$myPicture->drawRoundedRectangle(0,0,$width-1,239,5,array("R"=>50,"G"=>100,"B"=>29,"Alpha"=>30));
// 		$myPicture->setGraphArea(60,20,$width-20,200);
// 		$myPicture->drawFilledRectangle(60,20,$width-20,200,array("R"=>255,"G"=>255,"B"=>255,"Alpha"=>100));
// 		$myPicture->setFontProperties(array("FontName"=>"../inc/pChart2/tahoma.ttf","FontSize"=>8,"R"=>50,"G"=>100,"B"=>29,"Alpha"=>50));
// 		if(count($pot_size[1])>10) $labelSkip = 9;
// 		else $labelSkip = 0;
// 		$myPicture->drawScale(
//       array(
//         "GridR"=>200,"GridG"=>200,"GridB"=>200,"CycleBackground"=>TRUE,"LabelSkip"=>$labelSkip,"GridTicks"=>10,"DrawSubTicks"=>TRUE, "Pos"=>SCALE_POS_LEFTRIGHT, "Mode"=>SCALE_MODE_MANUAL,
//         "ManualScale"=>array(0=>array("Min"=>0,"Max"=>$total_start_cash))));
// 		for($i=0;$i<count($blind_steps)-1;$i++) {
// 			if($i%2 == 0) $alpha = 10;
// 			else $alpha = 7;
// 			$areaName = $blind_steps[$i][0]." / ".$blind_steps[$i][1];
// 			$myPicture->drawXThresholdArea($blind_steps[$i][2],$blind_steps[$i+1][2],array("AreaName"=>$areaName,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>$alpha,"Border"=>TRUE));
//		}
//		$myPicture->drawBarChart(array("Gradient"=>TRUE,"GradientMode"=>GRADIENT_EFFECT_CAN,"Surrounding"=>0));
	
// 		$myPicture->drawFilledRectangle(1,201,708,238,array("R"=>220,"G"=>220,"B"=>220));
// 		$myPicture->drawAreaMirror(0,200,708,30,array("StartAlpha"=>40));
	
//		$myPicture->Stroke();

	}

}

?>