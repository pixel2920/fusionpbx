<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/classes/logging.php";
	require_once "resources/classes/ringbacks.php";

//check permissions
	if (permission_exists('ivr_menu_add') || permission_exists('ivr_menu_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//function to show the list of sound files
	// moved to functions.php

//action add or update
	if (strlen($_REQUEST["id"]) > 0) {
		$action = "update";
		$ivr_menu_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get total ivr menu count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['ivr_menus']['numeric'] != '') {
			$sql = "select count(*) as num_rows from v_ivr_menus where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				$total_ivr_menus = $row['num_rows'];
			}
			unset($prep_statement, $row);
			if ($total_ivr_menus >= $_SESSION['limit']['ivr_menus']['numeric']) {
				$_SESSION['message_mood'] = 'negative';
				$_SESSION['message'] = $text['message-maximum_ivr_menus'].' '.$_SESSION['limit']['ivr_menus']['numeric'];
				header('Location: ivr_menus.php');
				return;
			}
		}
	}

//get http post values and set them to php variables
	if (count($_POST) > 0) {
		//get ivr menu
			$ivr_menu_name = check_str($_POST["ivr_menu_name"]);
			$ivr_menu_extension = check_str($_POST["ivr_menu_extension"]);
			$ivr_menu_greet_long = check_str($_POST["ivr_menu_greet_long"]);
			$ivr_menu_greet_short = check_str($_POST["ivr_menu_greet_short"]);
			$ivr_menu_options = $_POST["ivr_menu_options"];
			$ivr_menu_invalid_sound = check_str($_POST["ivr_menu_invalid_sound"]);
			$ivr_menu_exit_sound = check_str($_POST["ivr_menu_exit_sound"]);
			$ivr_menu_confirm_macro = check_str($_POST["ivr_menu_confirm_macro"]);
			$ivr_menu_confirm_key = check_str($_POST["ivr_menu_confirm_key"]);
			$ivr_menu_tts_engine = check_str($_POST["ivr_menu_tts_engine"]);
			$ivr_menu_tts_voice = check_str($_POST["ivr_menu_tts_voice"]);
			$ivr_menu_confirm_attempts = check_str($_POST["ivr_menu_confirm_attempts"]);
			$ivr_menu_timeout = check_str($_POST["ivr_menu_timeout"]);
			$ivr_menu_inter_digit_timeout = check_str($_POST["ivr_menu_inter_digit_timeout"]);
			$ivr_menu_max_failures = check_str($_POST["ivr_menu_max_failures"]);
			$ivr_menu_max_timeouts = check_str($_POST["ivr_menu_max_timeouts"]);
			$ivr_menu_digit_len = check_str($_POST["ivr_menu_digit_len"]);
			$ivr_menu_direct_dial = check_str($_POST["ivr_menu_direct_dial"]);
			$ivr_menu_ringback = check_str($_POST["ivr_menu_ringback"]);
			$ivr_menu_cid_prefix = check_str($_POST["ivr_menu_cid_prefix"]);
			$ivr_menu_enabled = check_str($_POST["ivr_menu_enabled"]);
			$ivr_menu_description = check_str($_POST["ivr_menu_description"]);

		//process the values
			$ivr_menu_exit_action = check_str($_POST["ivr_menu_exit_action"]);
			//$ivr_menu_exit_action = "transfer:1001 XML default";
			$timeout_action_array = explode(":", $ivr_menu_exit_action);
			$ivr_menu_exit_app = array_shift($timeout_action_array);
			$ivr_menu_exit_data = join(':', $timeout_action_array);

		//set the default ivr_menu_option_action
			if (strlen($ivr_menu_option_action) == 0) {
				$ivr_menu_option_action = "menu-exec-app";
			}
	}

