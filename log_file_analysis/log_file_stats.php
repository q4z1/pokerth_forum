<?php
//$DEBUG = true;
header('content-type: text/html; charset=utf-8');

if(isset($DEBUG)) {
	if($DEBUG) {
		error_reporting(E_ALL);
		ini_set('display_errors','On');
		echo "<h3 style='color:red'><span class='icon-warning-sign' style='padding-right:5px'></span>DEBUG_MODE<span class='icon-warning-sign' style='padding-left:5px'></h3>";
	}
}

if(isset($DEBUG)) {
	if($DEBUG) {
		$path = "floty/log_file_analysis_test";
	} else {
		$path = "log_file_analysis";
	}
} else {
	$path = "log_file_analysis";
}
$regex = '/[<>:&#"{}=?\']/';
error_reporting(E_ALL);
ini_set("display_errors", 1);
include $path."/config/upload_defs.php";

if(isset($_GET['ID'])) {

	$id = preg_replace('/[^abcdef0123456789]/','',$_GET['ID']);

	if(strlen($id)==40) {

		$log_file = $path."/upload/".$id.".pdb";

		include $path."/functions.php";

		if(file_exists($log_file)) {
?>

	<link rel="stylesheet" href="<?php echo $path ?>/config/format.css" type="text/css" media="all" />
	<!--<script type="text/javascript" src="<?php echo $path ?>/inc/jquery-1.8.2.js"></script>-->

<?php
		
		if($db = new PDO(DB,DB_USER,DB_PWD)) {
		
			$query = $db->prepare("SELECT TIMESTAMPDIFF(SECOND,Timestamp,CURRENT_TIMESTAMP()) as time_diff FROM log_file_analysis_upload_admin WHERE ID='".$id."'");
			$query->execute();
			if($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$time_diff = max(0,SEC_COUNT_ID-$row['time_diff']);
				$time_diff_hour = floor($time_diff/3600);
				$time_diff_min = floor($time_diff/60)-$time_diff_hour*60;
				$time_diff_sec = floor($time_diff)-$time_diff_min*60-$time_diff_hour*3600;
			
				echo "<p style='text-align:right;font-style:italic'>This log file analysis is still valid for ".$time_diff_hour." h ".sprintf('%02d',$time_diff_min)." min ".sprintf('%02d',$time_diff_sec)." sec.</p>";
			}
		
		}

			// detect all games
		if($db = new PDO("sqlite:".$log_file)) {
			$query = $db->prepare("SELECT UniqueGameID FROM game");
			$query->execute();

			$uniqueGameIDArray = array();

?>

    <div style="width:100%;text-align:center">

		<!-- select game -->
		<form method="get">
			<input type="hidden" name="ID" value="<?php echo $id ?>">
			<select align=center class="game" name="UniqueGameID" size="1" onchange="javascript:this.form.submit()">
				<option value="" selected>Select a game</option>
				<?php while($row = $query->fetch(PDO::FETCH_ASSOC)) {
					if(is_numeric($row['UniqueGameID']) & $row['UniqueGameID']>0) {
						echo "<option value=\"".$row['UniqueGameID']."\" >Game ".$row['UniqueGameID']."</option>\n";
						$uniqueGameIDArray[] = $row['UniqueGameID'];
					}
				} ?>
			</select>
		</form>

		<?php
	if(isset($_GET['UniqueGameID'])) {
		if(is_numeric($_GET['UniqueGameID']) & $_GET['UniqueGameID']>0 & in_array($_GET['UniqueGameID'],$uniqueGameIDArray,true)) {

			$uniqueGameID = preg_replace('/[^0123456789]/','',$_GET['UniqueGameID']);

		?>

	<script>
		<!--
		var selected_player = 0;
		var stacked = false;
		
		jQuery( document ).ready(function() {
			resize_charts();
		  });
		
		jQuery( window ).resize(function() {
			resize_charts();
		  });
		
		
		function resize_charts() {
			if(stacked) hand_cash_stacked();
			else hand_cash_line();
			pot_size();
			var width = get_width();
			jQuery('#div_course_game').css('width',width);
			jQuery('div.element').css('max-width',width);
			if(width < 600) {
				jQuery('td.hand_name').css('white-space','normal');
				jQuery('td.player').css('white-space','normal');
				
				// hidden colums of played hands table
				th = document.getElementById("played_hands_count_head_1");
				if(th !== null)th.outerHTML = th.outerHTML.replace('colspan="2"','colspan="1"');
				th = document.getElementById("played_hands_count_head_2");
				if(th !== null)th.outerHTML = th.outerHTML.replace('colspan="2"','colspan="1"');
				th = document.getElementById("played_hands_count_head_3");
				if(th !== null)th.outerHTML = th.outerHTML.replace('colspan="2"','colspan="1"');
				th = document.getElementById("played_hands_count_head_4");
				if(th !== null)	th.outerHTML = th.outerHTML.replace('colspan="2"','colspan="1"');
				jQuery('td.played_hands_count_data').css('display','none');
				
			} else {
				jQuery('td.hand_name').css('white-space','nowrap');
				jQuery('td.player').css('white-space','nowrap');
				
				// display colums of played hands table
				th = document.getElementById("played_hands_count_head_1");
				if(th !== null)th.outerHTML = th.outerHTML.replace('colspan="1"','colspan="2"');
				th = document.getElementById("played_hands_count_head_2");
				if(th !== null)th.outerHTML = th.outerHTML.replace('colspan="1"','colspan="2"');
				th = document.getElementById("played_hands_count_head_3");
				if(th !== null) th.outerHTML = th.outerHTML.replace('colspan="1"','colspan="2"');
				th = document.getElementById("played_hands_count_head_4");
				if(th !== null)	th.outerHTML = th.outerHTML.replace('colspan="1"','colspan="2"');
				jQuery('td.played_hands_count_data').css('display','block');
				
			}
			if(width < 400) {
				jQuery('table.data').css('font-size','12px');
			} else {
				jQuery('table.data').css('font-size','14px');
			}
		}

		function get_width() {
			return parseInt(jQuery('div#print_area').width());
		}

		function hand_cash_line() {
			stacked = false;
			hand_cash_line_select_player(selected_player);
			jQuery('#hand_cash_line').css('backgroundImage','url(/log_file_analysis/pic/hand_cash_top.png)');
			jQuery('#hand_cash_stacked').css("backgroundImage", "url(/log_file_analysis/pic/hand_cash_top_inactive.png)");
		}

		function hand_cash_line_select_player(i) {
			if(!stacked) {
				var id = '<?php echo $id ?>';
				var uId = '<?php echo $uniqueGameID ?>';
				var src = '/log_file_analysis/charts/hand_cash.php?ID=';
				src += id+'&UniqueGameID='+uId+'&stacked=0&player='
				src += i+'&width='+get_width();
				jQuery('img[name="hand_cash"]').attr("src", src);
			}
		}

		function hand_cash_stacked() {
			stacked = true;
			var id = '<?php echo $id ?>';
			var uId = '<?php echo $uniqueGameID ?>';
			var src = '/log_file_analysis/charts/hand_cash.php?ID='+id+'&UniqueGameID='+uId+'&stacked=1&player=0&width='+get_width();
			jQuery('img[name="hand_cash"]').attr("src", src);
			jQuery('#hand_cash_line').css('backgroundImage','url(/log_file_analysis/pic/hand_cash_top_inactive.png)');
			jQuery('#hand_cash_stacked').css('backgroundImage','url(/log_file_analysis/pic/hand_cash_top.png)');
		}
		function pot_size() {
			var id = '<?php echo $id ?>';
			var uId = '<?php echo $uniqueGameID ?>';
			var src = '/log_file_analysis/charts/pot_size.php?ID='+id+'&UniqueGameID='+uId+'&width='+get_width();
			jQuery('img[name="pot_size"]').attr("src", src);
		}
		function expand_collapse() {
			if(jQuery('#div_expand_collapse').attr('class') == 'expand') {
				jQuery('#div_expand_collapse').attr('class','collapse');
				jQuery('#span_expand_collapse').remove();
				jQuery('#div_expand_collapse').prepend("<span id='span_expand_collapse'>collapse all<span class='icon-resize-small' style='font-size:14px;padding-left:10px;'></span></span>");
				show_all_table();
			} else {
				jQuery('#div_expand_collapse').attr('class','expand');
				jQuery('#span_expand_collapse').remove();
				jQuery('#div_expand_collapse').prepend("<span id='span_expand_collapse'>&nbsp;expand all<span class='icon-resize-full' style='font-size:14px;padding-left:10px;'></span></span>");
				hide_all_table();
			}
		}
		function toggle_table(id_table,id_icon) {
			if(jQuery('#'+id_table).css('visibility')=='hidden') {
				show_table(id_table,id_icon);
			} else {
				hide_table(id_table,id_icon);
			}
		}
		function show_table(id_table,id_icon) {
			jQuery('#'+id_table).css('visibility','visible');
			jQuery('#'+id_table).css('position','relative');
			jQuery('#'+id_icon).attr('class','icon-chevron-up');
			if(id_table=='table_course_game') {
				jQuery('img.line').css('visibility','visible');
			}
			if(id_table == 'table_highest_wins') {
				jQuery('#table_highest_wins_add').css('visibility','visible');
				jQuery('#table_highest_wins_add').css('position','relative');
			}
		}
		function hide_table(id_table,id_icon) {
			jQuery('#'+id_table).css('visibility','hidden');
			jQuery('#'+id_table).css('position','absolute');
			jQuery('#'+id_icon).attr('class','icon-chevron-down');
			if(id_table=='table_course_game') {
				jQuery('img.line').css('visibility','hidden');
			}
			if(id_table == 'table_highest_wins') {
				jQuery('#table_highest_wins_add').css('visibility','hidden');
				jQuery('#table_highest_wins_add').css('position','absolute');
			}
		}
		function show_all_table() {
			show_table('table_course_game','icon_course_game');
			show_table('table_played_hands','icon_played_hands');
			show_table('table_best_hands','icon_best_hands');
			show_table('table_most_wins','icon_most_wins');
			show_table('table_highest_wins','icon_highest_wins');
			show_table('table_series_wins','icon_series_wins');
			show_table('table_series_losses','icon_series_losses');
			show_table('table_most_raise','icon_most_raise');
			show_table('table_most_all_in','icon_most_all_in');
		}

		function hide_all_table() {
			hide_table('table_course_game','icon_course_game');
			hide_table('table_played_hands','icon_played_hands');
			hide_table('table_best_hands','icon_best_hands');
			hide_table('table_most_wins','icon_most_wins');
			hide_table('table_highest_wins','icon_highest_wins');
			hide_table('table_series_wins','icon_series_wins');
			hide_table('table_series_losses','icon_series_losses');
			hide_table('table_most_raise','icon_most_raise');
			hide_table('table_most_all_in','icon_most_all_in');
		}

		function select_player(seat) {
			if(selected_player == seat) {
				jQuery('table.data tr').css('font-weight','normal');
				jQuery('table.data tr').css('color','rgb(20,20,20)');
				jQuery('table.data td').css('background-image','linear-gradient(rgb(180, 245, 120), rgb(147, 199, 80) 75%, rgb(147, 199, 80))');
				jQuery('td.rank').css('font-weight','bold');
				hand_cash_line_select_player(0);
				selected_player = 0;
			} else {
				jQuery('table.data tr').css('font-weight','normal');
				jQuery('table.data tr').css('color','rgb(20,20,20)');
				jQuery('table.data td').css('background-image','linear-gradient(rgb(180, 245, 120), rgb(147, 199, 80) 75%, rgb(147, 199, 80))');
				jQuery('td.rank').css('font-weight','bold');
				jQuery('tr.player_'+seat).css('font-weight','bold');
				jQuery('tr.player_'+seat).css('color','rgb(0,0,0)');				
				jQuery('tr.player_'+seat+' td').css('background-image','linear-gradient(rgb(110, 225, 170), rgb(173, 240, 208) 75%, rgb(173, 240, 208))');
				hand_cash_line_select_player(seat);
				selected_player = seat;
			}
		}

	//-->
	</script>

	<div id="print_area">

		<table style="border:0px;width:100%;padding:0px;margin:0px;border-spacing:0px">
			<tr style="font-size:30px;font-weight:400;height:45px;font-family: 'ArvoRegular',Helvetica,Arial,sans-serif;vertical-align:middle;text-align:center">
				<td style="background-image:url(<?php echo $path ?>/pic/h_background_left.png);background-repeat:no-repeat; background-position:right;min-width:50px;"></td>
				<td style="background-image:url(<?php echo $path ?>/pic/h_background.png);">
					Game <?php echo $uniqueGameID ?>
				</td>
				<td style="background-image:url(<?php echo $path ?>/pic/h_background_right.png);background-repeat:no-repeat; background-position:left;min-width:50px;text-align:right">
					<span class="icon-print" style="font-size:24px;cursor:pointer;" onclick="printdiv('print_area')"></span>
				</td>
			</tr>
			<tr style="height:20px;"><td><td></tr>
		</table>

<?php
	$player_list = get_player_list($db,$uniqueGameID);
	$hand_cash = get_hand_cash($db,$uniqueGameID);
	$blind_steps = get_blind_steps($db,$uniqueGameID);

	$query = $db->prepare("SELECT Startmoney,StartSb FROM game WHERE UniqueGameID=".$uniqueGameID);
	$query->execute();
	if($row = $query->fetch(PDO::FETCH_ASSOC)) {
		if(is_numeric($row['Startmoney']) & $row['Startmoney']>0) $startmoney = $row['Startmoney'];
		if(is_numeric($row['StartSb']) & $row['StartSb']>0) $startsmallblind = $row['StartSb'];
	} else {
		$startmoney = -1;
		$startsmallblind = -1;
	}

	$query = "
			SELECT Player
			FROM (
				SELECT Player, Seat
				FROM Player
				WHERE UniqueGameID=".$uniqueGameID."
			) NATURAL JOIN (
				SELECT Player as Seat
				FROM Action
				WHERE Action='wins game' AND UniqueGameID=".$uniqueGameID."
			)";
	$query = $db->prepare($query);
	$query->execute();
	if($row = $query->fetch(PDO::FETCH_ASSOC)) {
//		$winner = preg_replace($regex,'',$row['Player']);
                $winner = replace_spec_char($row['Player']);
	} else {
		$winner = "game aborted";
	}

?>

<!-- basic data -->
		<div class="element">
			<h2>Basic data</h2>
			<table style="text-align:left;margin:auto auto">
				<tr>
					<td><b>Number of players:</b></td>
					<td style="text-align:right"><?php
						if(empty($player_list)) echo "n/a";
						else echo count($player_list[0]);
					?></td>
				</tr>
				<tr>
					<td><b>Winner:</b></td>
					<td style="text-align:right"><?php echo $winner ?></td>
				</tr>
				<tr>
					<td><b>Hands:</b></td>
					<td style="text-align:right"><?php
						if(empty($player_list[3][0])) echo "0";
						else echo $player_list[3][0];
					?></td>
				</tr>
				<tr>
					<td><b>Startmoney:</b></td>
					<td style="text-align:right"><?php
						if($startmoney<0) echo "n/a";
						else echo "$".$startmoney;
					?></td>
				</tr>
				<tr>
					<td><b>Start big blind:</b></td>
					<td style="text-align:right"><?php 
						if($startsmallblind<0) echo "n/a";
						else echo "$".(2*$startsmallblind);
					?></td>
				</tr>
				<tr>
					<td><b>End big blind:</b></td>
					<td style="text-align:right"><?php
						if(empty($blind_steps)) {
							if($startsmallblind<0) echo "n/a";
							else echo "$".(2*$startsmallblind);
						} else echo "$".$blind_steps[count($blind_steps)-1][1];
					?></td>
				</tr>
				<tr><td style="height:20px"></td></tr>
				<tr>
					<td colspan=2 style="text-align:center">
						<div id="div_expand_collapse" onClick="javascript:expand_collapse()" class="expand">
							<span id="span_expand_collapse">
								&nbsp;expand all
								<span class="icon-resize-full" style="font-size:14px;padding-left:10px;"></span>
							</span>
						</div>
					</td>
				</tr>
			</table>
		</div>

<!-- ranking -->
	<?php if(!empty($player_list)) { ?>
		<div class="element">
			<h2>Ranking</h2>
			<table class="data">
				<tr>
					<th></th>
					<th class="player">Player</th>
					<th class="hand">Hand</th>
					<th></th>
					<th></th>
				</tr><?php
			for($i=0;$i<count($player_list[0]);$i++)	{ ?>

				<tr class="player_<?php echo $player_list[0][$i]; ?>">
					<td class="rank"><?php echo $player_list[2][$i]; ?>.</td>
					<td class="player" onclick="javascript:select_player(<?php echo $player_list[0][$i]; ?>)"><?php echo $player_list[1][$i]; ?></td>
					<td class="hand"><?php
						if(empty($player_list[3][$i])) echo "0";
						else echo $player_list[3][$i];
					?></td>
					<td style="padding-right:10px"><img class="line" src="/log_file_analysis/charts/line.php?player=<?php echo $player_list[2][$i]; ?>" /></td>
					<td style="font-size:11px;padding-right:10px">
					<?php if(is_array($player_list) && is_array($player_list[7]) && is_array($player_list[7][$i]) && count($player_list[7][$i])>0) {
						if($player_list[7][$i]==-1) echo "has left the game";
						else {
							if($player_list[6][$i] == 1) echo "wins with ";
							else echo "eliminated by ";
							for($j=0;$j<count($player_list[7][$i]);$j++) {
								if($j>0) {
									if(count($player_list[7][$i]) > 1 & $j==count($player_list[7][$i])-1) echo " and ";
									else echo ", ";
								}
								echo $player_list[7][$i][$j];
							}
						}
					} ?>
					</td>
					<?php
			} ?>

				</tr>
			</table>
		</div>

<!-- course of the game -->
		<div id="div_course_game" class="element">
			<span onClick="javascript:toggle_table('table_course_game','icon_course_game')" style="cursor:pointer">
				<h2>Course of the game</h2>
				<span id="icon_course_game" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_course_game" class="toggle">
				<tr>
					<td>
						<div style="margin-left:20px">
							<div id="hand_cash_line" style="width:60px;background-image:url(/log_file_analysis/pic/hand_cash_top.png);text-align:center;float:left">
								<a href="javascript:hand_cash_line()" onFocus="if(this.blur)this.blur()">line</a>
							</div>
							<div id="hand_cash_stacked" style="width:60px;background-image:url(/log_file_analysis//pic/hand_cash_top_inactive.png);text-align:center;float:left">
								<a href="javascript:hand_cash_stacked()" onFocus="if(this.blur)this.blur()">stacked</a>
							</div>
						</div>
						<div style="clear:left">
							<img name="hand_cash" src="" />
						</div>
						<div style="clear:left">
							<img name="pot_size" src="" />
						</div>
					</td>
				</tr>
			</table>
			<script type="text/javascript">
				<!--
					jQuery('#div_course_game').css('width',get_width());
				//-->
			</script>
		</div>

<?php array_multisort($player_list[0],$player_list[1],$player_list[2],$player_list[3],$player_list[4],$player_list[5],$player_list[6],$player_list[7]); ?>

<!-- hands played -->
		<div id='div_played_hands' class="element">
			<span onClick="javascript:toggle_table('table_played_hands','icon_played_hands')" style="cursor:pointer">
				<h2>Most hands played</h2>
				<span id="icon_played_hands" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_played_hands" class="data toggle">
				<tr>
					<th></th>
					<th class="player">Player</th>
					<th id="played_hands_count_head_1" class="hand" colspan="2" style="text-align:left">Count</th>
					<th id="played_hands_count_head_2" colspan="2">10&nbsp;to&nbsp;7<br>player</th>
					<th id="played_hands_count_head_3" colspan="2">6&nbsp;to&nbsp;4<br>player</th>
					<th id="played_hands_count_head_4" colspan="2">3&nbsp;to&nbsp;1<br>player</th>
				</tr><?php
			$played_hands = get_played_hands($db,$player_list[3],$uniqueGameID,$regex);
			for($i=0;$i<count($played_hands[0]);$i++) { ?>
				<tr class="player_<?php echo $played_hands[0][$i] ?>">
					<td style="padding-right:15px;padding-left:10px;text-align:right;"><?php echo ($i+1) ?>.</td>
					<td class="player" onclick="javascript:select_player(<?php echo $played_hands[0][$i] ?>)"><?php echo $played_hands[1][$i] ?></td>
					<td style="text-align:right;font-weight:bold"><?php echo round($played_hands[4][$i])."%" ?></td>
					<td class="played_hands_count_data" style="font-size:10px;text-align:left;padding-left:5px"><?php echo "(".$played_hands[2][$i]."/".$played_hands[3][$i]." hands)" ?></td>
					<td style="padding-left:20px;text-align:right"><?php echo round($played_hands[7][$i])."%" ?></td>
					<td class="played_hands_count_data" style="font-size:10px;text-align:left;padding-left:5px"><?php echo "(".$played_hands[5][$i]."/".$played_hands[6][$i].")" ?></td>
					<td style="padding-left:20px;text-align:right"><?php if($played_hands[10][$i]>0) { echo round($played_hands[10][$i])."%"; } else { echo "-"; } ?></td>
					<td class="played_hands_count_data" style="font-size:10px;text-align:left;padding-left:5px"><?php if($played_hands[10][$i]>0) { echo "(".$played_hands[8][$i]."/".$played_hands[9][$i].")"; } else { echo "&nbsp;"; } ?></td>
					<td style="padding-left:20px;text-align:right"><?php if($played_hands[13][$i]>0) { echo round($played_hands[13][$i])."%"; } else { echo "-"; } ?></td>
					<td class="played_hands_count_data" style="font-size:10px;text-align:left;padding-left:5px"><?php if($played_hands[13][$i]>0) { echo "(".$played_hands[11][$i]."/".$played_hands[12][$i].")"; } else { echo "&nbsp;"; } ?></td>
				</tr><?php
			} ?>
			</table>
			<script type="text/javascript">
				<!--
					jQuery('#div_most_wins').css('width',jQuery('#table_most_wins').css('width'));
				//-->
			</script>
		</div>

<?php array_multisort($played_hands[0],$played_hands[1],$played_hands[2],$played_hands[3],$played_hands[4],$played_hands[5],$played_hands[6],$played_hands[7],$played_hands[8],$played_hands[9],$played_hands[10],$played_hands[11],$played_hands[12],$played_hands[13]); ?>

<!-- best hands -->
		<div id='div_best_hands' class="element">
			<span onClick="javascript:toggle_table('table_best_hands','icon_best_hands')" style="cursor:pointer">
				<h2>Best hands</h2>
				<span id="icon_best_hands" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_best_hands" class="toggle data">
				<tr>
					<th></th>
					<th style="text-align:left">Cards</th>
					<th class="player">Player</th>
					<th class="hand">Hand</th>
					<th style="padding-right:10px">Result</th>
				</tr><?php
			$best_hands = get_best_hands($db,$hand_cash,10,$uniqueGameID,$regex);
		  if(!empty($best_hands)) {
				for($i=0;$i<count($best_hands[0]);$i++) { ?>
				<tr class="player_<?php echo $best_hands[0][$i] ?>">
					<td style="padding-right:15px;padding-left:10px;text-align:right;"><?php echo ($i+1) ?>.</td>
					<td class="hand_name" style="font-weight:bold"><?php echo $best_hands[2][$i] ?></td>
					<td class="player" onclick="javascript:select_player(<?php echo $best_hands[0][$i] ?>)"><?php echo $best_hands[1][$i] ?></td>
					<td class="hand"><?php echo $best_hands[3][$i] ?></td>
					<td class="result">
					<?php
					if($best_hands[4][$i] >= 0) echo "wins $".$best_hands[4][$i];
					else echo "<span style=\"color:red\">loses $".(-$best_hands[4][$i])."</span>"; ?>
					</td>
				</tr><?php
				}
			} ?>
			</table>
			<script type="text/javascript">
				<!--
					jQuery('#div_best_hands').css('width',jQuery('#table_best_hands').css('width'));
				//-->
			</script>
		</div>

<!-- most wins -->
		<div id='div_most_wins' class="element">
			<span onClick="javascript:toggle_table('table_most_wins','icon_most_wins')" style="cursor:pointer">
				<h2>Most wins</h2>
				<span id="icon_most_wins" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_most_wins" class="data toggle">
				<tr>
					<th></th>
					<th class="player">Player</th>
					<th class="hand" colspan="2">Count&nbsp;*</th>
					<th class="amount">Highest</th>
				</tr><?php
			$most_wins = get_most_wins($db,$hand_cash,$played_hands[2],$uniqueGameID,$regex);
			for($i=0;$i<count($most_wins[0]);$i++) { ?>
				<tr class="player_<?php echo $most_wins[0][$i] ?>">
					<td style="padding-right:15px;padding-left:10px;text-align:right;"><?php echo ($i+1) ?>.</td>
					<td class="player" onclick="javascript:select_player(<?php echo $most_wins[0][$i] ?>)"><?php echo $most_wins[1][$i] ?></td>
					<td style="text-align:right;font-weight:bold;padding-right:5px"><?php echo $most_wins[2][$i] ?></td>
					<td style="text-align:left;padding-left:5px;padding-right:15px"><?php echo "(".round($most_wins[3][$i])."%)" ?></td>
					<td class="amount"><?php if($most_wins[4][$i]>0) echo "$"; echo $most_wins[4][$i] ?></td>
					</tr><?php
			} ?>
			</table>
			<script type="text/javascript">
				<!--
					jQuery('#div_most_wins').css('width',jQuery('#table_most_wins').css('width'));
				//-->
			</script>
		</div>

<!-- highest wins -->
		<div id="div_highest_wins" class="element">
			<span onClick="javascript:toggle_table('table_highest_wins','icon_highest_wins')" style="cursor:pointer">
				<h2>Highest wins</h2>
				<span id="icon_highest_wins" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_highest_wins" class="data toggle">
				<tr>
					<th></th>
					<th class="amount">Amount</th>
					<th class="player">Player</th>
					<th></th>
					<th class="hand">Hand</th>
				</tr><?php
			$highest_win = get_highest_win($db,$hand_cash,10,$uniqueGameID,$regex);
			$side_pot_attendance = false;
			if(!empty($highest_win)) {
				for($i=0;$i<count($highest_win[0]);$i++) { ?>
				<tr class="player_<?php echo $highest_win[0][$i] ?>">
					<td style="padding-right:15px;padding-left:10px;text-align:right;"><?php echo ($i+1) ?>.</td>
					<td class="amount" style="font-weight:bold">$<?php echo $highest_win[4][$i] ?></td>
					<td class="player" onclick="javascript:select_player(<?php echo $highest_win[0][$i] ?>)"><?php echo $highest_win[1][$i] ?></td>
					<td style="font-size:8px;vertical-align:top">
					<?php
						if($highest_win[3][$i]) {
							echo "(*)";
							$side_pot_attendance = true;
						} else {
							echo "&nbsp;";
						}
					?></td>
					<td class="hand"><?php echo $highest_win[2][$i]?></td>
				</tr><?php
				}
			} ?>
			</table><?php
				if($side_pot_attendance) { ?>
					<div id="table_highest_wins_add" class="side_pot_div" style="float:left;text-align:right;font-size:10px;color:#1A4109">(*) side pot</div>
				<?php } ?>
			<script type="text/javascript">
				<!--
					jQuery('#div_highest_wins').css('width',jQuery('#table_highest_wins').css('width'));
					jQuery('#table_highest_wins_add').css('width',jQuery('#table_highest_wins').css('width'));
				//-->
			</script>
		</div>

<!-- longest series of wins -->
		<div id='div_series_wins' class="element">
			<span onClick="javascript:toggle_table('table_series_wins','icon_series_wins')" style="cursor:pointer">
				<h2>Longest series of wins</h2>
				<span id="icon_series_wins" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_series_wins" class="data toggle">
				<tr>
					<th></th>
					<th class="hand">Duration</th>
					<th class="player">Player</th>
					<th colspan=3 class="hands">Hands</th>
					<th class="amount">Total gain</th>
				</tr><?php
			$longest_series_win = get_longest_series_win($db,$hand_cash,$uniqueGameID,$regex);
			if(!empty($longest_series_win)) {
				for($i=0;$i<min(count($longest_series_win[0]),10);$i++) { ?>
				<tr class="player_<?php echo $longest_series_win[0][$i] ?>">
					<td style="padding-right:15px;padding-left:10px;text-align:right;"><?php echo ($i+1) ?>.</td>
					<td class="hand" style="font-weight:bold;padding-right:25px"><?php echo $longest_series_win[2][$i] ?></td>
					<td class="player" onclick="javascript:select_player(<?php echo $longest_series_win[0][$i] ?>)"><?php echo $longest_series_win[1][$i] ?></td>
					<td class="hand" style="padding-right:2px"><?php echo $longest_series_win[3][$i] ?></td>
					<td>-</td>
					<td class="hand" style="padding-right:20px;"><?php echo $longest_series_win[4][$i] ?></td>
					<td class="amount">$<?php echo $longest_series_win[5][$i] ?></td>
				</tr><?php
				}
			} ?>
			</table>
			<script type="text/javascript">
				<!--
					jQuery('#div_series_wins').css('width',jQuery('#table_series_wins').css('width'));
				//-->
			</script>
		</div>

<!-- longest series of losses -->
		<div id='div_series_losses' class="element">
			<span onClick="javascript:toggle_table('table_series_losses','icon_series_losses')" style="cursor:pointer">
				<h2>Longest series of losses</h2>
				<span id="icon_series_losses" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_series_losses" class="data toggle">
				<tr>
					<th></th>
					<th class="hand">Duration</th>
					<th class="player">Player</th>
					<th colspan=3 class="hands">Hands</th>
					<th class="amount">Total Loss</th>
				</tr><?php
			$longest_series_loose = get_longest_series_loose($db,$player_list,$hand_cash,$uniqueGameID);
			if(!empty($longest_series_loose)) {
				for($i=0;$i<min(count($longest_series_loose[0]),10);$i++) { ?>
				<tr class="player_<?php echo $longest_series_loose[0][$i] ?>">
					<td style="padding-right:15px;padding-left:10px;text-align:right;"><?php echo ($i+1) ?>.</td>
					<td class="hand" style="font-weight:bold;padding-right:25px"><?php echo $longest_series_loose[2][$i] ?></td>
					<td class="player" onclick="javascript:select_player(<?php echo $longest_series_loose[0][$i] ?>)"><?php echo $longest_series_loose[1][$i] ?></td>
					<td class="hand" style="padding-right:2px"><?php echo $longest_series_loose[3][$i] ?></td>
					<td>-</td>
					<td class="hand" style="padding-right:20px"><?php echo $longest_series_loose[4][$i] ?></td>
					<td class="amount">$<?php echo $longest_series_loose[5][$i] ?></td>
				</tr><?php
				}
			} ?>
			</table>
			<script type="text/javascript">
				<!--
					jQuery('#div_series_losses').css('width',jQuery('#table_series_losses').css('width'));
				//-->
			</script>
		</div>

<!-- most bet / raise -->
		<div id='div_most_raise' class="element">
			<span onClick="javascript:toggle_table('table_most_raise','icon_most_raise')" style="cursor:pointer">
				<h2>Most bet/raise</h2>
				<span id="icon_most_raise" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_most_raise" class="data toggle">
				<tr>
					<th></th>
					<th class="player">Player</th>
					<th class="hand" colspan="2">Count&nbsp;**</th>
				</tr><?php
			$most_raise = get_most_raise($db,$player_list[0],$player_list[1],$played_hands[2],$uniqueGameID);
			if(!empty($most_raise)) {
				for($i=0;$i<count($most_raise[0]);$i++) { ?>
				<tr class="player_<?php echo $most_raise[0][$i] ?>">
					<td style="padding-right:15px;padding-left:10px;text-align:right;"><?php echo ($i+1) ?>.</td>
					<td class="player" onclick="javascript:select_player(<?php echo $most_raise[0][$i] ?>)"><?php echo $most_raise[1][$i] ?></td>
					<td style="text-align:right;font-weight:bold;padding-right:5px;padding-left:15px"><?php echo $most_raise[2][$i] ?></td>
					<td style="text-align:left;padding-left:5px;padding-right:10px"><?php echo "(".round($most_raise[4][$i])."%)" ?></td>
					
				</tr><?php
				}
			} ?>
			</table>
			<script type="text/javascript">
				<!--
					jQuery('#div_most_raise').css('width',jQuery('#table_most_raise').css('width'));
				//-->
			</script>
		</div>

<!-- most all in -->
		<div id='div_most_all_in' class="element">
			<span onClick="javascript:toggle_table('table_most_all_in','icon_most_all_in')" style="cursor:pointer">
				<h2>Most all in</h2>
				<span id="icon_most_all_in" class="icon-chevron-down" style="font-size:12px;padding-left:10px;vertical-align:top"></span>
			</span>
			<table id="table_most_all_in" class="data toggle">
				<tr>
					<th></th>
					<th class="player">Player</th>
					<th class="hand" style="white-space:normal;padding-left:10px" colspan="2">Total count&nbsp;*</th>
					<th class="hand" style="white-space:normal">In preflop</th>
					<th class="hand" style="white-space:normal">First 5&nbsp;hands</th>
					<th class="hand" style="white-space:normal">Total won</th>
				</tr><?php
			$most_all_in = get_most_all_in($db,$uniqueGameID,$played_hands[2],$regex);
			for($i=0;$i<count($player_list[0]);$i++) { ?>
				<tr class="player_<?php echo $most_all_in[0][$i] ?>">
					<td style="padding-right:15px;padding-left:10px;text-align:right;"><?php echo ($i+1) ?>.</td>
					<td class="player" onclick="javascript:select_player(<?php echo $most_all_in[0][$i] ?>)"><?php echo $most_all_in[1][$i] ?></td>
					<td style="text-align:right;font-weight:bold;padding-left:15px"><?php echo $most_all_in[2][$i] ?></td>
					<td style="text-align:left;padding-left:5px"><?php echo "(".round($most_all_in[3][$i])."%)" ?></td>
					<td class="hand" style="padding-right:30px"><?php echo $most_all_in[4][$i] ?></td>
					<td class="hand" style="padding-right:25px"><?php echo $most_all_in[5][$i] ?></td>
					<td class="hand" style="padding-right:20px"><?php echo $most_all_in[6][$i] ?></td>
				</tr><?php
			} ?>
			</table>
			<script type="text/javascript">
				<!--
					jQuery('#div_most_all_in').css('width',jQuery('#table_most_all_in').css('width'));
				//-->
			</script>
		</div>
	<?php } ?>
		<!-- footnode -->
		<div style="display:inline-block">
			<table style="border:0px;text-align:left;font-size:10px;color:rgb(80,80,80);padding:0px;margin:0px;border-spacing:0px">
				<tr><td>*)</td><td>percental value: absolute value in relation to hands played</td></tr>
				<tr><td>**)</td><td>percental value: number of hands with at least one bet/raise in relation to all hands played</td></tr>
			</table>
		</div>
	
	</div>

<?php
				$db = null;
				} else {
					echo "<p><b>This Game ID is not valid.</b> Please choose a Game from the select box.</p>";
				}
				}
?>
	</div>
<?php
			}
		} else {
		
			$time_count_id_hour = floor(SEC_COUNT_ID/3600);
			$time_count_id_min = floor(SEC_COUNT_ID/60)-$time_count_id_hour*60;
			$time_count_id_sec = floor(SEC_COUNT_ID)-$time_count_id_min*60-$time_count_id_hour*3600;
	
			echo "<p style='color:rgb(255,0,0)'><span class='icon-warning-sign' style='font-size:16px;padding-right:5px'></span><b>Session is not valid.</b><span class='icon-warning-sign' style='font-size:16px;padding-left:5px;padding-right:10px'></span> This can have two reasons:</p><ul style='color:rgb(255,0,0)'><li>The storage time of the log file analysis is expired (current storage time: ".$time_count_id_hour." h ".sprintf('%02d',$time_count_id_min)." min ".sprintf('%02d',$time_count_id_sec)." sec).</li><li>You've entered the session ID wrongly. Please check your session ID for mistakes.</li><ul>";
		
		}
	} else {
	
		$time_count_id_hour = floor(SEC_COUNT_ID/3600);
		$time_count_id_min = floor(SEC_COUNT_ID/60)-$time_count_id_hour*60;
		$time_count_id_sec = floor(SEC_COUNT_ID)-$time_count_id_min*60-$time_count_id_hour*3600;
	
		echo "<p style='color:rgb(255,0,0)'><span class='icon-warning-sign' style='font-size:16px;padding-right:5px'></span><b>Session is not valid.</b><span class='icon-warning-sign' style='font-size:16px;padding-left:5px;padding-right:10px'></span> This can have two reasons:</p><ul style='color:rgb(255,0,0)'><li>The storage time of the log file analysis is expired (current storage time: ".$time_count_id_hour." h ".sprintf('%02d',$time_count_id_min)." min ".sprintf('%02d',$time_count_id_sec)." sec).</li><li>You've entered the session ID wrongly. Please check your session ID for mistakes.</li><ul>";
	}
	
} else {
	echo "<p style='color:rgb(255,0,0)'><span class='icon-warning-sign' style='font-size:16px;padding-right:5px'></span><b>No Session ID is specified.</b><span class='icon-warning-sign' style='font-size:16px;padding-left:5px;padding-right:10px'></span> Please use the Button 'Analyse Logfile...' in your PokerTH Client.</p>";
}

//echo "<h3>$path</h3>";

?>

<script>
	<!--
		function printdiv(printdivname) {
			var tPath = '<?php echo $path; ?>';
			var headstr = '<html><head><title>Booking Details</title></head><body>';
			headstr	+= '<link rel="stylesheet" href="';
			headstr	+= tPath + '/config/format.css" type="text/css" media="screen" />';
			headstr	+= '<link rel="stylesheet" href="';
			headstr	+= tPath + '/config/format.css" type="text/css"';
			headstr	+= 'media="print" />';
			var footstr = '<' + '/' + 'body>';

			var newstr = document.getElementById(printdivname).innerHTML;
			var oldstr = document.body.innerHTML;

			document.body.innerHTML = headstr+newstr+footstr;
			window.print();

			document.body.innerHTML = oldstr;
			return false;
		}
	//-->
</script>
