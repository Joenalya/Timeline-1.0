<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB")){die("Direct initialization of this file is not allowed.");}

// hooks
$plugins->add_hook('usercp_start', 'rpgtimeline_usercp');
$plugins->add_hook('usercp_menu', 'rpgtimeline_nav', 30);
$plugins->add_hook("member_profile_end", "rpgtimeline_profile");
$plugins->add_hook ('fetch_wol_activity_end', 'rpgtimeline_user_activity');
$plugins->add_hook ('build_friendly_wol_location_end', 'rpgtimeline_location_activity');


function rpgtimeline_info()
{
    return array(
        "name"            => "Timeline (RPG-Plugin)",
        "description"    => "Ermöglicht es Usern eine eigene Timeline im UCP anzulegen, diese wird im Profil ausgegeben.",
        "website"        => "https://github.com/Joenalya",
        "author"        => "Joenalya aka. Anne",
        "authorsite"    => "https://github.com/Joenalya",
        "version"        => "1.0",
        "codename"        => "rpgtimeline",
        "compatibility" => "18"
    );
}

function rpgtimeline_install()
{
	global $db, $mybb, $cache;
	
	// create database
	$db->query("CREATE TABLE ".TABLE_PREFIX."rpgtimeline (
	`timeid` int(11) NOT NULL AUTO_INCREMENT,
	`timeuid` varchar(155) NOT NULL,
	`timename` varchar(155) NOT NULL,
	`timedate` varchar(155) NOT NULL,
	`timedesc` longtext NOT NULL,
	`timesort` int(11) NOT NULL,
	`timesecret` int(11) NOT NULL,
	PRIMARY KEY (`timeid`),
	KEY `timeid` (`timeid`)
   ) ENGINE=MyISAM".$db->build_create_table_collation());
   
	// create settinggroup
	$setting_group = array(
    	'name' => 'rpgtimelinecp',
    	'title' => 'Timeline',
    	'description' => 'Einstellungen für die Timeline.',
    	'disporder' => -1, // The order your setting group will display
    	'isdefault' => 0
	);
	
	// insert settinggroup into database
	$gid = $db->insert_query("settinggroups", $setting_group);
	
	// create settings
	$setting_array = array(
    	'rpgtimelinecp_activate' => array(
        	'title' => 'Soll die Timeline aktiviert werden?',
        	'description' => '',
        	'optionscode' => 'yesno',
        	'value' => '0', // Default
        	'disporder' => 1
    	),
    	'rpgtimelinecp_edit' => array(
        	'title' => 'Absätze bearbeiten?',
        	'description' => 'Sollen User in der Lage sein, nach dem WoB noch Absätze zu bearbeiten?',
			'optionscode'	=> 'yesno',
        	'value' => '0', // Default
        	'disporder' => 2
    	),	
    	'rpgtimelinecp_grp' => array(
        	'title' => 'Bewerbergruppe',
        	'description' => 'Welche Gruppe ist für Bewerber?',
			'optionscode'	=> 'groupselectsingle',
        	'value' => '', // Default
        	'disporder' => 3
    	),	
    	'rpgtimelinecp_secret' => array(
        	'title' => 'Geheime Absätze?',
        	'description' => 'Sollen User in der Lage sein, Absätze als Geheim zu markieren? Diese können nur das Team und der User selbst einsehen im Profil.',
			'optionscode'	=> 'yesno',
        	'value' => '0', // Default
        	'disporder' => 4
    	),			
	);

	// insert settings into database
	foreach($setting_array as $name => $setting)
	{
    	$setting['name'] = $name;
    	$setting['gid'] = $gid;

    	$db->insert_query('settings', $setting);
	}

	// Don't forget this!
	rebuild_settings();
	
    // templates
    $insert_array = array(
        'title'        => 'member_profile_rpgtimeline',
        'template'    => $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
<tr>
<td class="thead"><strong>Timeline</strong></td>
</tr>
<tr>
<td class="trow1">
<div style="display: flex;flex-direction: column;row-gap: 15px;margin: 15px 0;">{$timeline_bit}</div>	
</td>
</tr>
</table>
<br />'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
    $insert_array = array(
        'title'        => 'member_profile_rpgtimeline_bit',
        'template'    => $db->escape_string('<fieldset class="trow2" style="position: relative;">
<legend>
<strong>{$timename}</strong> 
<div style="font-size: 9px;display: block;margin-top: -2px;text-transform: uppercase;font-weight: bold;margin-bottom: -9px;">{$timedate} {$timesecret}</div>
</legend>
<div style="text-align: justify;">{$timedesc}</div>
</fieldset>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
    $insert_array = array(
        'title'        => 'rpgtimeline_nav',
        'template'    => $db->escape_string('<tr><td class="trow1 smalltext"><a href="usercp.php?action=rpgtimeline" class="usercp_nav_item usercp_nav_options">Timeline bearbeiten</a></td></tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'rpgtimeline_usercp_event',
        'template'    => $db->escape_string('<tr>
	<td class="trow1">
		<span class="largetext">{$timename}</span><br>
		<span class="smalltext">{$timedate} {$timesecret}</span>
		<div style="text-align: justify;height: 150px;overflow: auto;padding-right: 11px;margin-top: 3px;margin-right: 10px;">{$timedesc}</div>
	</td>

	<td class="trow1" align="center">
		<div style="font-size: 25px; margin-bottom: 3px;">{$sorttop}</div>
		<div style="margin-bottom: 7px;">{$editbutton}</div>
		<div>{$delbutton}</div>
		<div style="font-size: 25px;">{$sortbottom}</div>
	</td>
</tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
    $insert_array = array(
        'title'        => 'rpgtimeline_usercp_edit',
        'template'    => $db->escape_string('<html>
<head>
<title>{$lang->user_cp} - Absatz bearbeiten</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
    
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>Absatz bearbeiten</strong></td>
</tr>
<tr>
<td class="trow1" colspan="{$colspan}">    
    <form id="timelineedit" action="usercp.php?action=dotimeline_edit&edittime={$timeid}" method="post">
        <div style="display: flex;column-gap: 20px;">
            <div style="width: 50%;">    
                <span>Überschrift</span>:<br>
                <span class="smalltext">Welche Überschrift hat dieser Absatz?</span><br>
                <input style="margin-top: 6px; width: 98%;" type="text" class="textbox" name="timename" id="timename" size="40" required maxlength="255" value="{$timename}"><br><br>
            </div>

            <div style="width: 50%;">
                <span>Datum/Zeitraum</span>:<br>
                <span class="smalltext">In welchem Jahr bzw. in welcher Zeitspanne ist dieser Absatz?</span><br>
                <input style="margin-top: 6px; width: 98%;" type="text" class="textbox" name="timedate" id="timedate" size="40" required maxlength="255" value="{$timedate}"><br><br>
            </div>
        </div>

        <span>Was ist passiert?</span>:<br>
        <span class="smalltext" style="display: block;margin-bottom: 10px;margin-top: 1px;">Um was geht es in diesem Absatz? br´s sind nicht nötig um Absätze zu machen!</span>
        <textarea name="timedesc" id="timedesc" style="margin-top: 6px;height: 150px;">{$timedesc}</textarea>
        {$codebuttons}{$secretbox}
        <input type="hidden" name="timeuid" value="{$time[\'timeuid\']}">
        <input type="hidden" name="timesort" value="{$time[\'timesort\']}">
        <center><input type="submit" value="Absatz bearbeiten" name="submit" class="button"></center>    
    </form>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'rpgtimeline_usercp_bit',
        'template'    => $db->escape_string('<br>
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>Timeline</strong></td>
</tr>
<tr>
<td class="tcat" width="90%"><span class="smalltext"><strong>Infos</strong></span></td>
<td class="tcat" width="10%" align="center"><span class="smalltext"><strong>Optionen</strong></span></td>
</tr>
{$timeline_events}	
</table>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'rpgtimeline_usercp_add',
        'template'    => $db->escape_string('<br><br><fieldset class="trow2">
	<legend><strong>Absatz hinzufügen</strong></legend>
	<form id="timelineadd" action="usercp.php?action=dotimeline" method="post">
		<div style="display: flex;column-gap: 20px;">
			<div style="width: 50%;">	
				<span>Überschrift</span>:<br>
				<span class="smalltext">Welche Überschrift hat dieser Absatz?</span><br>
				<input style="margin-top: 6px; width: 98%;" type="text" class="textbox" name="timename" id="timename" size="40" required maxlength="255"><br><br>
			</div>

			<div style="width: 50%;">
				<span>Datum/Zeitraum</span>:<br>
				<span class="smalltext">In welchem Jahr bzw. in welcher Zeitspanne ist dieser Absatz?</span><br>
				<input style="margin-top: 6px; width: 98%;" type="text" class="textbox" name="timedate" id="timedate" size="40" required maxlength="255"><br><br>
			</div>
		</div>

		<span>Was ist passiert?</span>:<br>
		<span class="smalltext" style="display: block;margin-bottom: 10px;margin-top: 1px;">Um was geht es in diesem Absatz? br´s sind nicht nötig um Absätze zu machen!</span>
		<textarea name="timedesc" id="timedesc" style="margin-top: 6px; height: 150px;"></textarea>
		{$codebuttons}{$secretbox}
		<center><input type="submit" value="Absatz erstellen" name="submit" class="button"></center>	
	</form>
</fieldset>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'rpgtimeline_usercp',
        'template'    => $db->escape_string('<html>
<head>
<title>{$lang->user_cp} - Timeline bearbeiten</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>Timeline bearbeiten</strong></td>
</tr>
<tr>
<td class="trow1" colspan="{$colspan}">	
Hier könnt ihr Informationen angeben. Wie viele Absätze sind gewünscht? Wie lange muss jeder Absatz sein etc.
{$timeline_add}	
</td>
</tr>
</table>	
{$timeline_bit}
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);		
}

function rpgtimeline_is_installed()
{
    global $db;
    if($db->table_exists("rpgtimeline"))
    {
        return true;
    }
    return false;
}

function rpgtimeline_uninstall() 
{
	global $db, $cache;
	
	// drop database
	$db->query("DROP TABLE ".TABLE_PREFIX."rpgtimeline");
	
    // drop templates
    $db->delete_query("templates", "title LIKE '%rpgtimeline%'");
	
	// drop settings
	$db->delete_query('settings', "name LIKE '%rpgtimelinecp_%'");
	$db->delete_query('settinggroups', "name = 'rpgtimelinecp'");
}

function rpgtimeline_activate()
{
    global $mybb;
	
 	// edit templates
 	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", "#".preg_quote('{$awaybit}')."#i", '{$awaybit} {$member_profile_timeline}');
}
	
function rpgtimeline_deactivate()
{
    global $mybb;
	
	// edit templates
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", "#".preg_quote('{$member_profile_timeline}')."#i", '', 0);
	  
	// Don't forget this
	rebuild_settings();
}

function rpgtimeline_usercp() {
	global $mybb, $db, $cache, $plugins, $templates, $theme, $lang, $header, $headerinclude, $footer, $parser, $options, $usercpnav, $codebuttons;
	
	if($mybb->input['action'] == "rpgtimeline"){


		$rpgtimeline_active = (int)$mybb->settings['rpgtimelinecp_activate'];
		
		if($rpgtimeline_active != "1" || $mybb->user['uid'] == "0"){
			 error_no_permission(); 
		} else {
			
			$moveup = $mybb->get_input('moveup');
			if($moveup) {
				$check = $db->fetch_field($db->query("SELECT timeuid FROM ".TABLE_PREFIX."rpgtimeline WHERE timeid = '$moveup'"), "timeuid");
				$sort = $db->fetch_field($db->query("SELECT timesort FROM ".TABLE_PREFIX."rpgtimeline WHERE timeid = '$moveup'"), "timesort");
				$sort++;
				$new_record = array(
				  "timesort" => $sort
				);
				
				$checker = $db->fetch_field($db->query("SELECT timeid FROM ".TABLE_PREFIX."rpgtimeline WHERE timeuid = '$check' AND timesort = '$sort'"), "timeid");
				$sort--;
				$new_record_old = array(
				  "timesort" => $sort
				);
				
				if($mybb->user['uid'] == $check){
					$db->update_query("rpgtimeline", $new_record, "timeid = '$moveup'");
					$db->update_query("rpgtimeline", $new_record_old, "timeid = '$checker'");
					redirect("usercp.php?action=rpgtimeline");
				};
			}  
			
			$movedown = $mybb->get_input('movedown');
			if($movedown) {
				$check = $db->fetch_field($db->query("SELECT timeuid FROM ".TABLE_PREFIX."rpgtimeline WHERE timeid = '$movedown'"), "timeuid");
				$sort = $db->fetch_field($db->query("SELECT timesort FROM ".TABLE_PREFIX."rpgtimeline WHERE timeid = '$movedown'"), "timesort");
				$sort--;
				$new_record = array(
				  "timesort" => $sort
				);
				
				$checker = $db->fetch_field($db->query("SELECT timeid FROM ".TABLE_PREFIX."rpgtimeline WHERE timeuid = '$check' AND timesort = '$sort'"), "timeid");
				$sort++;
				$new_record_old = array(
				  "timesort" => $sort
				);
				
				if($mybb->user['uid'] == $check){
					$db->update_query("rpgtimeline", $new_record, "timeid = '$movedown'");
					$db->update_query("rpgtimeline", $new_record_old, "timeid = '$checker'");
					redirect("usercp.php?action=rpgtimeline");
				};
				
			}
			
			$delete = $mybb->get_input('timedel');
			if($delete) {
				$check = $db->fetch_field($db->query("SELECT timeuid FROM ".TABLE_PREFIX."rpgtimeline WHERE timeid = '$delete'"), "timeuid");
				if($mybb->user['uid'] == $check){
					$db->delete_query("rpgtimeline", "timeid = '$delete'");
					redirect("usercp.php?action=rpgtimeline");
				};
			}
			
			
			$timeid = $mybb->user['uid'];
			$mode_type = (int)$mybb->settings['rpgtimelinecp_secret'];
			$time_grp = (int)$mybb->settings['rpgtimelinecp_grp'];
			$time_edittype = (int)$mybb->settings['rpgtimelinecp_edit'];
			
			$timename = "";
			$sortbottom = "";
			$sorttop = "";
			$timesort = "";
			$timeline_bit = "";
			$timeline_events = "";
			$query = $db->query("
			SELECT * FROM ".TABLE_PREFIX."rpgtimeline
			WHERE timeuid LIKE '$timeid'
			ORDER by timesort ASC");
			$timecounts = mysqli_num_rows($query);
			while($time = $db->fetch_array($query)) {
				$timename = $time['timename'];
				$timedate = $time['timedate'];
				$timedesc = $time['timedesc'];
				$timesort = $time['timesort'];
				
				if($time['timesecret'] == "1" && $mode_type == "1") {
					$timesecret = "# Geheim";
				} elseif($time['timesecret'] == "0" && $mode_type == "1") {
					$timesecret = "# Öffentlich";
				} else {
					$timesecret = "";
				}
				
				  $options = array(
					"allow_html" => 1,
					"allow_mycode" => 1,
					"allow_smilies" => 0,
					"allow_imgcode" => 0,
					"allow_videocode" => 0,
					"nl2br" => 1
				  );
				$timedesc = $parser->parse_message($timedesc, $options);
				
				
				if($time_edittype != "1" && ($mybb->user['usergroup'] != $time_grp && $mybb->user['additionalgroups'] != $time_grp)) {
					$editbutton = "";
					$delbutton = "";
				}else {
					$editbutton = "<a href=\"/usercp.php?action=timeline_edit&timeedit={$time['timeid']}\"><input type=\"submit\" value=\"Bearbeiten\" class=\"button\"></a>";
					$delbutton = "<a href=\"/usercp.php?action=rpgtimeline&timedel={$time['timeid']}\"><input type=\"submit\" value=\"Löschen\" class=\"button\"></a>";
				}
				
				
				if($timesort == "1"){$sorttop = "";}else{$sorttop = "<a href=\"/usercp.php?action=rpgtimeline&movedown={$time['timeid']}\">↑</a>";}; 
				if($timesort >= $timecounts){$sortbottom = "";}else{$sortbottom = "<a href=\"/usercp.php?action=rpgtimeline&moveup={$time['timeid']}\">↓</a>";};
				
				
				eval("\$timeline_events .= \"".$templates->get("rpgtimeline_usercp_event")."\";");
			}
			if(!empty($timeline_events)) {eval("\$timeline_bit .= \"".$templates->get("rpgtimeline_usercp_bit")."\";");};
			
			if($mode_type == "1") { $secretbox = "<input type=\"checkbox\" class=\"checkbox\" name=\"timesecret\" value=\"1\"> Soll dieser Absatz nur für das Team sichtbar sein?";};
			
			$codebuttons = build_mycode_inserter("timedesc");
			if(function_exists('markitup_run_build')) {
				markitup_run_build('timedesc');
			};
			
			if($time_edittype != "1" && ($mybb->user['usergroup'] != $time_grp && $mybb->user['additionalgroups'] != $time_grp)) {
				$timeline_add = "";
			} else {
				eval("\$timeline_add = \"".$templates->get("rpgtimeline_usercp_add")."\";");
			}
			
			$colspan = 2;
			eval("\$page= \"".$templates->get("rpgtimeline_usercp")."\";");
			output_page($page);
		}
		
	}
	
	if($mybb->input['action'] == "timeline_edit"){
		$rpgtimeline_active = (int)$mybb->settings['rpgtimelinecp_activate'];
		
		$timeid = $mybb->get_input('timeedit');
		$mode_type = (int)$mybb->settings['rpgtimelinecp_secret'];
		$check = $db->fetch_field($db->query("SELECT timeuid FROM ".TABLE_PREFIX."rpgtimeline WHERE timeid = '$timeid'"), "timeuid");
		
		if($rpgtimeline_active != "1" || $mybb->user['uid'] != $check){
			 error_no_permission(); 
		} else {
			
			$query = $db->query("SELECT * FROM ".TABLE_PREFIX."rpgtimeline WHERE timeid = '$timeid'");
			$time = $db->fetch_array($query);
			
			$scheck = "";
			
			$timeid = $time['timeid'];
			$timename = $time['timename'];
			$timedate = $time['timedate'];
			$timedesc = $time['timedesc'];
			
			if($time['timesecret'] == "1") { $scheck = "checked=\"\" ";};
			if($mode_type == "1") { $secretbox = "<input type=\"checkbox\" class=\"checkbox\" name=\"timesecret\" value=\"1\" {$scheck}> Soll dieser Absatz nur für das Team sichtbar sein?";};
			
			$codebuttons = build_mycode_inserter("timedesc");
			if(function_exists('markitup_run_build')) {
				markitup_run_build('timedesc');
			};
			
			$colspan = 2;
			eval("\$page= \"".$templates->get("rpgtimeline_usercp_edit")."\";");
			output_page($page);
		}
	}
	
	if($mybb->input['action'] == "dotimeline_edit") {
		$edittime = $mybb->get_input('edittime');
		if($mybb->request_method == "post") {
				
			$new_record = array(			
				"timename" => $db->escape_string($mybb->get_input('timename')),
				"timedate" => $db->escape_string($mybb->get_input('timedate')),	  
				"timedesc" => $db->escape_string($mybb->get_input('timedesc')),
				"timeuid" => $db->escape_string($mybb->get_input('timeuid')),
				"timesecret" => $db->escape_string($mybb->get_input('timesecret')),
				"timesort" => $db->escape_string($mybb->get_input('timesort')),
			);
			$db->update_query("rpgtimeline", $new_record, "timeid = '$edittime'");
			redirect("usercp.php?action=rpgtimeline");
		}
	}
	
	if($mybb->input['action'] == "dotimeline") {
		
		$timelineadd = $mybb->get_input('timelineadd');
		if($mybb->request_method == "post") {
			
			$time_sort = "";
			$time_uid = $mybb->user['uid'];
			$time_count = $db->query("SELECT COUNT(*) AS count FROM ".TABLE_PREFIX."rpgtimeline WHERE timeuid LIKE '$time_uid' ");
			while($time_claimcount = $db->fetch_array($time_count)){ $time_sort = $time_claimcount['count']; }
			  
			$time_sort++;
			
			$new_record = array(			
				"timename" => $db->escape_string($mybb->get_input('timename')),
				"timedate" => $db->escape_string($mybb->get_input('timedate')),	  
				"timedesc" => $db->escape_string($mybb->get_input('timedesc')),
				"timeuid" => $mybb->user['uid'],
				"timesecret" => $db->escape_string($mybb->get_input('timesecret')),
				"timesort" => $time_sort
			);
			$db->insert_query("rpgtimeline", $new_record);
			redirect("usercp.php?action=rpgtimeline");
		}
	}	
}

function rpgtimeline_profile()
{
	global $mybb, $db, $templates, $theme, $parser, $lang, $memprofile, $member_profile_timeline, $parser, $options;
	
	$rpgtimeline_active = (int)$mybb->settings['rpgtimelinecp_activate'];
	if($rpgtimeline_active == "1") {
		$timeplayer = $memprofile['uid'];
		$time_type = (int)$mybb->settings['rpgtimelinecp_secret'];
			
		if($time_type == "1"){
			if($mybb->usergroup['cancp'] != "1" && $timeplayer != $mybb->user['uid']) {
				$timesql = "AND timesecret NOT LIKE '1'";
			} else {
				$timesql = "";
			}
		} else {
			$timesql = "";
		}
		
		$timeline_bit = "";
		$timeline_events = "";
		$query = $db->query("
		SELECT * FROM ".TABLE_PREFIX."rpgtimeline
		WHERE timeuid LIKE '$timeplayer'
		{$timesql}
		ORDER by timesort ASC");
		while($time = $db->fetch_array($query)) {
			
			$timename = $time['timename'];
			$timedate = $time['timedate'];
			$timedesc = $time['timedesc'];
			$timesort = $time['timesort'];
				
			if($time_type == "1"){
				if($time['timesecret'] == "1") {
					$timesecret = "# Geheim";
				} else {
					$timesecret = "";
				}
			};
				
			$options = array(
				"allow_html" => 1,
				"allow_mycode" => 1,
				"allow_smilies" => 0,
				"allow_imgcode" => 0,
				"allow_videocode" => 0,
				"nl2br" => 1
			);
			$timedesc = $parser->parse_message($timedesc, $options);
			
			eval("\$timeline_bit .= \"".$templates->get("member_profile_rpgtimeline_bit")."\";");	
		}
		
		if(empty($timeline_bit)) {$timeline_bit = "<div class=\"Profil_InfText\">{$memprofile['username']} hat keine Timeline eingetragen.</div>";};
		if($mybb->user['uid'] == "0") {$timeline_bit = "Die Timeline von {$memprofile['username']} kann nicht von Gästen eingesehen werden.";};
		eval("\$member_profile_timeline = \"".$templates->get("member_profile_rpgtimeline")."\";");	
	};
	
}

function rpgtimeline_user_activity($user_activity)
{
    global $user;

    if (isset($user['location']) && my_strpos($user['location'], "usercp.php?action=rpgtimeline") !== false) {
        $user_activity['activity'] = "rpgtimeline";
    }

    return $user_activity;
}

function rpgtimeline_location_activity($plugin_array)
{
    global $db, $mybb, $lang;

    if ($plugin_array['user_activity']['activity'] == "rpgtimeline") {
        $plugin_array['location_name'] = "Bearbeitet die eigene <b><a href='usercp.php?action=rpgtimeline'>Timeline</a></b>.";
    }

    return $plugin_array;
}

function rpgtimeline_nav() {
	global $mybb, $templates, $lang, $usercpmenu;
	eval("\$usercpmenu .= \"".$templates->get("rpgtimeline_nav")."\";");
}
?>