//process the http data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the id
			if ($action == "update") {
				$ivr_menu_uuid = check_str($_POST["ivr_menu_uuid"]);
			}

		//check for all required data
			$msg = '';
			if (strlen($ivr_menu_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			if (strlen($ivr_menu_extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			if (strlen($ivr_menu_greet_long) == 0) { $msg .= $text['message-required'].$text['label-greet_long']."<br>\n"; }
			//if (strlen($ivr_menu_greet_short) == 0) { $msg .= $text['message-required'].$text['label-greet_short']."<br>\n"; }
			//if (strlen($ivr_menu_invalid_sound) == 0) { $msg .= $text['message-required'].$text['label-invalid_sound']."<br>\n"; }
			//if (strlen($ivr_menu_exit_sound) == 0) { $msg .= $text['message-required'].$text['label-exit_sound']."<br>\n"; }
			//if (strlen($ivr_menu_confirm_macro) == 0) { $msg .= $text['message-required'].$text['label-comfirm_macro']."<br>\n"; }
			//if (strlen($ivr_menu_confirm_key) == 0) { $msg .= $text['message-required'].$text['label-comfirm_key']."<br>\n"; }
			//if (strlen($ivr_menu_tts_engine) == 0) { $msg .= $text['message-required'].$text['label-tts_engine']."<br>\n"; }
			//if (strlen($ivr_menu_tts_voice) == 0) { $msg .= $text['message-required'].$text['label-tts_voice']."<br>\n"; }
			if (strlen($ivr_menu_confirm_attempts) == 0) { $msg .= $text['message-required'].$text['label-comfirm_attempts']."<br>\n"; }
			if (strlen($ivr_menu_timeout) == 0) { $msg .= $text['message-required'].$text['label-timeout']."<br>\n"; }
			//if (strlen($ivr_menu_exit_app) == 0) { $msg .= $text['message-required'].$text['label-exit_action']."<br>\n"; }
			if (strlen($ivr_menu_inter_digit_timeout) == 0) { $msg .= $text['message-required'].$text['label-inter_digit_timeout']."<br>\n"; }
			if (strlen($ivr_menu_max_failures) == 0) { $msg .= $text['message-required'].$text['label-max_failures']."<br>\n"; }
			if (strlen($ivr_menu_max_timeouts) == 0) { $msg .= $text['message-required'].$text['label-max_timeouts']."<br>\n"; }
			if (strlen($ivr_menu_digit_len) == 0) { $msg .= $text['message-required'].$text['label-digit_length']."<br>\n"; }
			if (strlen($ivr_menu_direct_dial) == 0) { $msg .= $text['message-required'].$text['label-direct_dial']."<br>\n"; }
			//if (strlen($ivr_menu_ringback) == 0) { $msg .= $text['message-required'].$text['label-ring_back']."<br>\n"; }
			if (strlen($ivr_menu_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			//if (strlen($ivr_menu_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//array cleanup
			//remove the save
				unset($_POST["submit"]);

			//add the domain_uuid
				if (strlen($_POST["domain_uuid"] ) == 0) {
					$_POST["domain_uuid"] = $_SESSION['domain_uuid'];
				}

			//seperate the action and the param
				$exit_array = explode(":", $_POST["ivr_menu_exit_action"]);
				$_POST["ivr_menu_exit_app"] = array_shift($exit_array);
				$_POST["ivr_menu_exit_data"] = join(':', $exit_array);
				unset($_POST["ivr_menu_exit_action"]);

			//unset empty options, and seperate the option action from the param
				$x = 0;
				foreach ($_POST["ivr_menu_options"] as $row) {
					if (strlen($row["ivr_menu_option_param"]) == 0) {
						//remove the empty row
						unset($_POST["ivr_menu_options"][$x]);
					}
					else {
						//seperate the action and the param
						$options_array = explode(":", $row["ivr_menu_option_param"]);
						$_POST["ivr_menu_options"][$x]["ivr_menu_option_action"] = array_shift($options_array);
						$_POST["ivr_menu_options"][$x]["ivr_menu_option_param"] = join(':', $options_array);

						//add the domain_uuid
						if (strlen($row["domain_uuid"]) == 0) {
							$_POST["ivr_menu_options"][$x]["domain_uuid"] = $_POST["domain_uuid"];
						}
					}
					$x++;
				}

		//add or update the database
			if ($_POST["persistformvar"] != "true") {

				//used for debugging
					if ($_POST["debug"] == "true") {
						unset($_POST["debug"]);
						echo "<pre>\n";
						print_r($_POST);
						echo "</pre>\n";
						exit;
					}

				//save the ivr menu
					$ivr = new ivr_menu;
					$ivr->domain_uuid = $_SESSION["domain_uuid"];
					if (strlen($ivr_menu_uuid ) > 0) {
						$ivr->ivr_menu_uuid = $ivr_menu_uuid;
					}
					$response = $ivr->save($_POST);
					if (strlen($response['uuid']) > 0) {
						$ivr_menu_uuid = $response['uuid'];
					}

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$_SESSION["context"]);
					$cache->delete("configuration:ivr.conf:".$ivr_menu_uuid);

				//redirect the user
					$_SESSION["message"] = $text['message-update'];
					header("Location: ivr_menu_edit.php?id=".$ivr_menu_uuid);
					return;

			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (strlen($ivr_menu_uuid) == 0) { $ivr_menu_uuid = check_str($_REQUEST["id"]); }
	if (strlen($ivr_menu_uuid) > 0 && $_POST["persistformvar"] != "true") {
		$ivr = new ivr_menu;
		$ivr->domain_uuid = $_SESSION["domain_uuid"];
		$ivr->ivr_menu_uuid = $ivr_menu_uuid;
		$ivr_menus = $ivr->find();
		if (is_array($ivr_menus)) foreach ($ivr_menus as &$row) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$ivr_menu_name = $row["ivr_menu_name"];
			$ivr_menu_extension = $row["ivr_menu_extension"];
			$ivr_menu_greet_long = $row["ivr_menu_greet_long"];
			$ivr_menu_greet_short = $row["ivr_menu_greet_short"];
			$ivr_menu_invalid_sound = $row["ivr_menu_invalid_sound"];
			$ivr_menu_exit_sound = $row["ivr_menu_exit_sound"];
			$ivr_menu_confirm_macro = $row["ivr_menu_confirm_macro"];
			$ivr_menu_confirm_key = $row["ivr_menu_confirm_key"];
			$ivr_menu_tts_engine = $row["ivr_menu_tts_engine"];
			$ivr_menu_tts_voice = $row["ivr_menu_tts_voice"];
			$ivr_menu_confirm_attempts = $row["ivr_menu_confirm_attempts"];
			$ivr_menu_timeout = $row["ivr_menu_timeout"];
			$ivr_menu_exit_app = $row["ivr_menu_exit_app"];
			$ivr_menu_exit_data = $row["ivr_menu_exit_data"];
			$ivr_menu_inter_digit_timeout = $row["ivr_menu_inter_digit_timeout"];
			$ivr_menu_max_failures = $row["ivr_menu_max_failures"];
			$ivr_menu_max_timeouts = $row["ivr_menu_max_timeouts"];
			$ivr_menu_digit_len = $row["ivr_menu_digit_len"];
			$ivr_menu_direct_dial = $row["ivr_menu_direct_dial"];
			$ivr_menu_ringback = $row["ivr_menu_ringback"];
			$ivr_menu_cid_prefix = $row["ivr_menu_cid_prefix"];
			$ivr_menu_enabled = $row["ivr_menu_enabled"];
			$ivr_menu_description = $row["ivr_menu_description"];

			//replace the dash with a space
			$ivr_menu_name = str_replace("-", " ", $ivr_menu_name);

			if (strlen($ivr_menu_exit_app) > 0) {
				$ivr_menu_exit_action = $ivr_menu_exit_app.":".$ivr_menu_exit_data;
			}
		}
		unset ($prep_statement);
	}

//get the ivr menu options
	$sql = "select * from v_ivr_menu_options ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and ivr_menu_uuid = '$ivr_menu_uuid' ";
	$sql .= "order by ivr_menu_option_digits, ivr_menu_option_order asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$ivr_menu_options = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//add an empty row to the options array
	if (count($ivr_menu_options) == 0) {
		$count = 5;
		$x = 0;
	}
	if (count($ivr_menu_options) > 0) {
		$count = 1;
		$x = '';
	}
	while ($x < $count) {
		$ivr_menu_options[$x]['ivr_menu_option_digits'] = '';
		$ivr_menu_options[$x]['ivr_menu_option_action'] = '';
		$ivr_menu_options[$x]['ivr_menu_option_param'] = '';
		$ivr_menu_options[$x]['ivr_menu_option_order'] = '';
		$ivr_menu_options[$x]['ivr_menu_option_description'] = '';
		$x++;
	}

//set the defaults
	if (strlen($ivr_menu_timeout) == 0) { $ivr_menu_timeout = '3000'; }
	if (strlen($ivr_menu_ringback) == 0) { $ivr_menu_ringback = 'local_stream://default'; }
	if (strlen($ivr_menu_invalid_sound) == 0) { $ivr_menu_invalid_sound = 'ivr/ivr-that_was_an_invalid_entry.wav'; }
	//if (strlen($ivr_menu_confirm_key) == 0) { $ivr_menu_confirm_key = '#'; }
	if (strlen($ivr_menu_tts_engine) == 0) { $ivr_menu_tts_engine = 'flite'; }
	if (strlen($ivr_menu_tts_voice) == 0) { $ivr_menu_tts_voice = 'rms'; }
	if (strlen($ivr_menu_confirm_attempts) == 0) { $ivr_menu_confirm_attempts = '1'; }
	if (strlen($ivr_menu_inter_digit_timeout) == 0) { $ivr_menu_inter_digit_timeout = '2000'; }
	if (strlen($ivr_menu_max_failures) == 0) { $ivr_menu_max_failures = '1'; }
	if (strlen($ivr_menu_max_timeouts) == 0) { $ivr_menu_max_timeouts = '1'; }
	if (strlen($ivr_menu_digit_len) == 0) { $ivr_menu_digit_len = '5'; }
	if (strlen($ivr_menu_direct_dial) == 0) { $ivr_menu_direct_dial = 'false'; }
	if (strlen($ivr_menu_enabled) == 0) { $ivr_menu_enabled = 'true'; }
	if (!isset($ivr_menu_exit_action)) { $ivr_menu_exit_action = ''; }

//get the recordings
	$sql = "select recording_name, recording_filename from v_recordings ";
	$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
	$sql .= "order by recording_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$recordings = $prep_statement->fetchAll(PDO::FETCH_ASSOC);

//get the phrases
	$sql = "select * from v_phrases where domain_uuid = '".$domain_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$phrases = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//content
	require_once "resources/header.php";
	$document['title'] = $text['title-ivr_menu'];

	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	$('#show_advanced_box').slideToggle();\n";
	echo "	$('#show_advanced').slideToggle();\n";
	echo "}\n";
	echo "</script>";

	echo "<form method='post' name='frm' action=''>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left' valign='top'>";
	echo "		<b>".$text['header-ivr_menu']."</b>";
	echo "		<br><br>";
	echo "		".$text['description-ivr_menu'];
	echo "		<br><br>";
	echo "	</td>\n";
	echo "	<td align='right' nowrap='nowrap' valign='top'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='ivr_menus.php'\" value='".$text['button-back']."'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"if (confirm('".$text['confirm-copy']."')){window.location='ivr_menu_copy.php?id=".$ivr_menu_uuid."';}\" value='".$text['button-copy']."'>\n";
	echo "		<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ivr_menu_name' maxlength='255' value=\"$ivr_menu_name\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ivr_menu_extension' maxlength='255' value='$ivr_menu_extension' required='required'>\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-greet_long']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
		echo "	tb.setAttribute('style', 'width: 380px;');\n";
		echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "	tbb=document.createElement('INPUT');\n";
		echo "	tbb.setAttribute('class', 'btn');\n";
		echo "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "	tbb.type='button';\n";
		echo "	tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "	tbb.objs=[obj,tb,tbb];\n";
		echo "	tbb.onclick=function(){ Replace(this.objs); }\n";
		echo "	obj.parentNode.insertBefore(tb,obj);\n";
		echo "	obj.parentNode.insertBefore(tbb,obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "}\n";
		echo "\n";
		echo "function Replace(obj){\n";
		echo "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "	obj[0].parentNode.removeChild(obj[1]);\n";
		echo "	obj[0].parentNode.removeChild(obj[2]);\n";
		echo "}\n";
		echo "</script>\n";
		echo "\n";
	}
	echo "<select name='ivr_menu_greet_long' class='formfld' style='width: 400px;' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
	//misc optgroup
		if (if_group("superadmin")) {
			echo "<optgroup label='Misc'>\n";
			echo "	<option value='say:'>say:</option>\n";
			echo "	<option value='tone_stream:'>tone_stream:</option>\n";
			echo "</optgroup>\n";
		}
	//recordings
		$tmp_selected = false;
		if (is_array($recordings)) {
			echo "<optgroup label='Recordings'>\n";
			foreach ($recordings as &$row) {
				$recording_name = $row["recording_name"];
				$recording_filename = $row["recording_filename"];
				if ($ivr_menu_greet_long == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($ivr_menu_greet_long) > 0) {
					$tmp_selected = true;
					echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
				}
				else if ($ivr_menu_greet_long == $recording_filename && strlen($ivr_menu_greet_long) > 0) {
					$tmp_selected = true;
					echo "	<option value='".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
				}
				else {
					echo "	<option value='".$recording_filename."'>".$recording_name."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
	//phrases
		if (is_array($phrases)) {
			echo "<optgroup label='Phrases'>\n";
			foreach ($result as &$row) {
				if ($ivr_menu_greet_long == "phrase:".$row["phrase_uuid"]) {
					$tmp_selected = true;
					echo "	<option value='phrase:".$row["phrase_uuid"]."' selected='selected'>".$row["phrase_name"]."</option>\n";
				}
				else {
					echo "	<option value='phrase:".$row["phrase_uuid"]."'>".$row["phrase_name"]."</option>\n";
				}
			}
			unset ($prep_statement);
			echo "</optgroup>\n";
		}
	//sounds
		/*
		$dir_path = $_SESSION['switch']['sounds']['dir'];
		recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
		if (count($dir_array) > 0) {
			echo "<optgroup label='Sounds'>\n";
			foreach ($dir_array as $key => $value) {
				if (strlen($value) > 0) {
					if (substr($ivr_menu_greet_long, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$ivr_menu_greet_long = substr($ivr_menu_greet_long, 71);
					}
					if ($ivr_menu_greet_long == $key) {
						$tmp_selected = true;
						echo "	<option value='$key' selected='selected'>$key</option>\n";
					}
					else {
						echo "	<option value='$key'>$key</option>\n";
					}
				}
			}
			echo "</optgroup>\n";
		}
		*/
	//select
		if (if_group("superadmin")) {
			if (!$tmp_selected && strlen($ivr_menu_greet_long) > 0) {
				echo "<optgroup label='Selected'>\n";
				if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_greet_long)) {
					echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_greet_long."' selected='selected'>".$ivr_menu_greet_long."</option>\n";
				}
				else if (substr($ivr_menu_greet_long, -3) == "wav" || substr($ivr_menu_greet_long, -3) == "mp3") {
					echo "	<option value='".$ivr_menu_greet_long."' selected='selected'>".$ivr_menu_greet_long."</option>\n";
				}
				else {
					echo "	<option value='".$ivr_menu_greet_long."' selected='selected'>".$ivr_menu_greet_long."</option>\n";
				}
				echo "</optgroup>\n";
			}
			unset($tmp_selected);
		}
	echo "	</select>\n";
	echo "	<br />\n";
	echo $text['description-greet_long']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-greet_short']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='ivr_menu_greet_short' class='formfld' style='width: 400px;' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
	echo "	<option></option>\n";
	//misc
		if (if_group("superadmin")) {
			echo "<optgroup label='Misc'>\n";
			echo "	<option value='say:'>say:</option>\n";
			echo "	<option value='tone_stream:'>tone_stream:</option>\n";
			echo "</optgroup>\n";
		}
	//recordings
		$tmp_selected = false;
		if (count($recordings) > 0) {
			echo "<optgroup label='Recordings'>\n";
			foreach ($recordings as &$row) {
				$recording_name = $row["recording_name"];
				$recording_filename = $row["recording_filename"];
				if ($ivr_menu_greet_short == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($ivr_menu_greet_short) > 0) {
					$tmp_selected = true;
					echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
				}
				else if ($ivr_menu_greet_short == $recording_filename && strlen($ivr_menu_greet_short) > 0) {
					$tmp_selected = true;
					echo "	<option value='".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
				}
				else {
					echo "	<option value='".$recording_filename."'>".$recording_name."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
	//phrases
		$sql = "select * from v_phrases where domain_uuid = '".$domain_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (count($result) > 0) {
			echo "<optgroup label='Phrases'>\n";
			foreach ($result as &$row) {
				if ($ivr_menu_greet_short == "phrase:".$row["phrase_uuid"]) {
					$tmp_selected = true;
					echo "	<option value='phrase:".$row["phrase_uuid"]."' selected='selected'>".$row["phrase_name"]."</option>\n";
				}
				else {
					echo "	<option value='phrase:".$row["phrase_uuid"]."'>".$row["phrase_name"]."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
		unset ($prep_statement);

	//sounds
		/*
		$dir_path = $_SESSION['switch']['sounds']['dir'];
		recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
		if (count($dir_array) > 0) {
			echo "<optgroup label='Sounds'>\n";
			foreach ($dir_array as $key => $value) {
				if (strlen($value) > 0) {
					if (substr($ivr_menu_greet_short, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$ivr_menu_greet_short = substr($ivr_menu_greet_short, 71);
					}
					if ($ivr_menu_greet_short == $key) {
						$tmp_selected = true;
						echo "	<option value='$key' selected='selected'>$key</option>\n";
					}
					else {
						echo "	<option value='$key'>$key</option>\n";
					}
				}
			}
			echo "</optgroup>\n";
		}
		*/
	//select
		if (if_group("superadmin")) {
			if (!$tmp_selected && strlen($ivr_menu_greet_short) > 0) {
				echo "<optgroup label='Selected'>\n";
				if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_greet_short)) {
					echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_greet_short."' selected='selected'>".$ivr_menu_greet_short."</option>\n";
				}
				else if (substr($ivr_menu_greet_short, -3) == "wav" || substr($ivr_menu_greet_short, -3) == "mp3") {
					echo "	<option value='".$ivr_menu_greet_short."' selected='selected'>".$ivr_menu_greet_short."</option>\n";
				}
				else {
					echo "	<option value='".$ivr_menu_greet_short."' selected='selected'>".$ivr_menu_greet_short."</option>\n";
				}
				echo "</optgroup>\n";
			}
			unset($tmp_selected);
		}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-greet_short']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-options']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "			<table width='59%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "				<tr>\n";
	echo "					<td class='vtable'>".$text['label-option']."</td>\n";
	echo "					<td class='vtable'>".$text['label-destination']."</td>\n";
	echo "					<td class='vtable'>".$text['label-order']."</td>\n";
	echo "					<td class='vtable'>".$text['label-description']."</td>\n";
	echo "					<td></td>\n";
	echo "				</tr>\n";
	if (is_array($ivr_menu_options)) {
		$c = 0;
		foreach($ivr_menu_options as $field) {

			//add the primary key uuid
			if (strlen($field['ivr_menu_option_uuid']) > 0) {
				echo "	<input name='ivr_menu_options[".$c."][ivr_menu_option_uuid]' type='hidden' value=\"".$field['ivr_menu_option_uuid']."\">\n";
			}

			echo "<td class='formfld' align='left'>\n";
			echo "  <input class='formfld' style='width:70px' type='text' name='ivr_menu_options[".$c."][ivr_menu_option_digits]' maxlength='255' value='".$field['ivr_menu_option_digits']."'>\n";
			echo "</td>\n";

			echo "<td class='formfld' align='left' nowrap='nowrap'>\n";
			$destination_action = '';
			if (strlen($field['ivr_menu_option_action'].$field['ivr_menu_option_param']) > 0) {
				$destination_action = $field['ivr_menu_option_action'].':'.$field['ivr_menu_option_param'];
			} else { $destination_action = ''; }
			echo $destination->select('ivr', 'ivr_menu_options['.$c.'][ivr_menu_option_param]', $destination_action);
			unset($destination_action);
			echo "</td>\n";

			echo "<td class='formfld' align='left'>\n";
			echo "	<select name='ivr_menu_options[".$c."][ivr_menu_option_order]' class='formfld' style='width:55px'>\n";
			//echo "	<option></option>\n";
			if (strlen(htmlspecialchars($field['ivr_menu_option_order']))> 0) {
				if (strlen($field['ivr_menu_option_order']) == 1) { $field['ivr_menu_option_order'] = "00".$field['ivr_menu_option_order']; }
				if (strlen($field['ivr_menu_option_order']) == 2) { $field['ivr_menu_option_order'] = "0".$field['ivr_menu_option_order']; }
				echo "	<option selected='yes' value='".htmlspecialchars($field['ivr_menu_option_order'])."'>".htmlspecialchars($field['ivr_menu_option_order'])."</option>\n";
			}
			$i=0;
			while($i<=999) {
				if (strlen($i) == 1) { echo "	<option value='00$i'>00$i</option>\n"; }
				if (strlen($i) == 2) { echo "	<option value='0$i'>0$i</option>\n"; }
				if (strlen($i) == 3) { echo "	<option value='$i'>$i</option>\n"; }
				$i++;
			}
			echo "	</select>\n";
			echo "</td>\n";

			echo "<td class='formfld' align='left'>\n";
			echo "	<input class='formfld' style='width:100px' type='text' name='ivr_menu_options[".$c."][ivr_menu_option_description]' maxlength='255' value=\"".$field['ivr_menu_option_description']."\">\n";
			echo "</td>\n";

			echo "					<td class='list_control_icons'>";
			if (strlen($field['ivr_menu_option_uuid']) > 0) {
				//echo "						<a href='ivr_menu_option_edit.php?id=".$field['ivr_menu_option_uuid']."&ivr_menu_uuid=".$field['ivr_menu_uuid']."' alt='edit'>$v_link_label_edit</a>";
				echo "						<a href='ivr_menu_option_delete.php?id=".$field['ivr_menu_option_uuid']."&ivr_menu_uuid=".$field['ivr_menu_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			else {
				echo "						&nbsp;\n";
			}
			echo "					</td>\n";
			echo "				</tr>\n";

			$c++;
		}
	}
	unset($sql, $result);
/*
	for ($c = 0; $c < 1; $c++) {
		echo "				<tr>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' style='width:70px' type='text' name='ivr_menu_options[".$c."][ivr_menu_option_digits]' maxlength='255' value='$ivr_menu_option_digits'>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' nowrap='nowrap'>\n";
		echo $destination->select('ivr', 'ivr_menu_options['.$c.'][ivr_menu_option_param]', $destination_action);
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select name='ivr_menu_options[".$c."][ivr_menu_option_order]' class='formfld' style='width:55px'>\n";
		//echo "	<option></option>\n";
		if (strlen(htmlspecialchars($ivr_menu_option_order))> 0) {
			echo "	<option selected='yes' value='".htmlspecialchars($ivr_menu_option_order)."'>".htmlspecialchars($ivr_menu_option_order)."</option>\n";
		}
		$i=0;
		while($i<=999) {
			if (strlen($i) == 1) {
				echo "	<option value='00$i'>00$i</option>\n";
			}
			if (strlen($i) == 2) {
				echo "	<option value='0$i'>0$i</option>\n";
			}
			if (strlen($i) == 3) {
				echo "	<option value='$i'>$i</option>\n";
			}
			$i++;
		}
		echo "	</select>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:100px' type='text' name='ivr_menu_options[".$c."][ivr_menu_option_description]' maxlength='255' value=\"$ivr_menu_option_description\">\n";
		echo "</td>\n";

		echo "					<td>\n";
		echo "						<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
		echo "					</td>\n";
		echo "				</tr>\n";
	}
*/
	echo "			</table>\n";

	echo "			".$text['description-options']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='ivr_menu_timeout' maxlength='255' min='1' step='1' value='$ivr_menu_timeout' required='required'>\n";
	echo "<br />\n";
	echo $text['description-timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-exit_action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'ivr_menu_exit_action', $ivr_menu_exit_action);
	echo "	<br />\n";
	echo "	".$text['description-exit_action']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-direct_dial']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ivr_menu_direct_dial'>\n";
	if ($ivr_menu_direct_dial == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($ivr_menu_direct_dial == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-direct_dial']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	 ".$text['label-ring_back']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$ringbacks = new ringbacks;
	echo $ringbacks->select('ivr_menu_ringback', $ivr_menu_ringback);

	echo "<br />\n";
	echo $text['description-ring_back']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ivr_menu_cid_prefix' maxlength='255' value=\"$ivr_menu_cid_prefix\">\n";
	echo "<br />\n";
	echo $text['description-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//--- begin: show_advanced -----------------------
		echo "	<div id=\"show_advanced_box\">\n";
		echo "		<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "		<tr>\n";
		echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">&nbsp;</td>\n";
		echo "		<td width=\"70%\" class=\"vtable\">\n";
		echo "			<input type=\"button\" class='btn' onClick=\"show_advanced_config()\" value=\"".$text['button-advanced']."\"></input></a>\n";
		echo "		</td>\n";
		echo "		</tr>\n";
		echo "		</table>\n";
		echo "	</div>\n";

		echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
		echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";

		echo "<tr>\n";
		echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-invalid_sound']."\n";
		echo "</td>\n";
		echo "<td width=\"70%\" class='vtable' align='left'>\n";
		echo "<select name='ivr_menu_invalid_sound' class='formfld' style='width: 350px;' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
		//misc optgroup
			if (if_group("superadmin")) {
				echo "<optgroup label='Misc'>\n";
				echo "	<option value='phrase:'>phrase:</option>\n";
				echo "	<option value='say:'>say:</option>\n";
				echo "	<option value='tone_stream:'>tone_stream:</option>\n";
				echo "</optgroup>\n";
			}
		//recordings
			$tmp_selected = false;
			if (count($recordings) > 0) {
				echo "<optgroup label='Recordings'>\n";
				foreach ($recordings as &$row) {
					$recording_name = $row["recording_name"];
					$recording_filename = $row["recording_filename"];
					if ($ivr_menu_invalid_sound == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($ivr_menu_invalid_sound) > 0) {
						$tmp_selected = true;
						echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
					}
					else if ($ivr_menu_invalid_sound == $recording_filename && strlen($ivr_menu_invalid_sound) > 0) {
						$tmp_selected = true;
						echo "	<option value='".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
					}
					else {
						echo "	<option value='".$recording_filename."'>".$recording_name."</option>\n";
					}
				}
				echo "</optgroup>\n";
			}
		//phrases
			$sql = "select * from v_phrases where domain_uuid = '".$domain_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			if (count($result) > 0) {
				echo "<optgroup label='Phrases'>\n";
				foreach ($result as &$row) {
					if ($ivr_menu_invalid_sound == "phrase:".$row["phrase_uuid"]) {
						$tmp_selected = true;
						echo "	<option value='phrase:".$row["phrase_uuid"]."' selected='selected'>".$row["phrase_name"]."</option>\n";
					}
					else {
						echo "	<option value='phrase:".$row["phrase_uuid"]."'>".$row["phrase_name"]."</option>\n";
					}
				}
				unset ($prep_statement);
				echo "</optgroup>\n";
			}
		//sounds
			$dir_path = $_SESSION['switch']['sounds']['dir'];
			recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
			if (count($dir_array) > 0) {
				echo "<optgroup label='Sounds'>\n";
				foreach ($dir_array as $key => $value) {
					if (strlen($value) > 0) {
						if (substr($ivr_menu_invalid_sound, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
							$ivr_menu_invalid_sound = substr($ivr_menu_invalid_sound, 71);
						}
						if ($ivr_menu_invalid_sound == $key) {
							$tmp_selected = true;
							echo "	<option value='$key' selected='selected'>$key</option>\n";
						}
						else {
							echo "	<option value='$key'>$key</option>\n";
						}
					}
				}
				echo "</optgroup>\n";
			}
		//select
			if (if_group("superadmin")) {
				if (!$tmp_selected && strlen($ivr_menu_invalid_sound) > 0) {
					echo "<optgroup label='Selected'>\n";
					if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_invalid_sound)) {
						echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_invalid_sound."' selected='selected'>".$ivr_menu_invalid_sound."</option>\n";
					}
					else if (substr($ivr_menu_invalid_sound, -3) == "wav" || substr($ivr_menu_invalid_sound, -3) == "mp3") {
						echo "	<option value='".$ivr_menu_invalid_sound."' selected='selected'>".$ivr_menu_invalid_sound."</option>\n";
					}
					echo "</optgroup>\n";
				}
				unset($tmp_selected);
			}
		echo "</select>\n";
		echo "<br />\n";
		echo $text['description-invalid_sound']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-exit_sound']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "<select name='ivr_menu_exit_sound' class='formfld' style='width: 350px;' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
		echo "	<option value=''></option>\n";
		//misc optgroup
			if (if_group("superadmin")) {
				echo "<optgroup label='Misc'>\n";
				echo "	<option value='phrase:'>phrase:</option>\n";
				echo "	<option value='say:'>say:</option>\n";
				echo "	<option value='tone_stream:'>tone_stream:</option>\n";
				echo "</optgroup>\n";
			}
		//recordings
			$tmp_selected = false;
			if (count($recordings) > 0) {
				echo "<optgroup label='Recordings'>\n";
				foreach ($recordings as &$row) {
					$recording_name = $row["recording_name"];
					$recording_filename = $row["recording_filename"];
					if ($ivr_menu_exit_sound == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($ivr_menu_exit_sound) > 0) {
						$tmp_selected = true;
						echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
					}
					else if ($ivr_menu_exit_sound == $recording_filename && strlen($ivr_menu_exit_sound) > 0) {
						$tmp_selected = true;
						echo "	<option value='".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
					}
					else {
						echo "	<option value='".$recording_filename."'>".$recording_name."</option>\n";
					}
				}
				echo "</optgroup>\n";
			}
		//phrases
			$sql = "select * from v_phrases where domain_uuid = '".$domain_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			if (count($result) > 0) {
				echo "<optgroup label='Phrases'>\n";
				foreach ($result as &$row) {
					if ($ivr_menu_exit_sound == "phrase:".$row["phrase_uuid"]) {
						$tmp_selected = true;
						echo "	<option value='phrase:".$row["phrase_uuid"]."' selected='selected'>".$row["phrase_name"]."</option>\n";
					}
					else {
						echo "	<option value='phrase:".$row["phrase_uuid"]."'>".$row["phrase_name"]."</option>\n";
					}
				}
				unset ($prep_statement);
				echo "</optgroup>\n";
			}
		//sounds
			$dir_path = $_SESSION['switch']['sounds']['dir'];
			recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
			if (count($dir_array) > 0) {
				echo "<optgroup label='Sounds'>\n";
				foreach ($dir_array as $key => $value) {
					if (strlen($value) > 0) {
						if (substr($ivr_menu_exit_sound, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
							$ivr_menu_exit_sound = substr($ivr_menu_exit_sound, 71);
						}
						if ($ivr_menu_exit_sound == $key) {
							$tmp_selected = true;
							echo "	<option value='$key' selected='selected'>$key</option>\n";
						}
						else {
							echo "	<option value='$key'>$key</option>\n";
						}
					}
				}
				echo "</optgroup>\n";
			}
		//select
			if (if_group("superadmin")) {
				if (!$tmp_selected && strlen($ivr_menu_exit_sound) > 0) {
					echo "<optgroup label='Selected'>\n";
					if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_exit_sound)) {
						echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_exit_sound."' selected='selected'>".$ivr_menu_exit_sound."</option>\n";
					}
					else if (substr($ivr_menu_exit_sound, -3) == "wav" || substr($ivr_menu_exit_sound, -3) == "mp3") {
						echo "	<option value='".$ivr_menu_exit_sound."' selected='selected'>".$ivr_menu_exit_sound."</option>\n";
					}
					echo "</optgroup>\n";
				}
				unset($tmp_selected);
			}
		echo "</select>\n";
		echo "<br />\n";
		echo $text['description-exit_sound']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-comfirm_macro']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_confirm_macro' maxlength='255' value=\"$ivr_menu_confirm_macro\">\n";
		echo "<br />\n";
		echo $text['description-comfirm_macro']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-comfirm_key']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_confirm_key' maxlength='255' value=\"$ivr_menu_confirm_key\">\n";
		echo "<br />\n";
		echo $text['description-comfirm_key']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-tts_engine']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_tts_engine' maxlength='255' value=\"$ivr_menu_tts_engine\">\n";
		echo "<br />\n";
		echo $text['description-tts_engine']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-tts_voice']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_tts_voice' maxlength='255' value=\"$ivr_menu_tts_voice\">\n";
		echo "<br />\n";
		echo $text['description-tts_voice']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-comfirm_attempts']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_confirm_attempts' maxlength='255' min='1' step='1' value='$ivr_menu_confirm_attempts' required='required'>\n";
		echo "<br />\n";
		echo $text['description-comfirm_attempts']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-inter-digit_timeout']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_inter_digit_timeout' maxlength='255' min='1' step='1' value='$ivr_menu_inter_digit_timeout' required='required'>\n";
		echo "<br />\n";
		echo $text['description-inter-digit_timeout']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-max_failures']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_max_failures' maxlength='255' min='0' step='1' value='$ivr_menu_max_failures' required='required'>\n";
		echo "<br />\n";
		echo $text['description-max_failures']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-max_timeouts']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_max_timeouts' maxlength='255' min='0' step='1' value='$ivr_menu_max_timeouts' required='required'>\n";
		echo "<br />\n";
		echo $text['description-max_timeouts']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-digit_length']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_digit_len' maxlength='255' min='1' step='1' value='$ivr_menu_digit_len' required='required'>\n";
		echo "<br />\n";
		echo $text['description-digit_length']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "	</table>\n";
		echo "	</div>";

	//--- end: show_advanced -----------------------

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ivr_menu_enabled'>\n";
	if ($ivr_menu_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($ivr_menu_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='width: 450px; height: 100px;' name='ivr_menu_description'>".$ivr_menu_description."</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if (strlen($ivr_menu_uuid) > 0) {
		echo "		<input type='hidden' name='ivr_menu_uuid' value='$ivr_menu_uuid'>\n";
		echo "		<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
