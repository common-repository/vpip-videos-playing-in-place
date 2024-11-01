<?php
/*
Plugin Name: vPIP
Plugin URI: http://vpip.org
Description: <=[wordpress plugin version, not vPIP version]  vPIP (videos Playing In Place) let's you specify a movie that will only embed after a poster image or link is clicked.
Author: Enric Teller
Version: 0.13i
Author URI: http://www.vpip.org

New:
    * Removed "publish_post" and "save_post" hooks which had problems in Wordpress 2.6.  
    * 	Only using "edit_post" hook.  

Next:
	* Share code options of Link or Embed with Help (link to help page on sharing code.)
	* Backup/Restore vPIP DB
	
 * License (X11 License)
 * ===================================================================
 *  Copyright 2006-2007  Enric Teller  (email: enric@vpip.org)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.

 * Except as contained in this notice, the name of the author or copyright
 * holders shall not be used in advertising or otherwise to promote the sale,
 * use or other dealings in this Software without prior written authorization
 * from the author or copyright holders.
 *
 * ===================================================================
 *

*/

	//Tables constants
	define("TBL_MEDIADEFAULT", $GLOBALS['wpdb']->prefix . "vPIP_MediaDefault");
	define("TBL_VIDEOFORMATS", $GLOBALS['wpdb']->prefix . "vPIP_VideoFormats");
	define("TBL_VIDEOFMTSDEFAULT", $GLOBALS['wpdb']->prefix . "vPIP_VideoFmtsDefault");
	define("TBL_MEDIAFOR", $GLOBALS['wpdb']->prefix . "vPIP_MediaFor");
	define("TBL_MEDIAFORCONNECT", $GLOBALS['wpdb']->prefix . "vPIP_MediaForConnect");
	define("TBL_VIDEOFORMAT", $GLOBALS['wpdb']->prefix . "vPIP_VideoFormat");

	if (function_exists('get_option'))
	{
	   $vPIPLocation = get_option("vPIP_Location");
	   if ($vPIPLocation == null || strlen($vPIPLocation) == 0)
	   		$vPIPLocation = get_settings("siteurl") . "/wp-content/plugins/vPIP";

	}

	function _vPIP_Options_Page() {
		global $vPIPLocation;

		echo "
			<div class=\"wrap\">
				<h2>vPIP Settings</h2>
				<h3 style=\"margin-bottom: 1px;\" >vPIP operations settings</h3>
				";

		$sInterface = '';
		$sInsertJSHead = '';
		$sAutoVPIP = '';
		$sEnableThickBox = '';
		$sShowCodeGen = '';
		$sBracketedCode = '';

		$table_name = TBL_VIDEOFORMATS;
		$byUpdatingTables = false;
		// Check if Media Entry tables needs to be created
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
		{
			_vPIP_DBCreateTables();
			$byUpdatingTables = true;
		}
		else if(get_option("vpip_db_version") != "1.21") // Update to 1.21
		{
			_vPIP_DBUpdateTo(get_option("vpip_db_version"), "1.21");
			$byUpdatingTables = true;
		}

		//If vPIP fields have not yet been initialized, do it
		if (get_option("vPIP_Init") === false) {
			$sInterface = 'vPIPMediaEntry';
			$sInsertJSHead = 'on';
			$sAutoVPIP = 'on';
			$sEnableThickBox = 'on';
			$sShowCodeGen = 'on';
			$vPIPLocation = get_settings("siteurl") . "/wp-content/plugins/vPIP";
			$sBracketedCode = 'off';

			update_option('vPIP_Interface', $sInterface);
			update_option('vPIP_InsertJSHead', $sInsertJSHead);
			update_option('vPIP_AutoVPIP', $sAutoVPIP);
			update_option('vPIP_EnableThickBox', $sEnableThickBox);
			update_option('vPIP_ShowCodeGen', $sShowCodeGen);
			update_option('vPIP_Location', $vPIPLocation);
			update_option('vPIP_BracketedCode', $sBracketedCode);
			update_option('vPIP_Init', 'true');
			echo "	<p>Init</p>\n";
		}
		// If updating to submitted form
		else if ($_POST['vPIPOptions_Displayed']) {

			//*** Update Settings ***
			//$aVideoWidth and $aVideoHeight will hold arrays of the video format
			//   widths and heights
			$aUpdate = array();
			$aMediaNames = _vPIP_GetTableCol(TBL_VIDEOFORMATS, "mediaName");

			foreach($aMediaNames as $aMediaName)
			{
				$mediaName_underscored = strtr($aMediaName->mediaName, " ", "_");
				$sVideoWidthName = "vPIP_" . $mediaName_underscored . "_width";
				$sVideoHeightName = "vPIP_" . $mediaName_underscored . "_height";
				if ($_POST["vPIP_isDefault"] == $mediaName_underscored)
					$iVideo_isDefault = "1";
				else
					$iVideo_isDefault = "0";
				$iVideo_displayOrder = $_POST["vPIP_" . $mediaName_underscored . "_displayOrder"];
				if (isset($_POST["vPIP_" . $mediaName_underscored . "_isActive"]) )
					$iVideo_isActive = "1";
				else
					$iVideo_isActive = "0";
				if (isset($_POST["vPIP_" . $mediaName_underscored . "_isActive"]) )
					$iVideo_isActive = "1";
				else
					$iVideo_isActive = "0";
				if (isset($_POST["vPIP_" . $mediaName_underscored . "_isVisible"]) )
					$iVideo_isVisible = "1";
				else
					$iVideo_isVisible = "0";

				//        where clause                                     set clause
				$aUpdate["mediaName = '" . $aMediaName->mediaName . "'"] = "width = " .
										$_POST[$sVideoWidthName] . ", height = " .
										$_POST[$sVideoHeightName] . ", isDefault = " .
										$iVideo_isDefault . ", displayOrder = " .
										$iVideo_displayOrder . ", isActive = " .
										$iVideo_isActive . ", isVisible = " .
										$iVideo_isVisible;
			}
			_vPIP_UpdateTable(TBL_VIDEOFORMATS, $aUpdate);

			// *** Update Flash External Player settings ***
			$aUpdate = array();
			if (isset($_POST["vPIP_useExtFlashPlayer"]))
				$useExtFlashPlayer = 1;
			else
				$useExtFlashPlayer = 0;
			if (isset($_POST["vPIP_offerEmbedCode"]))
				$offerEmbedCode = 1;
			else
				$offerEmbedCode = 0;

			$FLVPlayerURL = $_POST["vPIP_FLVPlayerURL"];
			$FLVParam = $_POST["vPIP_FLVParam"];
			$FLVParamLoc = $_POST["vPIP__FLVParamLoc"];
			$FLVFlashVar = $_POST["vPIP_FLVFlashVar"];
			$FLVFlashVar = $_POST["vPIP_FLVFlashVar"];
			$embedCodeBtnTitle = $_POST["vPIP_EmbedBtnTitle"];
			if (isset($_POST["vPIP_embedCodeStyled"]))
				$embedCodeStyled = 1;
			else
				$embedCodeStyled = 0;
			$aUpdate[""] = "useExtFlashPlayer = " . $useExtFlashPlayer .
				", extFlashPlayer = '" . $FLVPlayerURL .
				"', movieParam = '" . $FLVParam .
				"', movieParamLoc = '" . $FLVParamLoc .
				"', extFlashPlayerFlashVars = '" . $FLVFlashVar . "'" .
				", offerEmbedCode = " . $offerEmbedCode .
				", embedCodeBtnTitle = '" . $embedCodeBtnTitle .
				"', embedCodeStyled = " . $embedCodeStyled;
			_vPIP_UpdateTable(TBL_MEDIADEFAULT, $aUpdate);

			echo "	<div class=\"updated\">\n";

			//*** Get settings ***
			if( $_POST['vPIP_Interface']) {
				$sInterface = 'vPIPMediaEntry';
			}
			else {
				$sInterface = 'vpip';
			}
			if (update_option('vPIP_Interface', $sInterface))
				echo "		<p>vPIP interface updated.</p>\n";

			if( $_POST['vPIP_InsertJSHead']) {
				$sInsertJSHead = 'on';
			}
			else {
				$sInsertJSHead = 'off';
			}
			if (update_option('vPIP_InsertJSHead', $sInsertJSHead))
				echo "		<p>Inserting vPIP links into HTML head area updated.</p>\n";

			if ($_POST['vPIP_AutoVPIP']) {
				$sAutoVPIP = 'on';
			}
			else {
				$sAutoVPIP = 'off';
			}

			if (update_option('vPIP_AutoVPIP', $sAutoVPIP))
				echo "		<p>Automatically make movie links work with vPIP updated.</p>\n";

			if ($_POST['vPIP_EnableThickBox']) {
				$sEnableThickBox = 'on';
			}
			else {
				$sEnableThickBox = 'off';
			}

			if (update_option('vPIP_EnableThickBox', $sEnableThickBox))
				echo "		<p>Enable ThickBox option updated.</p>\n";

			if ($_POST['vPIP_ShowCodeGen']) {
				$sShowCodeGen = 'on';
			}
			else {
				$sShowCodeGen = 'off';
			}

			if (update_option('vPIP_ShowCodeGen', $sShowCodeGen))
				echo "		<p>Show vPIP Code Generator option updated.</p>\n";

			if ($_POST['vPIP_Location']) {
				$vPIPLocation = $_POST['vPIP_Location'];
			}

			if ($_POST['vPIP_BracketedCode']) {
				$sBracketedCode = 'on';
			}
			else {
				$sBracketedCode = 'off';
			}

			if (update_option('vPIP_BracketedCode', $sBracketedCode))
				echo "		<p>Generating vPIP bracketed code updated.</p>\n";

			if (update_option('vPIP_Location', $vPIPLocation))
				echo "		<p>vPIP Plugin location updated.</p>\n";
			echo "	</div>\n";
		}
		// Page display (before submit), get db values
		else {
			$sInterface = get_option("vPIP_Interface");
			if ($sInterface === false)
				$sInterface = 'vPIPMediaEntry';
			$sInsertJSHead = get_option("vPIP_InsertJSHead");
			if ($sInsertJSHead === false)
				$sInsertJSHead = 'on';
			$sAutoVPIP = get_option("vPIP_AutoVPIP");
			if ($sAutoVPIP == null) {
				$sAutoVPIP = "on";
				update_option('vPIP_AutoVPIP', $sAutoVPIP);
			}
			$sEnableThickBox = get_option("vPIP_EnableThickBox");
			if ($sEnableThickBox == null) {
				$sEnableThickBox = "on";
				update_option('vPIP_EnableThickBox', $sEnableThickBox);
			}
			$sShowCodeGen = get_option("vPIP_ShowCodeGen");
			if ($sShowCodeGen == null) {
				$sShowCodeGen = "on";
				update_option('vPIP_ShowCodeGen', $sShowCodeGen);
			}
			$sBracketedCode = get_option("vPIP_BracketedCode");
			$vPIPLocation = get_option("vPIP_Location");
			if (strlen($vPIPLocation) == 0) {
				$vPIPLocation = get_settings("siteurl") . "/wp-content/plugins/vPIP";
				update_option('vPIP_Location', $vPIPLocation);
			}

		}


		echo "
			<script type=\"text/javascript\" src=\"" . $vPIPLocation . "/jquery.js\"></script>
			<script type=\"text/javascript\" src=\"" . $vPIPLocation . "/vpipwp.js\"></script>

				<form name=\"frmvPIP\" id=\"frmvPIP\" method=\"post\" >
				<fieldset class=\"options\">

					<p style=\"margin-left: 20px; \">Options Description:
					<ul style=\"margin-left: 25px; \">
					<li>Use the Media Entry interface.  </li>
					<li>When theme offers support, select whether to have vPIP insert the link
					references to the vPIP code in the &lt;HEAD> section of the web page.</li>
					<li>Whether to make &lt;a href=\"...\" ...>...&lt;/a> links to movies automatically
					work with vPIP.</li>
					<li>Whether the ThickBox capability is enabled.</li>
					<li>Whether the vPIP Code Generator is visible when writing a post.</li>
					<li>And whether your using vPIP bracketed code (usually only for the multi-user
					version of Wordpress.)</li></ul></p>
					<hr />
			";

		if ($sInterface === false || $sInterface == "vPIPMediaEntry" || $sInterface == "vlosplosion")
		{
			echo "		<input type=\"checkbox\" name=\"vPIP_Interface\" id=\"vPIP_Interface\" checked=\"checked\" onclick=\"showvPIPMediaEntry();\" />\n";;
		}
		else
		{
			echo "		<input type=\"checkbox\" name=\"vPIP_Interface\" id=\"vPIP_Interface\" onclick=\"showvPIPMediaEntry();\" />\n";;
		}
		echo "		Use the Media Entry interface for media entry and theme display?<br />\n";

		if (! $byUpdatingTables)
		{
			$aVideoFormats = _vPIP_GetTableRows(TBL_VIDEOFORMATS, NULL, "displayOrder");
			$aMediaFor = _vPIP_GetTableRows(TBL_MEDIAFOR, NULL, NULL);
			echo "
						<div id=\"vPIPMediaEntryOptions\" style=\"margin-left: 17px; color: #BB0000; \" >
							<div id=\"vPIPMediaEntryTable\" >
								<br /><table border=\"2\" cellpadding=\"2\" >
								<caption style=\"font-weight: bold; text-align: center; margin-bottom: 2px; \" >Media Entry settings</caption>
								<tr id=\"tblMediaTitle\" align=\"center\">
									<td>Media Type</td>
									<td>Width</td>
									<td>Height</td>
									<td>Default</td>
									<td>Order</td>
									<td>Active</td>
									<td>Visible</td>
									<td>For</td>
									<td>Feed</td>
								</tr>\n";

			$iVideoFormatsCount = count($aVideoFormats);
			for ($i=0; $i< $iVideoFormatsCount; $i++)
			{
				$aVideoFormat = $aVideoFormats[$i];
				$sMediaName = strtr($aVideoFormat->mediaName, " ", "_");

				echo "
									<tr>";
								/* TODO: Next Release:
										<td align=\"left\">" . $aVideoFormat->mediaName . " (" . $aVideoFormat->descript . ")&nbsp;&nbsp;&nbsp; <a href=\"\">Edit</a> | <a href=\"\">Delete</a></td>
									*/
				if (strlen(trim($aVideoFormat->descript)) > 0)
				{
					echo "
										<td align=\"left\">" . $aVideoFormat->mediaName . " (" . $aVideoFormat->descript . ")</td>";

				}
				else {
					echo "
										<td align=\"left\">" . $aVideoFormat->mediaName . "</td>";

				}
				echo "
										<td><input type=\"text\" name=\"vPIP_" . $sMediaName . "_width\" size=\"4\" maxlength=\"4\" value=\"" . $aVideoFormat->width . "\" /></td>
										<td><input type=\"text\" name=\"vPIP_" . $sMediaName . "_height\" size=\"4\" maxlength=\"4\" value=\"" . $aVideoFormat->height . "\" /></td>";
				$isChecked = $aVideoFormat->isDefault == 1?"checked=\"checked\"":"";
				echo "
										<td align=\"center\"><input type=\"radio\" name=\"vPIP_isDefault\" " . $isChecked . " value=\"" . $sMediaName . "\" /></td>
										<td align=\"center\">
											<select name=\"vPIP_" . $sMediaName . "_displayOrder\" id=\"vPIP_" . $sMediaName . "_displayOrder\" onchange=\"vPIPWP_SetDisplayOrder('vPIP_" . $sMediaName . "_displayOrder', 'frmvPIP', 'vPIPMediaEntryTable');\" >";
				for ($j= 0; $j < $iVideoFormatsCount; $j++)
				{
					if ((int)$aVideoFormat->displayOrder == ($j+1))
					{
						echo "
												<option value=\"" . ($j+1) . "\" selected=\"selected\" >" . ($j+1) . "</option>";
					}
					else
					{
						echo "
												<option value=\"" . ($j+1) . "\">" . ($j+1) . " </option>";
					}
				}
				echo "
											</select>
										</td>
	";
				$isChecked = $aVideoFormat->isActive == 1?"checked=\"checked\"":"";
				echo "
										<td align=\"center\"><input type=\"checkbox\" name=\"vPIP_" . $sMediaName . "_isActive\" value=\"\" " . $isChecked . " /></td>\n";
				$isChecked = $aVideoFormat->isVisible == 1?"checked=\"checked\"":"";
				echo "
										<td align=\"center\"><input type=\"checkbox\" name=\"vPIP_" . $sMediaName . "_isVisible\" value=\"\" " . $isChecked . " /></td>
	";
				//Media is For
				echo "
										<td align=\"center\"><textarea cols=\"13\" rows=\"1\" name=\"vPIP_" . $sMediaName . "_mediaFor\" readonly=\"readonly\" style=\"font-size: 10px\" >" . _vPIP_getMediaFor($aVideoFormat->mediaName, false) . "</textarea> </td>
	";
	/*
											<select name=\"vPIP_" . $sMediaName . "_mediaFor\" id=\"vPIP_" . $sMediaName . "_mediaFor\" onchange=\"\" >
				for ($j=0; $j<count($aMediaFor); $j++)
				{
						if (_vPIP_isMediaForSelected($aMediaFor[$j]->mediaFor, $aVideoFormat->mediaName, false))
						{
							echo "
													<option value=\"" . $aMediaFor[$j]->mediaFor . "\" selected=\"selected\" >" . $aMediaFor[$j]->mediaFor . "</option>";
						}
						else
						{
							echo "
													<option value=\"" . $aMediaFor[$j]->mediaFor . "\" >" . $aMediaFor[$j]->mediaFor . "</option>";
						}

				}
				echo "
											</select>
										</td>
	";*/

				//vPIP unique feed for Media Entry:
				$blogURL = get_bloginfo('url');
				$href = $vPIPLocation . "/vPIPFeed.php?blogURL=" . $blogURL . "&media=" . urlencode("vs-title: " . $aVideoFormat->mediaName);
				echo "
										<td align=\"center\"><a href=\"" .  $href . "\" style=\"border:none;\" /><img src=\"" . $vPIPLocation . "/feed-icon-14x14.png\" alt=\"Right click & copy Link/Shortcut.\" title=\"Right click & copy Link/Shortcut.\" /></a></td>
									</tr>
	";
			}
			echo "
							</table>
						</div>
	";
			/* TODO: Next release
							<span style=\"text-align: left; margin-top: 3px; \">&nbsp;&nbsp;<a href=\"\" onclick=\"addMediaLine(this); return false;\" >Add</a> media line</span>
							<div id=\"divAddMediaLine\" style=\"margin-left: 10px;\" >
							</div><br />
							*/
			$aMediaDefault = _vPIP_GetTableRows(TBL_MEDIADEFAULT);
			$useExtFlashPlayerChecked = $aMediaDefault[0]->useExtFlashPlayer == 1?"checked=\"checked\"":"";
			$URLMovieParamLoc = $aMediaDefault[0]->movieParamLoc == "URL"?"checked=\"checked\"":"";
			$FlashVarsMovieParamLoc = $aMediaDefault[0]->movieParamLoc == "FlashVars"?"checked=\"checked\"":"";

			echo "
						<div id=\"vPIPPleaseWait\" style=\"position:absolute;\" >
						</div>
	";
			//FIXME:  Styled should now refer to entire Media Entry area
			$embedCodeStyledChecked = $aMediaDefault[0]->embedCodeStyled == 1?"checked=\"checked\"":"";
			echo "
						<input type=\"checkbox\" name=\"vPIP_embedCodeStyled\" id=\"vPIP_embedCodeStyled\" " . $embedCodeStyledChecked . "/> Styled?<br /><br />
						<span style=\"margin-left: 9px; \"><input type=\"checkbox\" name=\"vPIP_useExtFlashPlayer\" id=\"vPIP_useExtFlashPlayer\" onclick=\"useExtFlashPlayer(this);\" " . $useExtFlashPlayerChecked . "/> Use an external Flash Player?</span>
						<div id=\"divUseExternalFlashPlayer\" style=\"margin-left: 10px; \" >
							<table border='1' cellpadding='2' cellspacing='2' >
								<caption style=\"font-weight: 600; text-align: center; margin-bottom: 1px; \" >External Flash Player Settings</caption>
								<tr align=\"center\">
									<td colspan=\"2\" >URL to external Flash player<br /><input type=\"text\" name=\"vPIP_FLVPlayerURL\" size=\"90\" maxlength=\"255\" value=\"" . $aMediaDefault[0]->extFlashPlayer . "\" /></td>
								</tr>
								<tr align=\"left\">
									<td>Parameter to open movie: <input type=\"text\" name=\"vPIP_FLVParam\" size=\"25\" maxlength=\"500\" value=\"" . $aMediaDefault[0]->movieParam . "\" /></td>
									<td>Parameter Location:  &nbsp;&nbsp;<input type=\"radio\" name=\"vPIP__FLVParamLoc\" " . $URLMovieParamLoc . " value=\"URL\" > URL  <input type=\"radio\" name=\"vPIP__FLVParamLoc\" " . $FlashVarsMovieParamLoc . " value=\"FlashVars\" /> FlashVars</td>
								</tr>
								<tr align=\"left\">
									<td colspan=\"2\">FlashVar parameters: <input type=\"text\" name=\"vPIP_FLVFlashVar\" size=\"70\" maxlength=\"255\" value=\"" . $aMediaDefault[0]->extFlashPlayerFlashVars . "\" /></td>
								</tr>
							</table>
						</div>
	";
			$offerEmbedCodeChecked = $aMediaDefault[0]->offerEmbedCode == 1?"checked=\"checked\"":"";
			echo "
						<p style=\"margin-left: 9px; \"><input type=\"checkbox\" name=\"vPIP_offerEmbedCode\" id=\"vPIP_offerEmbedCode\" onclick=\"offerEmbedCode(this);\" " . $offerEmbedCodeChecked . "/> Offer embed code?
						<div id=\"divOfferEmbedCode\" style=\"margin-left: 10px; \" >
							<table border='1' cellpadding='5' cellspacing='2' style=\"margin-left: 10px;\">
								<caption style=\"font-weight: 600;  font-size: 12px; text-align: center; margin-bottom: 1px; \" >Embed Code Settings</caption>
								<tr align=\"left\">
									<td>Embed button title: <input type=\"text\" name=\"vPIP_EmbedBtnTitle\" size=\"20\" maxlength=\"255\" value=\"" . $aMediaDefault[0]->embedCodeBtnTitle . "\" />
								</tr>
							</table>
						</div></p>
				</div><br />
	";
		}
		else
		{
			echo "<p><div style=\"text-align: center; color: red; \" >vPIP tables updated, please refresh page...</div></p>
";
		}
		if ($sInterface !== false && $sInterface != "vPIPMediaEntry" && $sInterface != "vlogsplosion")
		{
			echo "
				<script type=\"text/javascript\">
					divVS = \"vPIPMediaEntryOptions\";
					jQuery(\"#\" + divVS).hide();
				</script>";
		}

		$echoText = (!($sInsertJSHead === false) && $sInsertJSHead == "on")?"checked=\"checked\"":"";
		echo "		<input type=\"checkbox\" name=\"vPIP_InsertJSHead\" " . $echoText . " />\n";;
		echo "		When supported by theme, insert vPIP javascript links into page &lt;HEAD> section?<br />\n";
		$echoText = (!($sAutoVPIP === false) && $sAutoVPIP == 'on')?"checked=\"checked\"":"";
		echo "		<input type=\"checkbox\" name=\"vPIP_AutoVPIP\" " . $echoText . " />\n";
		echo "		Automatically make movie links work with vPIP?<br />\n";
		$echoText = (!($sEnableThickBox === false) && $sEnableThickBox == 'on')?"checked=\"checked\"":"";
		echo "		<input type=\"checkbox\" name=\"vPIP_EnableThickBox\" " . $echoText . " />\n";
		echo "		Enable ThickBox?<br />\n";
		$echoText = (!($sShowCodeGen === false) && $sShowCodeGen == 'on')?"checked=\"checked\"":"";
		echo "		<input type=\"checkbox\" name=\"vPIP_ShowCodeGen\" " . $echoText . " />\n";
		echo "		Show vPIP Code Generator?<br />\n";
		$echoText = (!($sBracketedCode === false) && $sBracketedCode == 'on')?"checked=\"checked\"":"";
		echo "
				<input type=\"checkbox\" name=\"vPIP_BracketedCode\" " . $echoText . " />
				Use vPIP bracketed code?<br />
				<p  style=\"text-align: left; color: #DD2200;\">vPIP Plugin location <br />
				<input type=\"text\" name=\"vPIP_Location\" size=\"100\" maxlength=\"255\" value=\"" . $vPIPLocation . "\" /></p>
				<input type=\"hidden\" name=\"vPIPOptions_Displayed\" value=\"I'm here!\" />
				<p class=\"submit\" style=\"text-align: center;\"><input type=\"submit\" value=\"Change vPIP options\" style=\" color: #0022FF;\" /></p>
			</fieldset>
		</form>

		<script type=\"text/javascript\">

			divVS = \"vPIPMediaEntryOptions\";

			if (! jQuery('#vPIP_useExtFlashPlayer').is(\":checked\"))
				jQuery(\"#divUseExternalFlashPlayer\").hide();
			if (! jQuery('#vPIP_offerEmbedCode').is(\":checked\"))
				jQuery(\"#divOfferEmbedCode\").hide();

			String.prototype.trim = function() {
				return this.replace(/^\s+|\s+$/g,\"\");
			}
			String.prototype.ltrim = function() {
				return this.replace(/^\s+/,\"\");
			}
			String.prototype.rtrim = function() {
				return this.replace(/\s+$/,\"\");
			}

			function showvPIPMediaEntry()
			{
				//Get if #vPIP_Interface is checked to byOpen
				byOpen = jQuery('#vPIP_Interface').is(\":checked\");

				if (byOpen)
				{
					jQuery(\"#\" + divVS).show();
				}
				else
				{
					jQuery(\"#\" + divVS).hide();
				}
			}

";

		/* TODO: Next release
			function addMediaLine() {
				sMediaLine =  \"<form name='frmMediaLine' id='frmMediaLine'>\";
				sMediaLine += \"	<table border='1' cellpadding='2' style='margin-left: 20px;'>\";
				sMediaLine += \"		<tr align=center >\";
				sMediaLine += \"			<td>Media name</td>\";
				sMediaLine += \"			<td>Media description</td>\";
				sMediaLine += \"			<td>mimetype</td>\";
				sMediaLine += \"			<td>width</td>\";
				sMediaLine += \"			<td>height</td>\";
				sMediaLine += \"			<td>default</td>\";
				sMediaLine += \"			<td>order</td>\";
				sMediaLine += \"			<td>active</td>\";
				sMediaLine += \"			<td>visible</td>\";
				sMediaLine += \"			<td>for</td>\";
				sMediaLine += \"		</tr>\";
				sMediaLine += \"		<tr align=center >\";
				sMediaLine += \"			<td><input type='text' value='' size='20' maxlength='30' name='vPIP_AddLine_mediaName' ></td>\";
				sMediaLine += \"			<td><input type='text' value='' size='20' maxlength='30' name='vPIP_AddLine_descript' ></td>\";
				sMediaLine += \"			<td><input type='text' value='' size='15' maxlength='20' name='vPIP_AddLine_Mimetype' ></td>\";
				sMediaLine += \"			<td><input type='text' value='' size='6' maxlength='3' name='vPIP_AddLine_width' ></td>\";
				sMediaLine += \"			<td><input type='text' value='' size='6' maxlength='3' name='vPIP_AddLine_height' ></td>\";
				sMediaLine += \"			<td><input type='checkbox' ></td>\";
				sMediaLine += \"			<td><select name='vPIP_AddMedia_displayOrder' name='vPIP_AddLine_displayOrder' >\";";
				for ($k= 0; $k < $iVideoFormatsCount+1; $k++)
				{
					if ($k == $iVideoFormatsCount)
					{
						echo "
				sMediaLine += \"				<option value='" . ($k+1) . "' selected='selected' >" . ($k+1) . "</option>\";\n";
					}
					else
					{
						echo "
				sMediaLine += \"				<option value='" . ($k+1) . "'>" . ($k+1) . "</option>\";\n";

					}
				}
				echo "
				sMediaLine += \"			</select></td>\";
				sMediaLine += \"			<td><input type='checkbox' name='vPIP_AddLine_isActive' checked='checked' ></td>\";
				sMediaLine += \"			<td><input type='checkbox' name='vPIP_AddLine_isVisible' checked='checked' ></td>\";
				sMediaLine += \"		</tr>\";
				sMediaLine += \"	</table>\";
				sMediaLine += \"	<span style='text-align: left; margin-top: 3px; margin-left: 20px; '>&nbsp;&nbsp;<a href='' onclick='saveMediaLine(this); return false;' >Save</a> entry | <a href='' onclick='discardMediaLine(); return false;' >Discard</a> entry </span>\";
				jQuery(\"#divAddMediaLine\").html(sMediaLine);
			}

			function discardMediaLine()
			{
				jQuery(\"#divAddMediaLine\").html(\"\");
			}
*/
		echo "

			function useExtFlashPlayer(oCheckbox) {
				if (oCheckbox.checked)
				{
					jQuery(\"#divUseExternalFlashPlayer\").show();

				}
				else
				{
					jQuery(\"#divUseExternalFlashPlayer\").hide();

				}
			}

			function offerEmbedCode(oCheckbox) {
				if (oCheckbox.checked)
				{
					jQuery(\"#divOfferEmbedCode\").show();

				}
				else
				{
					jQuery(\"#divOfferEmbedCode\").hide();

				}
			}

			function transferEnclosureFields()
			{
				 jQuery(\"#vPIPPleaseWait\").html(\"Please wait...<img src=" . $vPIPLocation . "/pulse.gif />\");
				 jQuery(\"#vPIPPleaseWait\").show();
				 jQuery.post(\"" . $vPIPLocation . "/vPIPVlogsplosionOps.php\",
					   { op: \"transferEnclosureFields\" },
					   function(data){
				 			jQuery(\"#vPIPPleaseWait\").html(\"\");
					     	alert(data);
					   }
					 );
			}

			//!!! This is not yet working !!!
			function vPIP_centerDiv(oDiv)
			{
				  var x = (window.innerWidth / 2) - (oDiv.offsetWidth / 2);
				  var y = (window.innerHeight / 2) - (oDiv.offsetHeight / 2);
				  jQuery(\"#vPIPPleaseWait\").css({top: y, left: x});
				  //oDiv.style.top = y;
				  //oDiv.style.left = x;
				  //oDiv.style.display = \"block\";

			}

		</script>
		";

	}

	function _vPIP_AddOptionsPage() {
	    if (function_exists('add_options_page')) {
			add_options_page('vPIP Options', 'vPIP Config', 8, basename(__FILE__), '_vPIP_Options_Page');
	    }
	 }

// A C T I O N S ////////////////////////////////////////////////////////////////////////////////////////////////////////////

	function add_vpip_js() {
		global $vPIPLocation;

		if (get_option("vPIP_InsertJSHead") === false || get_option("vPIP_InsertJSHead") == 'on')
		{


			echo "	<script src=\"" . $vPIPLocation . "/vpip.js?fresh=" . time() . "\" type=\"text/javascript\"></script>\n";
			if ((get_option("vPIP_EnableThickBox") === false) || get_option("vPIP_EnableThickBox") == 'on')
			{
				echo "	<style type=\"text/css\" media=\"all\">@import \"" . $vPIPLocation . "/vPIPBox.css\";</style>
						<script src=\"" . $vPIPLocation . "/jquery.js\" type=\"text/javascript\"></script>\n";
			}
			if ((get_option("vPIP_AutoVPIP") === false) || get_option("vPIP_AutoVPIP") == 'on')
			{
				echo "<script src=\"" . $vPIPLocation . "/vpipit.js\" type=\"text/javascript\"></script>\n";
			}
		}
	}

	/* For writing vPIP code either through the Media Entry interface or vPIP Code
	 * Generator.
	 */
	function _vPIP_Write()
	{

		if (get_option("vPIP_ShowCodeGen") === false || get_option("vPIP_ShowCodeGen") == 'on') {
			global $vPIPLocation;

			$sInterface = get_option("vPIP_Interface");
			if ($sInterface === false || $sInterface == 'vPIPMediaEntry' || $sInterface == 'vlogsplosion')
			{
				//If VideoFormats table doesn't exist, assume Media Entry tables not
				//  yet created, and create.
				$table_name = TBL_VIDEOFORMATS;
				//Check if table already exists
				$byUpdatingTables = false;
				if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
				{
					_vPIP_DBCreateTables();
					$byUpdatingTables = true;
				}
				else if(get_option("vpip_db_version") != "1.21") // Update to 1.21
				{
					_vPIP_DBUpdateTo(get_option("vpip_db_version"), "1.21");
					$byUpdatingTables = true;
				}

				if (!$byUpdatingTables)
				{
					//echo "<p style=\"text-align: center; font-size: 14px; text-decoration: underline;\"></p>\n";
					$aVideoFormats = _vPIP_GetTableRows(TBL_VIDEOFORMATS, NULL, "displayOrder");
					if ($GLOBALS['post']->ID)
					{
						$oValues = _vPIP_GetVideoFormat(-1, $GLOBALS['post']->ID);
					}
					else
						$oValues = NULL;
					echo "
					<div id=\"vPIPMediaEntryMedia\" class=\"dbx-b-ox-wrapper\" style=\"margin-left: 10px; color: #8b4513; \" >
						<fieldset id=\"vPIP\" class=\"dbx-box\">
						<div class=\"dbx-h-andle-wrapper\">
							<h3 class=\"dbx-handle\">vPIP Media Entry</h3>
						</div>
						<div class=\"dbx-c-ontent-wrapper\">
						<div id=\"vPIPMediaEntryMediaLines\">
							<br /><table border=\"2\" cellpadding=\"2\">
							<!-- caption style=\"font-weight: bold; text-align: center;  margin-bottom: 2px;\" >vPIP Media Entry</caption -->
							<tr style=\"font-size: 10px\" >
							<td align=\"center\" style=\"font-size: 11px\" >Poster Image (png, jpg or gif): </td>
							";
					if ($oValues && count($oValues) > 0)
						echo "				<td align=\"center\" colspan=\"8\" style=\"font-size: 11px\" >URL<br /><input type=\"text\" name=\"vPIP_PosterImage_URL\" size=\"75\" maxlength=\"255\" value=\"" . $oValues[0]->url . "\" style=\"font-size: 11px\" /></td>\n";
					else
						echo "				<td align=\"center\" colspan=\"8\" style=\"font-size: 11px\" >URL<br /><input type=\"text\" name=\"vPIP_PosterImage_URL\" size=\"75\" maxlength=\"255\" value=\"\" style=\"font-size: 11px\" /></td>\n";
					echo "
						</tr>
						<tr>
							<td align=\"center\" style=\"font-size: 11px\" >Thumbnail Image (png, jpg or gif): </td>
							";

					if ($GLOBALS['post']->ID)
						$oValues = _vPIP_GetVideoFormat(-2, $GLOBALS['post']->ID);
					else
						$oValues = NULL;
					if ($oValues && count($oValues) > 0)
						echo "				<td align=\"center\" colspan=\"8\" style=\"font-size: 11px\" ><input type=\"text\" name=\"vPIP_ThumbnailImage_URL\" size=\"75\" maxlength=\"255\" value=\"" . $oValues[0]->url . "\" style=\"font-size: 11px\" /></td>\n";
					else
						echo "				<td align=\"center\" colspan=\"8\" style=\"font-size: 11px\" ><input type=\"text\" name=\"vPIP_ThumbnailImage_URL\" size=\"75\" maxlength=\"255\" value=\"\" style=\"font-size: 11px\" /></td>\n";

					echo "
						</tr>
						<tr align=\"center\" id=\"tblMediaTitle\">
							<td style=\"font-size: 11px\" >Media Type</td>
							<td style=\"font-size: 11px\" >URL to media file</td>
							<td style=\"font-size: 11px\" >Width</td>
							<td style=\"font-size: 11px\" >Height</td>
							<td style=\"font-size: 11px\" >Default</td>
							<td style=\"font-size: 11px\" >Order</td>
							<td style=\"font-size: 11px\" >Active</td>
							<td style=\"font-size: 11px\" >Visible</td>
							<td style=\"font-size: 11px\" >For</td>
						</tr>
	";
					$iVideoFormatsCount = count($aVideoFormats);
					$avPIPMediaEntrys = array();
					$avPIPMediaEntryLine = array();
					//Media Entry line entries that conflict with ones already in.
					$avPIPMediaEntrysToAdd = array();


					//Accumulate into array to be sorted by displayOrder
					foreach ($aVideoFormats as $aVideoFormat)
					{
						$avPIPMediaEntryLine['mediaName'] = $aVideoFormat->mediaName; //strtr($aVideoFormat->mediaName, " ", "_");

						if ($GLOBALS['post']->ID)
							$oValues = _vPIP_GetVideoFormat($aVideoFormat->id, $GLOBALS['post']->ID);

						if (! is_null($aVideoFormat->descript)  && 	strlen(trim($aVideoFormat->descript)) > 0)
							$avPIPMediaEntryLine['descript'] = $avPIPMediaEntryLine['mediaName'] . " (" . $aVideoFormat->descript . ")";
						else
							$avPIPMediaEntryLine['descript'] = $avPIPMediaEntryLine['mediaName'];

						//If Video Format values already entered, display
						if ($oValues && count($oValues) > 0)
						{
							$avPIPMediaEntryLine['url'] = $oValues[0]->url;
							$avPIPMediaEntryLine['width'] = $oValues[0]->width;
							$avPIPMediaEntryLine['height'] = $oValues[0]->height;
							$avPIPMediaEntryLine['isDefault'] = $oValues[0]->isDefault;
							$avPIPMediaEntryLine['displayOrder'] = $oValues[0]->displayOrder;
							$avPIPMediaEntryLine['isActive'] = $oValues[0]->isActive;
							$avPIPMediaEntryLine['isVisible'] = $oValues[0]->isVisible;
							$avPIPMediaEntryLine['useExtFlashPlayer'] = $oValues[0]->useExtFlashPlayer;
							$avPIPMediaEntryLine['extFlashPlayer'] = $oValues[0]->extFlashPlayer;
							$avPIPMediaEntryLine['movieParam'] = $oValues[0]->movieParam;
							$avPIPMediaEntryLine['movieParamLoc'] = $oValues[0]->movieParamLoc;
							$avPIPMediaEntryLine['extFlashPlayerFlashVars'] = $oValues[0]->extFlashPlayerFlashVars;
							$avPIPMediaEntryLine['offerEmbedCode'] = $oValues[0]->offerEmbedCode;
							$avPIPMediaEntryLine['embedCodeBtnTitle'] = $oValues[0]->embedCodeBtnTitle;
							$avPIPMediaEntryLine['embedCodeDefault'] = $oValues[0]->embedCodeDefault;
							$avPIPMediaEntryLine['embedCodeStyled'] = $oValues[0]->embedCodeStyled;
							$avPIPMediaEntryLine['embedCodeInThickBox'] = $oValues[0]->embedCodeInThickBox;
							$avPIPMediaEntryLine['embedCodeThickBoxTitle'] = $oValues[0]->embedCodeThickBoxTitle;
						}
						//Else show default values
						else
						{
							$avPIPMediaEntryLine['url'] = "";
							$avPIPMediaEntryLine['width'] = $aVideoFormat->width;
							$avPIPMediaEntryLine['height'] = $aVideoFormat->height;
							$avPIPMediaEntryLine['isDefault'] = $aVideoFormat->isDefault;
							$avPIPMediaEntryLine['displayOrder'] = $aVideoFormat->displayOrder;
							$avPIPMediaEntryLine['isActive'] = $aVideoFormat->isActive;
							$avPIPMediaEntryLine['isVisible'] = $aVideoFormat->isVisible;
							$avPIPMediaEntryLine['useExtFlashPlayer'] = -1;
							$avPIPMediaEntryLine['extFlashPlayer'] = "";
							$avPIPMediaEntryLine['movieParam'] = "";
							$avPIPMediaEntryLine['movieParamLoc'] = "";
							$avPIPMediaEntryLine['extFlashPlayerFlashVars'] = "";
							$avPIPMediaEntryLine['offerEmbedCode'] = -1;
							$avPIPMediaEntryLine['embedCodeBtnTitle'] = "";
							$avPIPMediaEntryLine['embedCodeDefault'] = "";
							$avPIPMediaEntryLine['embedCodeStyled'] = -1;
							$avPIPMediaEntryLine['embedCodeInThickBox'] = -1;
							$avPIPMediaEntryLine['embedCodeThickBoxTitle'] = "";
						}

						//If not already entered, put in media line
						if ($avPIPMediaEntrys[$avPIPMediaEntryLine['displayOrder']] == null)
						{
						$avPIPMediaEntrys[$avPIPMediaEntryLine['displayOrder']] = $avPIPMediaEntryLine;
					}
						else
						{
							$avPIPMediaEntrysToAdd[] =  $avPIPMediaEntryLine;
						}
					}

					//Insert any out of order entries
					foreach ($avPIPMediaEntrysToAdd as $avPIPMediaEntryToAdd)
					{
						$byAdded = false;
						for ($i=1; $i<=count($avPIPMediaEntrys); $i++)
						{
							if ($avPIPMediaEntrys[$i] == null)
							{
								$avPIPMediaEntryToAdd['displayOrder'] = $i;
								$avPIPMediaEntrys[$i] = $avPIPMediaEntryToAdd;
								$byAdded = true;
								break;
							}
						}
						if (!$byAdded)
						{
							$avPIPMediaEntryToAdd['displayOrder'] = count($avPIPMediaEntrys)+1;
							$avPIPMediaEntrys[] = $avPIPMediaEntryToAdd;
						}
					}

					$iFlashIndex = -1;
					for ($i=1; $i<=count($avPIPMediaEntrys); $i++)
					{
						$avPIPMediaEntry = $avPIPMediaEntrys[$i];

						if ($avPIPMediaEntry == null)
							continue;

						if (strtolower($avPIPMediaEntry['mediaName']) == "flash")
							$iFlashIndex = $i;

						echo "			<tr style=\"font-size: 10px\" >\n";
						echo "				<td align=\"left\" style=\"font-size: 11px\" >" . $avPIPMediaEntry['descript'] . "</td>\n";
						echo "				<td style=\"font-size: 11px\" ><input type=\"text\" name=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_URL\" id=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_URL\" size=\"25\" maxlength=\"255\" value=\"" . $avPIPMediaEntry['url'] . "\"  style=\"font-size: 11px\" /></td>\n";
						echo "				<td align=\"right\" style=\"font-size: 11px\" ><input type=\"text\" name=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_width\" id=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_width\" size=\"4\" maxlength=\"4\" value=\"" . $avPIPMediaEntry['width'] .  "\"  style=\"font-size: 11px\" /></td>\n";
						echo "				<td align=\"right\" style=\"font-size: 11px\" ><input type=\"text\" name=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_height\" id=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_height\" size=\"4\" maxlength=\"4\" value=\"" . $avPIPMediaEntry['height'] . "\"  style=\"font-size: 11px\" /></td>\n";
						$checked = $avPIPMediaEntry['isDefault'] == 1?"checked=\"checked\"":"";

						echo "				<td align=\"center\" style=\"font-size: 11px\" ><input type=\"radio\" name=\"vPIP_isDefault\" id=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_isDefault\" value=\"" . $avPIPMediaEntry['mediaName'] . "\" " . $checked . "  style=\"font-size: 11px\" /></td>\n";
						echo "				<td align=\"center\" style=\"font-size: 11px\" >
						<select name=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_displayOrder\" id=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_displayOrder\" class=\"vPIP_displayOrder\"  onchange=\"vPIPWP_SetDisplayOrder('vPIP_" . $avPIPMediaEntry['mediaName'] . "_displayOrder', 'post', 'vPIPMediaEntryMediaLines');\" >";
						for ($j= 1; $j <= $iVideoFormatsCount; $j++)
						{
							if ($avPIPMediaEntry['displayOrder'] == $j)
								echo "				<option value=\"" . ($j) . "\" selected=\"selected\" >" . ($j) . "</option>";
							else
								echo "				<option value=\"" . ($j) . "\">" . ($j) . " </option>";
						}
						echo "				</select>
								</td>\n";
						$checked = $avPIPMediaEntry['isActive'] == 1?"checked=\"checked\"":"";
						echo "				<td align=\"center\" style=\"font-size: 11px\" ><input type=\"checkbox\" name=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_isActive\" value=\"\" " . $checked . "  style=\"font-size: 11px\" /></td>\n";
						$checked = $avPIPMediaEntry['isVisible'] == 1?"checked=\"checked\"":"";
						echo "				<td align=\"center\" style=\"font-size: 11px\" ><input type=\"checkbox\" name=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_isVisible\" value=\"\" " . $checked . "  style=\"font-size: 11px\" /></td>
						<td align=\"center\"><textarea cols=\"13\" rows=\"1\" name=\"vPIP_" . $avPIPMediaEntry['mediaName'] . "_mediaFor\" readonly=\"readonly\" style=\"font-size: 10px\" >" . _vPIP_getMediaFor($avPIPMediaEntry['mediaName'], false) . "</textarea> </td>
	";
						echo "			</tr>\n";
					}

					$avPIPMediaEntry = $avPIPMediaEntrys[$iFlashIndex];
					$aMediaDefault = _vPIP_GetTableRows(TBL_MEDIADEFAULT);
					if ($avPIPMediaEntry['useExtFlashPlayer'] > -1) {
						$useExtFlashPlayerChecked = $avPIPMediaEntry['useExtFlashPlayer'] == 1?"checked=\"checked\"":"";
						$extFlashPlayer = $avPIPMediaEntry['extFlashPlayer'];
						$URLMovieParamLoc = $avPIPMediaEntry['movieParamLoc'] == "URL"?"checked=\"checked\"":"";
						$movieParam = $avPIPMediaEntry['movieParam'];
						$FlashVarsMovieParamLoc = $avPIPMediaEntry['movieParamLoc'] == "FlashVars"?"checked=\"checked\"":"";
						$extFlashPlayerFlashVars = $avPIPMediaEntry['extFlashPlayerFlashVars'];
					}
					else
					{
						$useExtFlashPlayerChecked = $aMediaDefault[0]->useExtFlashPlayer == 1?"checked=\"checked\"":"";
						$extFlashPlayer = $aMediaDefault[0]->extFlashPlayer;
						$URLMovieParamLoc = $aMediaDefault[0]->movieParamLoc == "URL"?"checked=\"checked\"":"";
						$movieParam = $aMediaDefault[0]->movieParam;
						$FlashVarsMovieParamLoc = $aMediaDefault[0]->movieParamLoc == "FlashVars"?"checked=\"checked\"":"";
						$extFlashPlayerFlashVars = $aMediaDefault[0]->extFlashPlayerFlashVars;
					}
					if ($avPIPMediaEntry['offerEmbedCode'] > -1) {
						$offerEmbedCodeChecked = $avPIPMediaEntry['offerEmbedCode'] == 1?"checked=\"checked\"":"";
						$embedCodeBtnTitle = $avPIPMediaEntry['embedCodeBtnTitle'];
						$embedCodeDefault = $avPIPMediaEntry['embedCodeDefault'];
						//FIXME:  Styled should now refer to entire Media Entry area
						$embedCodeStyledChecked = $avPIPMediaEntry['embedCodeStyled'] == 1?"checked=\"checked\"":"";
						$embedCodeInThickBoxChecked = $avPIPMediaEntry['embedCodeInThickBox'] == 1?"checked=\"checked\"":"";
						$embedCodeThickBoxTitle = $avPIPMediaEntry['embedCodeThickBoxTitle'];
					}
					else
					{
						$offerEmbedCodeChecked = $aMediaDefault[0]->offerEmbedCode == 1?"checked=\"checked\"":"";
						$embedCodeBtnTitle = $aMediaDefault[0]->embedCodeBtnTitle;
						$embedCodeDefault = $aMediaDefault[0]->embedCodeDefault;
						//FIXME:  Styled should now refer to entire Media Entry area
						$embedCodeStyledChecked = $aMediaDefault[0]->embedCodeStyled == 1?"checked=\"checked\"":"";
						$embedCodeInThickBoxChecked = $aMediaDefault[0]->embedCodeInThickBox == 1?"checked=\"checked\"":"";
						$embedCodeThickBoxTitle = $aMediaDefault[0]->embedCodeThickBoxTitle;
					}

					echo "
								</table>
							</div>";
					//FIXME:  Styled should now refer to entire Media Entry area
					echo "
							<input type=\"checkbox\" name=\"vPIP_embedCodeStyled\" id=\"vPIP_embedCodeStyled\" " . $embedCodeStyledChecked . "/> Styled?<br /><br />
							<span style=\"margin-left: 9px; font-size: 11px; \"><input type=\"checkbox\" name=\"vPIP_useExtFlashPlayer\" id=\"vPIP_useExtFlashPlayer\" onclick=\"useExtFlashPlayer(this);\" " . $useExtFlashPlayerChecked . "/> Use an external Flash Player?</span><br />
							<div id=\"vPIPPleaseWait\" style=\"position:absolute;\" >
							</div>
							<div id=\"divUseExternalFlashPlayer\" style=\"margin-left: 10px; \" >
								<table border='1' cellpadding='2' cellspacing='2' style='font-size: 11px; ' >
									<caption style=\"font-weight: 600;  font3-size: 12px; text-align: center; margin-bottom: 1px; \" >External Flash Player Settings</caption>
									<tr style=\"text-align: center; \" >
										<td colspan=\"2\" ><span  style=\"font-size: 11px;\" >URL to external Flash player<br /><input type=\"text\" name=\"vPIP_FLVPlayerURL\" size=\"90\" maxlength=\"255\" value=\"" . $extFlashPlayer . "\" /></span></td>
									</tr>
									<tr style=\"text-align: left; \" >
										<td><span  style=\"font-size: 11px;\" >Parameter to open movie: <input type=\"text\" name=\"vPIP_FLVParam\" size=\"25\" maxlength=\"500\" value=\"" . $movieParam . "\" /></span></td>
										<td><span  style=\"font-size: 11px;\" >Parameter Location:  &nbsp;&nbsp;<input type=\"radio\" name=\"vPIP__FLVParamLoc\" " . $URLMovieParamLoc . " value=\"URL\" /> URL  <input type=\"radio\" name=\"vPIP__FLVParamLoc\" " . $FlashVarsMovieParamLoc . " value=\"FlashVars\" /> FlashVars</span></td>
									</tr>
									<tr style=\"text-align: left; \" >
										<td colspan=\"2\"><span  style=\"font-size: 11px;\" >FlashVar parameters: <input type=\"text\" name=\"vPIP_FLVFlashVar\" size=\"70\" maxlength=\"255\" value=\"" . $extFlashPlayerFlashVars . "\" /></span></td>
									</tr>
								</table>
							</div>
							<p style=\"margin-left: 9px; \"><input type=\"checkbox\" name=\"vPIP_offerEmbedCode\" id=\"vPIP_offerEmbedCode\" onclick=\"offerEmbedCode(this);\" " . $offerEmbedCodeChecked . "/> Offer embed code?
							<div id=\"divOfferEmbedCode\" style=\"margin-left: 10px; \" >
								<table border='1' cellpadding='5' cellspacing='2' style=\"margin-left: 10px;\">
									<caption style=\"font-weight: 600;  font-size: 12px; text-align: center; margin-bottom: 1px; \" >Embed Code Settings</caption>
									<tr align=\"left\">
										<td>Embed button title: <input type=\"text\" name=\"vPIP_EmbedBtnTitle\" size=\"20\" maxlength=\"255\" value=\"" . $embedCodeBtnTitle . "\" />
									</tr>
								</table>
							</div></p>
                            </div>
                            </fieldset>
						</div><br />
			<script type=\"text/javascript\" src=\"" . $vPIPLocation . "/jquery.js\"></script>
			<script type=\"text/javascript\" src=\"" . $vPIPLocation . "/vpipwp.js\"></script>
			<script type=\"text/javascript\">

				if (! jQuery('#vPIP_useExtFlashPlayer').is(\":checked\"))
					jQuery(\"#divUseExternalFlashPlayer\").hide();
				if (! jQuery('#vPIP_offerEmbedCode').is(\":checked\"))
				{
					jQuery(\"#divOfferEmbedCode\").hide();
				}

				function useExtFlashPlayer(oCheckbox) {
					if (oCheckbox.checked)
					{
						jQuery(\"#divUseExternalFlashPlayer\").show();

					}
					else
					{
						jQuery(\"#divUseExternalFlashPlayer\").hide();

					}
				}

				function offerEmbedCode(oCheckbox) {
					if (oCheckbox.checked)
					{
						jQuery(\"#divOfferEmbedCode\").show();

					}
					else
					{
						jQuery(\"#divOfferEmbedCode\").hide();

					}
				}

			</script>
			";

				}
				else
				{
					echo "<p><div style=\"text-align: center; color: red;\" >vPIP tables updated, please refresh page...</div></p>
";
				}

			}
			else if ($sInterface == 'vpip')
			{
				echo "<br />\n";
				echo "<div style=\"text-align:center;\">\n";
				echo "	<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0\" width=\"640\" height=\"300\" id=\"GenvPIP0-07\" align=\"middle\">\n";
				echo "	<param name=\"allowScriptAccess\" value=\"sameDomain\" />\n";
				echo "	<param name=\"movie\" value=\"" . $vPIPLocation . "/GenvPIP0-07.swf\" />\n";
				echo "	<param name=\"quality\" value=\"high\" />\n";
				echo "	<param name=\"bgcolor\" value=\"#ddccee\" />\n";

		       if (!(get_option("vPIP_BracketedCode") === false) && get_option("vPIP_BracketedCode") == 'on')
					echo "<PARAM NAME=FlashVars VALUE=\"bracketed=true\">\n";
				else
					echo "<PARAM NAME=FlashVars VALUE=\"bracketed=false\">\n";

				echo "	<embed src=\"" . $vPIPLocation . "/GenvPIP0-07.swf\"\n";
				echo "	       quality=\"high\" bgcolor=\"#ddccee\" width=\"640\" height=\"300\"\n";
				echo "	       name=\"GenvPIP0-07\" align=\"middle\" allowScriptAccess=\"sameDomain\" \n";
				echo "	       type=\"application/x-shockwave-flash\" \n";
				echo "	       pluginspage=\"http://www.macromedia.com/go/getflashplayer\" ";

		       if (!(get_option("vPIP_BracketedCode") === false) && get_option("vPIP_BracketedCode") == 'on')
					echo "FlashVars=\"bracketed=true\"\n";
				else
					echo "FlashVars=\"bracketed=false\"\n";

				echo "       />
				</object>\n
				<br />
				Open in external window: <a href=\"#\" onclick=\"window.open('" . $vPIPLocation . "/GenvPIP0-07.php','genvPIP0-07','scrollbars=yes,resizable=yes,width=660,height=320,left=10,top=10'); return false\"  ><img src=\"" . $vPIPLocation . "/vPIPbutton.png\" /></a>
	            </div>
	            <br />
";

			}
		}
	}


    // This action makes it a special HTML <link>'s are added to each single post page that specify the poster image and
    // the thumbnail image URLs.  This is useful for when other software wants to link to us (in an automated way).
    function _vPIP__single_post_links()
    {
        //
        // Only do this on a single post page.
        //
            if (  ! is_single()  ) {
                return;
            }

        //
        // Make sure the "Video API" is available to us.
        //
            global $post;
            if (  !isset($post) || FALSE === $post || !is_object($post)  ) {
                return;
            }
            if (  !isset($post->video) || FALSE === $post->video || !is_object($post->video)  ) {
                return;
            }


        //
        // Check to see if we have a "poster" or a "thumbnail"
        //
            $has_poster    = (  isset($post->video->poster->href)    && FALSE !== $post->video->poster->href    && is_string($post->video->poster->href)     );
            $has_thumbnail = (  isset($post->video->thumbnail->href) && FALSE !== $post->video->thumbnail->href && is_string($post->video->thumbnail->href)  );


        //
        // Render and output.
        //
	    if ($has_poster) {
		    echo "
        <link rel=\"poster videoposter video-poster\" href=\"" . htmlspecialchars($post->video->poster->href) . "\" />
";
            }
	    if ($has_thumbnail) {
		    echo "
        <link rel=\"thumbnail videothumbnail video-thumbnail\" href=\"" . htmlspecialchars($post->video->thumbnail->href) . "\" />
";
            }
    }
//-------------------------------------------------------------------------------------------------------------------------//

	if (function_exists('add_action'))
	{
		add_action('wp_head', 'add_vpip_js');
		add_action('admin_menu', '_vPIP_AddOptionsPage');
		add_action("simple_edit_form","_vPIP_Write");
		add_action("edit_form_advanced","_vPIP_Write");
		add_action("edit_page_form","_vPIP_Write");
		//add_action("publish_post","_vPIP_Publish");
		add_action("edit_post","_vPIP_Post");
		//add_action("save_post","_vPIP_Post");
		add_action("delete_post","_vPIP_PostDelete");
		add_action('activate_vPIP/vPIP.php', '_vPIP_CreateTables');
    	add_action('wp_head', '_vPIP__single_post_links');
	}

	if (function_exists('add_filter'))
	{
		add_filter('the_content', '_vPIP_Content_Process');
        add_filter('the_posts', '_vPIP__the_posts');
	}

	function _vPIP_DBCreateTables()
	{
		//Table:  MediaDefault
		//        ------------
		//  Contains the global default for vPIP Media Entry data.
		//     Available in Options menu
		$table_name = TBL_MEDIADEFAULT;
		$byNewTable = false;
		$byUpdateTable = false;

		//Check if table already exists
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
		{
			$byNewTable = true;
		}

		// mediaName = Flash/QuickTime/WindowsMedia
		// mediaCall = <a href=~url~ type=~mimetype~ onclick=vPIPPlay(...) ..>
		$sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				useExtFlashPlayer tinyint,
				extFlashPlayer text,
				movieParam text,
				movieParamLoc tinytext,
				extFlashPlayerFlashVars text,
				offerEmbedCode tinyint,
				embedCodeBtnTitle tinytext,
				embedCodeDefault tinytext,
				embedCodeInThickBox tinyint,
				embedCodeStyled tinyint,
				extendedFlds longtext,
				objects longtext,
				UNIQUE KEY id (id))
				COMMENT = 'ver. 1.21';";
		//Pre WP 2.30 location
		if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php'))
		{
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		}
		else if (file_exists(ABSPATH . 'wp-admin/upgrade-functions.php'))
		{
			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		}
		dbDelta($sql);

		if ($byNewTable)
		{
			$insert = "INSERT INTO " . $table_name . " (offerEmbedCode) " . "VALUES " .
			          "(1)";

			$results = $GLOBALS['wpdb']->query( $insert );
		}
		add_option("vpip_db_version", "1.21");

		//Table:  VideoFormats
		//        ------------
		//Contains video formats (flash, quicktime, windows media) and
		// 	associated display code
		$table_name = TBL_VIDEOFORMATS;
		$byNewTable = false;
		$byUpdateTable = false;

		//Check if table already exists
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
		{
			$byNewTable = true;
		}

		// mediaName = Flash/QuickTime/WindowsMedia
		// mediaCall = <a href=~url~ type=~mimetype~ onclick=vPIPPlay(...) ..>
		$sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				mediaName tinytext NOT NULL,
				mediaCall text NOT NULL,
				descript text,
				width smallint,
				height smallint,
				mimeType text,
				isDefault tinyint,
				displayOrder tinyint,
				isActive tinyint,
				isVisible tinyint,
				extendedFlds longtext,
				objects longtext,
				UNIQUE KEY id (id))
				COMMENT = 'ver. 1.20';";
		dbDelta($sql);

		if ($byNewTable)
		{
			$insert = "INSERT INTO " . $table_name . " (mediaName, mediaCall, descript, width, height, mimeType, isDefault, displayOrder, isActive, isVisible) " . "VALUES " .
			          "('Flash','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~,flv=true', 'FLVbuffer=10', ''); return false;\" >") . "', '', 640, 480, 'video/x-flv', 1, 1, 1, 1), " .
			          "('QuickTime','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 2, 1, 1), " .
			          "('iPod','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 3, 1, 1), " .
			          "('Apple TV','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 4, 1, 1), " .
			          "('Mobile','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 5, 1, 1), " .
			          "('Windows Media','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/x-ms-wmv', 0, 6, 1, 1), " .
			          "('HD','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 1280, 720, '', 0, 7, 1, 1), " .
			          "('Ogg','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'application/ogg', 0, 8, 1, 1) ";

			$results = $GLOBALS['wpdb']->query( $insert );
		}

		//Table:  VideoFmtsDefault
		//        ------------
		$table_name = TBL_VIDEOFMTSDEFAULT;
		//Check if table already exists
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
			$byNewTable = true;
		else
			$byNewTable = false;

		$sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				width smallint,
				height smallint,
				align tinytext,
				extendedFlds longtext,
				UNIQUE KEY id (id))
				COMMENT = 'ver. 1.10';";
		dbDelta($sql);
		if ($byNewTable)
		{
			$insert = "INSERT INTO " . $table_name . " (width, height, align) " . "VALUES " .
			          "(640, 480, 'TC') ";

			$results = $GLOBALS['wpdb']->query( $insert );
		}

		//Table:  MediaFor
		//        ------------
		$table_name = TBL_MEDIAFOR;
		//Check if table already exists
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
			$byNewTable = true;
		else
			$byNewTable = false;

		$sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				mediaFor text,
				extendedFlds longtext,
				UNIQUE KEY id (id))
				COMMENT = 'ver. 1.20';";
		dbDelta($sql);
		if ($byNewTable)
		{
			$insert = "INSERT INTO " . $table_name . " (mediaFor) " . "VALUES " .
			          "('iPod'), ('web'), ('tv'), ('hdtv'), ('Flash Player'), ('QuickTime Player'), ".
			          "('Apple TV'), ('phone'), ('Windows Media Player'), ('OGG Player')";

			$results = $GLOBALS['wpdb']->query( $insert );
		}

		$table_name = TBL_MEDIAFORCONNECT;
		//Check if table already exists
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
			$byNewTable = true;
		else
			$byNewTable = false;

		$sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				mediaForID mediumint(9) NOT NULL,
				formatsID mediumint(9),
				formatID mediumint(9),
				extendedFlds longtext,
				UNIQUE KEY id (id))
				COMMENT = 'ver. 1.20';";
		dbDelta($sql);
		if ($byNewTable)
		{
			_vPIP_DBMediaForConnectSetup("1.20");
		}

		//Table:  VideoFormat
		//        ------------
		$table_name = TBL_VIDEOFORMAT;
		$byNewTable = false;
		//Check if table already exists
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
			$byNewTable = true;

		$sql = "CREATE TABLE " . $table_name . " (
				 id mediumint(9) NOT NULL AUTO_INCREMENT,
				 videoFormats_ID mediumint(9) NOT NULL,
				 post_ID mediumint(9) NOT NULL,
				 mediaType tinytext,
				 url text,
				 width smallint,
				 height smallint,
				 isDefault tinyint,
				 isActive tinyint,
				 displayOrder tinyint,
				 isVisible tinyint,
				 useExtFlashPlayer tinyint,
				 extFlashPlayer text,
				 movieParam text,
				 movieParamLoc tinytext,
				 extFlashPlayerFlashVars text,
				 offerEmbedCode tinyint,
				 embedCodeBtnTitle tinytext,
				 embedCodeDefault tinytext,
				 embedCodeInThickBox tinyint,
				 embedCodeStyled tinyint,
				 embedCodeThickBoxTitle text,
				 extendedFlds longtext,
				 objects longtext,
				 UNIQUE KEY id (id))
				 COMMENT = 'ver. 1.21';";
		dbDelta($sql);

	}

	//Update vPIP tables to version
	function _vPIP_DBUpdateTo($sFrom, $sTo)
	{
		$success = true;
		if ($sTo == "1.10")
		{
			$table_name = TBL_VIDEOFORMATS;
			if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " extendedFlds"))
			{
				$sql = "ALTER TABLE " . $table_name . " ADD COLUMN extendedFlds longtext, ADD COLUMN objects longtext, COMMENT = 'ver. 1.10'";
				$result = $GLOBALS['wpdb']->query( $sql );
				$success = $result !== false;
			}

			$table_name = TBL_VIDEOFMTSDEFAULT;
			if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " extendedFlds"))
			{
				$sql = "ALTER TABLE " . $table_name . " ADD COLUMN extendedFlds longtext, COMMENT = 'ver. 1.10' ";
				$result = $GLOBALS['wpdb']->query( $sql );
				$success = $result !== false;
			}

			$table_name = TBL_VIDEOFORMAT;
			if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " extendedFlds"))
			{
				$sql = "ALTER TABLE " . $table_name . " ADD COLUMN extendedFlds longtext, ADD COLUMN objects longtext, COMMENT = 'ver. 1.10'";
				$result = $GLOBALS['wpdb']->query( $sql );
				$success = $result !== false;

			}

			if ($success)
				update_option("vpip_db_version", "1.10");
		}
		else if ($sTo == "1.20")
		{
			if ($sFrom == null || ((float)$sFrom) < 1.1 )
			{
				$result = _vPIP_DBUpdateTo($sFrom, "1.10");
			}

			//For dbDelta function
			if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php'))
			{
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			}
			else if (file_exists(ABSPATH . 'wp-admin/upgrade-functions.php'))
			{
				require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			}

			//Table:  MediaDefault
			//        ------------
			//  Contains the global default for vPIP Media Entry data.
			//     Available in Options menu
			$table_name = TBL_MEDIADEFAULT;
			$byNewTable = false;
			$byUpdateTable = false;

			//Check if table already exists
			if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
			{
				$byNewTable = true;
			}

			// mediaName = Flash/QuickTime/WindowsMedia
			// mediaCall = <a href=~url~ type=~mimetype~ onclick=vPIPPlay(...) ..>
			$sql = "CREATE TABLE " . $table_name . " (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					useExtFlashPlayer tinyint,
					extFlashPlayer text,
					movieParam text,
					movieParamLoc tinytext,
					extFlashPlayerFlashVars text,
					offerEmbedCode tinyint,
					embedCodeDefault tinytext,
					embedCodeInThickBox tinyint,
					extendedFlds longtext,
					objects longtext,
					UNIQUE KEY id (id))
					COMMENT = 'ver. 1.20';";
			if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php'))
			{
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			}
			else if (file_exists(ABSPATH . 'wp-admin/upgrade-functions.php'))
			{
				require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			}
			dbDelta($sql);

			if ($byNewTable)
			{
				$insert = "INSERT INTO " . $table_name . " (offerEmbedCode) " . "VALUES " .
				          "(1)";

				$results = $GLOBALS['wpdb']->query( $insert );
			}

			$table_name = TBL_VIDEOFORMATS;
			if ($GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " useExtFlashPlayer"))
			{

				//Transfer any external Flash Player settings before removing those fields
				$sql = "SELECT useExtFlashPlayer, extFlashPlayer, movieParam, movieParamLoc, extFlashPlayerFlashVars FROM " . $table_name . " WHERE useExtFlashPlayer = 1";
				//$results = $GLOBALS['wpdb']->query( $sql );

				$data = $GLOBALS['wpdb']->get_results($sql);
				if ($data !== false && $data != null && count($data) > 0)
				{
					$sql = "UPDATE " . TBL_MEDIADEFAULT . " SET useExtFlashPlayer=" . $data[0]->useExtFlashPlayer .
						   ", extFlashPlayer='" . $data[0]->extFlashPlayer . "', movieParam='" .
						   $data[0]->movieParam . "', movieParamLoc='" . $data[0]->movieParamLoc .
						   "', extFlashPlayerFlashVars='" . $data[0]->extFlashPlayerFlashVars . "'";
					$results = $GLOBALS['wpdb']->query( $sql );
				}

				$sql = "ALTER TABLE " . $table_name . " DROP COLUMN useExtFlashPlayer, " .
					   "DROP COLUMN extFlashPlayer, DROP COLUMN movieParam, DROP COLUMN movieParamLoc, " .
					   "DROP COLUMN extFlashPlayerFlashVars, COMMENT = 'ver. 1.20'";
				$result = $GLOBALS['wpdb']->query( $sql );
				$success = $result !== false;
			}

			$sql = "SELECT * FROM " . $table_name . " WHERE mediaName == 'HD' OR mediaName == 'Ogg'";
			$results = $GLOBALS['wpdb']->query( $sql );

			if ($results === false)
			{
				$insert = "INSERT INTO " . $table_name . " (mediaName, mediaCall, descript, width, height, mimeType, isDefault, displayOrder, isActive, isVisible) " . "VALUES " .
				          "('HD','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 1280, 720, '', 0, 7, 1, 1), " .
				          "('Ogg','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'application/ogg', 0, 8, 1, 1) ";

				$results = $GLOBALS['wpdb']->query( $insert );
			}

			// Drop TBL_VIDEOFMTSDEFAULT
			$table_name = TBL_VIDEOFMTSDEFAULT;
			if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name))
			{
				$sql = "DROP TABLE " . $table_name;
				$result = $GLOBALS['wpdb']->query( $sql );
				$success = $result !== false;
			}

			//Table:  MediaFor
			//        ------------
			$table_name = TBL_MEDIAFOR;
			//Check if table already exists
			if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
				$byNewTable = true;
			else
				$byNewTable = false;

			$sql = "CREATE TABLE " . $table_name . " (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					mediaFor text,
					extendedFlds longtext,
					UNIQUE KEY id (id))
					COMMENT = 'ver. 1.20';";
			dbDelta($sql);
			if ($byNewTable)
			{
				$insert = "INSERT INTO " . $table_name . " (mediaFor) " . "VALUES " .
				          "('iPod'), ('web'), ('tv'), ('hdtv'), ('Flash Player'), ('QuickTime Player'), ".
				          "('Apple TV'), ('phone'), ('Windows Media Player'), ('OGG Player')";

				$results = $GLOBALS['wpdb']->query( $insert );
				$success = $result !== false;
			}

			$table_name = TBL_MEDIAFORCONNECT;
			//Check if table already exists
			if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
				$byNewTable = true;
			else
				$byNewTable = false;

			$sql = "CREATE TABLE " . $table_name . " (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					mediaForID mediumint(9) NOT NULL,
					formatsID mediumint(9),
					formatID mediumint(9),
					extendedFlds longtext,
					UNIQUE KEY id (id))
					COMMENT = 'ver. 1.20';";
			dbDelta($sql);
			if ($byNewTable)
			{
				$success = _vPIP_DBMediaForConnectSetup("1.20");
			}
			else
				$success = true;

			$table_name = TBL_VIDEOFORMAT;
			if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " offerEmbedCode"))
			{
				$sql = "ALTER TABLE " . $table_name . " ADD COLUMN offerEmbedCode tinyint, ADD COLUMN embedCodeDefault tinytext, ADD COLUMN embedCodeInThickBox tinyint, ADD COLUMN embedCodeThickBoxTitle text, COMMENT = 'ver. 1.20'";
				$result = $GLOBALS['wpdb']->query( $sql );
				$success = $result !== false;

				$sql = "UPDATE " . $table_name . " SET offerEmbedCode=1 WHERE useExtFlashPlayer IS NOT NULL";
				$results = $GLOBALS['wpdb']->query( $sql );
			}

			if ($success)
				update_option("vpip_db_version", "1.20");
		}
		else if ($sTo == "1.21")
		{
			if ($sFrom == null || ((float)$sFrom) < 1.2 )
			{
				$result = _vPIP_DBUpdateTo($sFrom, "1.20");
			}

			//
			$table_name = TBL_MEDIADEFAULT;
			if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " embedCodeBtnTitle"))
			{

				$sql = "ALTER TABLE " . $table_name . " ADD COLUMN embedCodeBtnTitle tinytext, " .
					   "ADD COLUMN embedCodeStyled tinyint, COMMENT = 'ver. 1.21'";
				$result = $GLOBALS['wpdb']->query( $sql );
				$success = $result !== false;
				if ($result === false)
					echo mysql_error() . "<br />";

				$sql = "UPDATE " . $table_name . " SET embedCodeBtnTitle='embed', embedCodeStyled=true";
				$results = $GLOBALS['wpdb']->query( $sql );
				if ($results === false)
					echo mysql_error() . "<br />";
			}

			$table_name = TBL_VIDEOFORMAT;
			if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " embedCodeBtnTitle"))
			{

				$sql = "ALTER TABLE " . $table_name . " ADD COLUMN embedCodeBtnTitle tinytext, " .
					   "ADD COLUMN embedCodeStyled tinyint, COMMENT = 'ver. 1.21'";
				$result = $GLOBALS['wpdb']->query( $sql );
				$success = $result !== false;
				if ($result === false)
					echo mysql_error() . "<br />";

				$sql = "UPDATE " . $table_name . " SET embedCodeBtnTitle='embed', embedCodeStyled=true";
				$results = $GLOBALS['wpdb']->query( $sql );
				if ($results === false)
					echo mysql_error() . "<br />";
			}

			if ($success)
			{
				update_option("vpip_db_version", "1.21");
			}
		}

		return $success;
	}

	function _vPIP_DBMediaForConnectSetup($sVersion)
	{
		$bySuccess = false;

		if ($sVersion == "1.20")
		{
			//Flash
			$bySuccess = _vPIP_DBMediaForConnect('Flash Player', 'Flash', false);

			//QuickTime
			$bySuccess = _vPIP_DBMediaForConnect('Quicktime Player', 'QuickTime', false);

			//iPod
			$bySuccess = _vPIP_DBMediaForConnect('iPod', 'iPod', false);

			//Apple TV
			$bySuccess = _vPIP_DBMediaForConnect('Apple TV', 'Apple TV', false);

			//Mobile
			$bySuccess = _vPIP_DBMediaForConnect('phone', 'Mobile', false);

			//Windows Media
			$bySuccess = _vPIP_DBMediaForConnect('Windows Media Player', 'Windows Media', false);

			//HD
			$bySuccess = _vPIP_DBMediaForConnect('hdtv', 'HD', false);

			//OGG
			$bySuccess = _vPIP_DBMediaForConnect('OGG Player', 'Ogg', false);

		}

		return $bySuccess;
	}

	//Connects a videoFor label either to VideoFormats table if $mediaName given
	// or VideoFormat table if $post_ID given
	function _vPIP_DBMediaForConnect($mediaFor, $mediaName, $post_ID)
	{
		//required entries
		if ($mediaFor == null || $mediaFor === false || $mediaName == null ||
			$mediaName === false)
		{
			return;
		}

		$bySuccess = false;

		//  MediaFor
		$sql = "SELECT id FROM " . TBL_MEDIAFOR . " WHERE mediaFor = '" . $mediaFor . "'";
		$results = $GLOBALS['wpdb']->query( $sql );

		if ($results)
		{
			$aID = $GLOBALS['wpdb']->get_results($sql);
			$nMediaForID = $aID[0]->id;

			// VideoFormats
			$sql = "SELECT id FROM " . TBL_VIDEOFORMATS . " WHERE mediaName = '". $mediaName . "'";
			$results = $GLOBALS['wpdb']->query( $sql );

			if ($results)
			{
				$aID = $GLOBALS['wpdb']->get_results($sql);
				$nFormatsID = $aID[0]->id;

				//Insert VideoFormat table connection
				if ($post_ID !== false)
				{
					// VideoFormat
					$sql = "SELECT id FROM " . TBL_VIDEOFORMAT . " WHERE post_ID = ". $post_ID . " AND videoFormats_ID = " . $nFormatsID;
					$results = $GLOBALS['wpdb']->query( $sql );

					if ($results)
					{
						$aID = $GLOBALS['wpdb']->get_results($sql);
						$nFormatID = $aID[0]->id;

						//See if MediaName to post_ID entry is already there.
						$sql = "SELECT id FROM " . TBL_MEDIAFORCONNECT . " WHERE FormatsID = ". $nFormatsID . " AND FormatID = " . $nFormatID;
						$results = $GLOBALS['wpdb']->query( $sql );

						//If it's already there, update it.
						if ($results)
						{
							$update = "UPDATE " . TBL_MEDIAFORCONNECT . " SET mediaForID=" . $nMediaForID .
									  " WHERE FormatsID=" . $nFormatsID . " AND FormatID=" . $nFormatID;

							$results = $GLOBALS['wpdb']->query( $update );
							if ($results)
								$bySuccess = true;
						}
						//If not there, insert it.
						else
						{
							$insert = "INSERT INTO " . TBL_MEDIAFORCONNECT . " (mediaForID, FormatsID, FormatID) " . "VALUES " .
							          "(" . $nMediaForID . ", " . $nFormatsID . ", " . $nFormatID. ")";

							$results = $GLOBALS['wpdb']->query( $insert );
							if ($results)
								$bySuccess = true;
						}
					}

				}
				//Insert VideoFormats table connection
				else
				{
					//See if MediaName is already there.
					$sql = "SELECT id FROM " . TBL_MEDIAFORCONNECT . " WHERE FormatsID = ". $nFormatsID . " AND FormatID IS NULL";
					$results = $GLOBALS['wpdb']->query( $sql );

					//If it's already there, update it.
					if ($results)
					{
						$update = "UPDATE " . TBL_MEDIAFORCONNECT . " SET mediaForID=" . $nMediaForID .
								  " WHERE FormatsID=" . $nFormatsID . " AND FormatID IS NULL";

						$results = $GLOBALS['wpdb']->query( $update );
						if ($results)
							$bySuccess = true;
					}
					//If not there, insert it.
					else
					{
						$insert = "INSERT INTO " . TBL_MEDIAFORCONNECT . " (mediaForID, FormatsID) " . "VALUES " .
						          "(" . $nMediaForID . ", " . $nFormatsID . ")";

						$results = $GLOBALS['wpdb']->query( $insert );
						if ($results)
							$bySuccess = true;
					}
				}

			}


		}

		return $bySuccess;
	}

	function _vPIP_Publish($post_ID)
	{
		_vPIP_Post($post_ID);
	}

	//Post is being saved - save vPIP data
	function _vPIP_Post($post_ID)
	{
		$table_name = TBL_VIDEOFORMATS;
		$byUpdatingTables = false;
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
		{
			_vPIP_DBCreateTables();
			$byUpdatingTables = true;
		}
		else if(get_option("vpip_db_version") != "1.21") // Update to 1.21
		{
			_vPIP_DBUpdateTo(get_option("vpip_db_version"), "1.21");
			$byUpdatingTables = true;
		}
		$sInterface = get_option("vPIP_Interface");
		if ($sInterface === false || $sInterface == 'vPIPMediaEntry' || $sInterface == 'vlogsplosion')
		{
			//If no Default Media Entry, then save is outside the post edit/write area
			if (isset($_POST["vPIP_isDefault"]))
			{
				if (!$byUpdatingTables )
				{
					//If already saved in the last 3 seconds, don't save
					$now = time();
					if ($now - $GLOBALS['last_save'] < 4)
						return;

					//Clear radio & checkbox fields:
					$aUpdate["post_ID = " . $post_ID] = "isDefault = 0";
					_vPIP_UpdateTable(TBL_VIDEOFORMAT, $aUpdate);
					$aUpdate["post_ID = " . $post_ID] = "isActive = 0";
					_vPIP_UpdateTable(TBL_VIDEOFORMAT, $aUpdate);
					$aUpdate["post_ID = " . $post_ID] = "isVisible = 0";
					_vPIP_UpdateTable(TBL_VIDEOFORMAT, $aUpdate);

					foreach ($_POST as $key => $value) {
					  if (substr($key, 0, 5) == "vPIP_") {
						
					  	if ($key == "vPIP_PosterImage_URL" && strlen(trim($value)) > 0 ) {
					  		$sInsert = "(videoFormats_ID, post_ID, url) VALUES (-1, " . $post_ID . ", '" . $value . "')";
					  		$sWhere = "videoFormats_ID = -1 AND post_ID = " . $post_ID;
					  		//TODO:  Prepend "http://" if missing
					  		$sUpdate = "url = '" . $value . "'";
					  		_vPIP_TableInsert(TBL_VIDEOFORMAT, $sInsert, $sWhere, $sUpdate);
					  	}
					  	else if ($key == "vPIP_ThumbnailImage_URL" && strlen(trim($value)) > 0 ) {
					  		$sInsert = "(videoFormats_ID, post_ID, url) VALUES (-2, " . $post_ID . ", '" . $value . "')";
					  		$sWhere = "videoFormats_ID = -2 AND post_ID = " . $post_ID;
					  		//TODO:  Prepend "http://" if missing
					  		$sUpdate = "url = '" . $value . "'";
					  		_vPIP_TableInsert(TBL_VIDEOFORMAT, $sInsert, $sWhere, $sUpdate);
					  	}
					  	else {
					  		if ($key == "vPIP_isDefault") {
					  			$id = _vPIP_GetFormatID($value);
					  			$value = 1;
					  		}
					  		else if (strpos($key, "_isActive") > 0 || strpos($key, "_isVisible") > 0)
					  		{
					  			$id = _vPIP_GetFormatID(strtr(substr($key,5, strrpos($key, "_")-5), "_", " "));
					  			$value = "1";
					  		}
					  		else
					  			$id = _vPIP_GetFormatID(strtr(substr($key,5, strrpos($key, "_")-5), "_", " "));

					  		$field = substr($key,strrpos($key, "_")+1);

					  		if ($field == "mediaFor")
					  		{
					  			$iStart = strpos($key, "_")+1;
					  			$iLen = strrpos($key, "_")-$iStart;
					  			$mediaName = substr($key,$iStart,$iLen);
					  			$mediaName = str_replace("_", " ", $mediaName);
					  			_vPIP_DBMediaForConnect($value, $mediaName, $post_ID);
					  			continue;
					  		}

					  		if ($id > -1) {
								$colType = _vPIP_GetColType(TBL_VIDEOFORMAT, $field);
						  		if (strcasecmp($colType,"text") == 0)
						  		{
							  		$sInsert = "(videoFormats_ID, post_ID, ". $field . ") VALUES (" . $id . ", " . $post_ID . ", '" . $value . "')";
							  		if ($value == NULL)
								  		$sUpdate = $field . " = NULL";
							  		else
								  		$sUpdate = $field . " = '" . $value . "'";
						  		}
						  		else
						  		{
							  		$sInsert = "(videoFormats_ID, post_ID, ". $field . ") VALUES (" . $id . ", " . $post_ID . ", " . $value . ")";
							  		if ($value == NULL)
								  		$sUpdate = $field . " = NULL";
							  		else
							  			$sUpdate = $field . " = " . $value;
						  		}
						  		$sWhere = "videoFormats_ID = " . $id . " AND post_ID = " . $post_ID;
						  		_vPIP_TableInsert(TBL_VIDEOFORMAT, $sInsert, $sWhere, $sUpdate);
					  		}
					  	}
					  }
					}

					//Update Flash External Player settings
					if (isset($_POST["vPIP_useExtFlashPlayer"]))
						$useExtFlashPlayer = 1;
					else
						$useExtFlashPlayer = 0;

					$FLVPlayerURL = $_POST["vPIP_FLVPlayerURL"];
					$FLVParam = $_POST["vPIP_FLVParam"];
					$FLVParamLoc = $_POST["vPIP__FLVParamLoc"];
					$FLVFlashVar = $_POST["vPIP_FLVFlashVar"];
					if (isset($_POST["vPIP_offerEmbedCode"]))
						$offerEmbedCode = 1;
					else
						$offerEmbedCode = 0;
					$embedCodeBtnTitle = $_POST["vPIP_EmbedBtnTitle"];
					if (isset($_POST["vPIP_embedCodeStyled"]))
						$embedCodeStyled = 1;
					else
						$embedCodeStyled = 0;

		  			$id = _vPIP_GetFormatID("Flash");
			  		$sInsert = "(useExtFlashPlayer, extFlashPlayer, movieParam, movieParamLoc, extFlashPlayerFlashVars, " .
			  					"offerEmbedCode, embedCodeBtnTitle, embedCodeStyled) VALUES (" .
			  				     $useExtFlashPlayer . ", '" . $FLVPlayerURL . "', '" . $FLVFlashVar . "', '" .
			  				     $FLVParamLoc . "', '" . $FLVParam . "', " . $offerEmbedCode . ", '" .
			  				     $embedCodeBtnTitle . "', " . $embedCodeStyled . ")";
					$sUpdate = "useExtFlashPlayer = " . $useExtFlashPlayer .
						", extFlashPlayer = '" . $FLVPlayerURL .
						"', movieParam = '" . $FLVParam .
						"', movieParamLoc = '" . $FLVParamLoc .
						"', extFlashPlayerFlashVars = '" . $FLVFlashVar . "', " .
						"offerEmbedCode = " . $offerEmbedCode . ", embedCodeBtnTitle = '" .
						$embedCodeBtnTitle . "', embedCodeStyled = " . $embedCodeStyled;
			  		$sWhere = "videoFormats_ID = " . $id . " AND post_ID = " . $post_ID;
			  		_vPIP_TableInsert(TBL_VIDEOFORMAT, $sInsert, $sWhere, $sUpdate);

					$GLOBALS['last_save'] = time();
				}
				else
				{
					echo "<p><div style=\"text-align: center; color: red; \" >vPIP tables updated, please refresh page...</div></p>
		";
				}

			}
		}
	}

	function _vPIP_PostDelete($post_ID) {
		$delete = "DELETE FROM " . TBL_VIDEOFORMAT . " WHERE post_ID = " . $post_ID;
		$result = $GLOBALS['wpdb']->query( $delete );
	}

	// **** deprecated Database functions call ****

    function _vPIP_GetVlogsplosionData($post_ID = NULL, $iLast = NULL, $post_status = "publish")
    {
    	return _vPIP_GetMediaEntryData($post_ID, $iLast, $post_status);
    }

    //Return object array of Media Entry data
    function _vPIP_GetMediaEntryData($post_ID = NULL, $iLast = NULL, $post_status = "publish")
    {

		//Make sure vpip tables are available
		$table_name = TBL_VIDEOFORMATS;
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) == 0)
		{

        //
        // Construct the beginning part of the SQL statement.
        //
            $sql = '
                            SELECT vf.*
                            FROM '.TBL_VIDEOFORMAT.' vf
            ';
            if (  !function_exists('is_admin') || !is_admin()  ) {
                $sql .= '
                            JOIN '.$GLOBALS['wpdb']->posts.' wp_posts ON (vf.post_ID=wp_posts.ID)
                ';
            }

        //
        // Construct the WHERE-clause of the SQL statement.
        //
            $separator = 'WHERE';

            if (  (!function_exists('is_admin') || !is_admin()) && $post_status != null ) {
                $sql .= " {$separator} wp_posts.post_status = '{$post_status}'";
                $separator = '  AND';
            }

            if (  !is_null($post_ID)  ) {
                if (is_null($iLast)) {
                    $sql .= '
                            '.$separator.' vf.post_ID = "' . $GLOBALS['wpdb']->escape($post_ID) .'"
                    ';
                    $separator = '  AND';
                } else {
                    $sql .= '
                            '.$separator.' vf.post_ID < "' . $GLOBALS['wpdb']->escape($post_ID) . '"
                    ';
                    $separator = '  AND';
                }
            }

        //
        // Construct the last part of the SQL statement.
        //
// #### TODO: Is ordering by post_ID really correct?... shouldn't it be ordered by the post_modified or post_date?
            $sql .= '
                            ORDER BY vf.post_ID DESC
            ';
            if (is_null($post_ID)) {
                if (  isset($iLast) && FALSE !== $iLast && is_numeric($iLast) && 0 != $iLast  ) {
                    $sql .= '
                            LIMIT '. $iLast .'
                    ';
                }
            }


        //
        // Get data from database.
        //
            $results = $GLOBALS['wpdb']->query( $sql );

        //
        // Return.
        //
            return $GLOBALS['wpdb']->get_results($sql);
		}
		else
			return false;

    }


	//Return an array of records for table with serialized data
	function _vPIP_ExplodeExtendedFlds($aRecs)
	{

		$aReturn = array();
		for ($i=0; $i<count($aRecs); $i++)
		{
			$aRec = $aRecs[$i];
			$aExtendedFlds = unserialize($aRec->extendedFlds);
			if ($aExtendedFlds)
			{
				foreach ($aExtendedFlds as $index => $value)
				{
			        //value passed by reference to the index
			        $aRec->{$index} =& $aExtendedFlds->{$index};
				}
			}
			$aReturn[$i] = $aRec;
		}

		return $aReturn;
	}

	/* TBR:
	//Turn unserialized field data into an record object associative array.
	function _vPIP_TblDataToRec($aData)
	{
		$aRecord = NULL;
		foreach ($aData as $aDatum)
		{
			foreach ($aDatum as $field => $value)
			{
				$aRecord->$field = $value;
			}
		}

		return $aRecord;
	}
	*/

	function _vPIP_StripDataCol($aRecs)
	{
		$aReturn = array();
		for ($i=0; $i<count($aRecs); $i++)
		{
			$aRec = $aRecs[$i];
			unset($aRec->data);
			$aReturn[$i] = $aRec;
		}

		return $aReturn;
	}

	// Return associative array of VideoFormat data
	function _vPIP_GetVideoFormat($videoFormats_ID, $post_ID)
	{
		$sql = "SELECT * FROM " . TBL_VIDEOFORMAT . " WHERE videoFormats_ID = " .
				$videoFormats_ID . " AND post_ID = " . $post_ID;
		$results = $GLOBALS['wpdb']->get_results( $sql );
		return _vPIP_ExplodeExtendedFlds($results); //$GLOBALS['wpdb']->get_results($sql));
	}

	//Return the ID of the format name
	function _vPIP_GetFormatID($sFormat)
	{
		$sql = "SELECT id FROM " . TBL_VIDEOFORMATS . " WHERE mediaName = '" . $sFormat . "'";

		$results = $GLOBALS['wpdb']->query( $sql );

		if ($results)
		{
			$aID = $GLOBALS['wpdb']->get_results($sql);
			return $aID[0]->id;
		}
		else
		{
			return -2;
		}
	}

	function _vPIP_GetMediaFormats()
	{
		$aMediaFormats = array();
		$aVideoFormats = _vPIP_GetTableRows(TBL_VIDEOFORMATS);
		foreach ($aVideoFormats as $aVideoFormat)
		{
			$aMediaFormats[$aVideoFormat->mediaName] = $aVideoFormat->descript;
		}

		return $aMediaFormats;
	}

	function _vPIP_getMediaFor($mediaName, $post_ID)
	{
		$nMediaForID = false;

		//Get required mediaName
		$sql = "SELECT id FROM " . TBL_VIDEOFORMATS . " WHERE mediaName = '" . $mediaName . "'";

		$results = $GLOBALS['wpdb']->query( $sql );

		if ($results)
		{
			$aID = $GLOBALS['wpdb']->get_results($sql);
			$nFormatsID = $aID[0]->id;

			//If search in VideoFormat detail
			if($post_ID !== false && $post_ID != null)
			{
				$sql = "SELECT id FROM " . TBL_VIDEOFORMAT . " WHERE videoFormats_ID=" . $nFormatsID . 	" AND post_ID = " . $post_ID;

				$results = $GLOBALS['wpdb']->query( $sql );

				if ($results)
				{
					$aID = $GLOBALS['wpdb']->get_results($sql);
					$nFormatID = $aID[0]->id;

					$sql = "SELECT mediaForID FROM " . TBL_MEDIAFORCONNECT . " WHERE formatID=" . $nFormatID . " AND formatsID=" . $nFormatsID;

					$results = $GLOBALS['wpdb']->query( $sql );

					if ($results)
					{
						$aID = $GLOBALS['wpdb']->get_results($sql);
						$nMediaForID = $aID[0]->mediaForID;

					}
				}
			}
			//If search in VideoFormats default
			else
			{
				$sql = "SELECT id FROM " . TBL_VIDEOFORMATS . " WHERE mediaName = '" . $mediaName . "'";

				$results = $GLOBALS['wpdb']->query( $sql );

				if ($results)
				{
					$aID = $GLOBALS['wpdb']->get_results($sql);
					$nFormatsID = $aID[0]->id;

					$sql = "SELECT mediaForID FROM " . TBL_MEDIAFORCONNECT . " WHERE formatsID=" . $nFormatsID;

					$results = $GLOBALS['wpdb']->query( $sql );

					if ($results)
					{
						$aID = $GLOBALS['wpdb']->get_results($sql);
						$nMediaForID = $aID[0]->mediaForID;

					}
				}
			}
		}

		//If MediaFor entry found, return description
		if ($nMediaForID !== false)
		{
			$sql = "SELECT mediaFor FROM " . TBL_MEDIAFOR . " WHERE id=" . $nMediaForID;

			$results = $GLOBALS['wpdb']->query( $sql );

			if ($results)
			{
				$aID = $GLOBALS['wpdb']->get_results($sql);
				return $aID[0]->mediaFor;
			}
		}
		else
		{
			return null;
		}
	}

	function _vPIP_isMediaForSelected($mediaFor, $mediaName, $post_ID)
	{
		$sql = "SELECT id FROM " . TBL_MEDIAFOR . " WHERE mediaFor = '" . $mediaFor . "'";

		$results = $GLOBALS['wpdb']->query( $sql );

		if ($results)
		{
			$aID = $GLOBALS['wpdb']->get_results($sql);
			$nMediaForID = $aID[0]->id;

			if ($mediaName !== false)
			{
				$sql = "SELECT id FROM " . TBL_VIDEOFORMATS . " WHERE mediaName = '" . $mediaName . "'";

				$results = $GLOBALS['wpdb']->query( $sql );

				if ($results)
				{
					$aID = $GLOBALS['wpdb']->get_results($sql);
					$nFormatsID = $aID[0]->id;

					$sql = "SELECT id FROM " . TBL_MEDIAFORCONNECT . " WHERE mediaForID = " . $nMediaForID . " AND formatsID=" . $nFormatsID;

					$results = $GLOBALS['wpdb']->query( $sql );

					if ($results)
					{
						return true;
					}
				}

			}
			else if($post_ID !== false)
			{
				$sql = "SELECT id FROM " . TBL_VIDEOFORMAT . " WHERE post_ID = " . $post_ID;

				$results = $GLOBALS['wpdb']->query( $sql );

				if ($results)
				{
					$aID = $GLOBALS['wpdb']->get_results($sql);
					$nFormatID = $aID[0]->id;

					$sql = "SELECT id FROM " . TBL_MEDIAFORCONNECT . " WHERE mediaForID = " . $nMediaForID . " AND formatID=" . $nFormatID;

					$results = $GLOBALS['wpdb']->query( $sql );

					if ($results)
					{
						return true;
					}
				}

			}
		}

		return false;
	}
	// Get object with media format fields
	function _vPIP_GetMediaFormat($id)
	{
		$sql = "SELECT * FROM " . TBL_VIDEOFORMATS . " WHERE id = " . $id;

		$results = $GLOBALS['wpdb']->query( $sql );

		$oData = $GLOBALS['wpdb']->get_results($sql);

		if ($oData && count($oData) > 0)
			return $oData[0];
		else
			return NULL;
	}

	//Gets all rows for a table and returns as associative array
	function _vPIP_GetTableRows($sTable, $sWhere = NULL, $sOrderBy = NULL)
	{
		$sql = "SELECT * FROM " . $sTable;
		if (! is_null($sWhere))
			$sql .= " WHERE " . $sWhere;
		if (! is_null($sOrderBy))
			$sql .= " ORDER BY " . $sOrderBy;

		//$results = $GLOBALS['wpdb']->query( $sql );

		return $GLOBALS['wpdb']->get_results($sql);

	}

	function _vPIP_GetTableCol($sTable, $sCol)
	{
		$sql = "SELECT " . $sCol . " FROM " . $sTable;

		//$results = $GLOBALS['wpdb']->query( $sql );

		return $GLOBALS['wpdb']->get_results($sql);
	}

	function _vPIP_GetColType($sTable, $sCol)
	{
		$sql = "SHOW COLUMNS FROM " . $sTable;

		//$results = $GLOBALS['wpdb']->query( $sql );

		$aCols = $GLOBALS['wpdb']->get_results($sql);
		foreach ($aCols as $col)
		{
			if (strcasecmp($col->Field, $sCol) == 0)
			{
				return $col->Type;
			}
		}

		return "";
	}

	function _vPIP_UpdateTable($sTable, $aUpdate)
	{

		foreach ($aUpdate as $where => $update)
		{
			if (strlen($where) > 0)
				$update = "UPDATE " . $sTable . " SET "  . $update . " WHERE " . $where;
			else
				$update = "UPDATE " . $sTable . " SET "  . $update;
			$results = $GLOBALS['wpdb']->query( $update );
		}

	}

	function _vPIP_TableInsert($sTable, $sInsert, $sWhere, $sUpdate)
	{
		$result = FALSE;
		$sql = "SELECT * FROM " . $sTable . " WHERE " . $sWhere;
		// If entry already exists, update
		if ($GLOBALS['wpdb']->query( $sql ))
		{
			$update = "UPDATE " . $sTable . " SET "  . $sUpdate . " WHERE " . $sWhere;
			$result = $GLOBALS['wpdb']->query( $update );
		}
		//If entry doesn't exist, insert
		else
		{
			$insert = "INSERT INTO  " . $sTable . " " . $sInsert;
			$result = $GLOBALS['wpdb']->query( $insert );

		}

		return $result;

	}

	//Locates the movie data first in Media Entry table by $post_id.
	//   Returns "<movie url>,<mimetype>,<width>,<height>,<flashVars>;..."
	//      number = 1 - found, 0 - unknown, -1 - not found.
	//   	movie url = url to movie or flashVars if network flash call or blank.
	//      mimetype = mimetype of movie or blank.
	//    	width = pixel width of movie.
	//   	height = pixel height of movie.
	//   	flashVars = flashVars sent to external player.
	function _vPIP_GetMovieData($post_id)
	{
		$sReturn = "";
		$avPIPMediaEntryData =_vPIP_GetMediaEntryData($post_id);

		if ($avPIPMediaEntryData) {
			//foreach($avPIPMediaEntryData as $vPIPMediaEntryData)
			for ($i=0; $i<count($avPIPMediaEntryData); $i++)
			{
				$vPIPMediaEntryData = $avPIPMediaEntryData[$i];
				//Poster Image
				if ($vPIPMediaEntryData->videoFormats_ID == -1)
				{
					$sReturn .= trim($vPIPMediaEntryData->url);
					if (strlen(trim($vPIPMediaEntryData->mimeType)) > 0)
					{
						$sReturn .= "," . trim($vPIPMediaEntryData->mimeType);
					}
					else
					{
						$sReturn .= ",image/";
					}
					$sReturn .= "," . $vPIPMediaEntryData->width . "," .
								$vPIPMediaEntryData->height .  "," .
								$vPIPMediaEntryData->extFlashPlayerFlashVars . ";";
				}
				else
				{
					$sReturn .= trim($vPIPMediaEntryData->url) . "," . "," .
							    trim($vPIPMediaEntryData->mimeType) . $vPIPMediaEntryData->width . "," .
							    $vPIPMediaEntryData->height .  "," .
								$vPIPMediaEntryData->extFlashPlayerFlashVars . ";";

				}
			}
		}

		return $sReturn;

	}

	// Returns an array of data objects
	// $post_id = the id of the post.  If null, all records returned
	// $iLast = If not null, returns the last # of entries offset from the post id.
	//          If post id is null, then most current last.
	function _vPIP_GetData($post_id = NULL, $iLast = NULL)
	{
		$avPIPMediaEntryData = array();
		if (is_null($iLast))
		{
			// Get all records
			if (is_null($post_id))
			{
				$avPIPMediaEntryData = _vPIP_GetDataFilter(_vPIP_GetMediaEntryData(NULL));
			}
			else
			{
				$avPIPMediaEntryData = _vPIP_GetDataFilter(_vPIP_GetMediaEntryData($post_id));
			}
		}
		else
		{
			// Get last records from top
			if (is_null($post_id))
			{
				$avPIPMediaEntryData = _vPIP_GetDataFilter(_vPIP_GetMediaEntryData(NULL));
			}
			else
			{
				$avPIPMediaEntryData = _vPIP_GetDataFilter(_vPIP_GetMediaEntryData($post_id, 0));
			}

			//Truncate to $iLast post entries.
			$aTemp = $avPIPMediaEntryData;
			$iPostsProcessed = 0;
			$currPost = -1;
			$avPIPMediaEntryData = array();
			for ($i=0; $i<count($aTemp); $i++)
			{
				if ($currPost != $aTemp[$i]->post_ID)
				{
					$currPost = $aTemp[$i]->post_ID;
					$iPostsProcessed++;
					if ($iPostsProcessed > $iLast)
						break;
				}
				$avPIPMediaEntryData[$i] = $aTemp[$i];
			}

		}

		return $avPIPMediaEntryData;
	}


	function _vPIP_GetDataFilter($avPIPMediaEntryData)
	{
		$aReturn = array();
		$j = 0;
		for ($i=0; $i<count($avPIPMediaEntryData); $i++)
		{
			$avPIPMediaEntryDatum = $avPIPMediaEntryData[$i];
			if (! is_null($avPIPMediaEntryDatum->url))
			{
				if ($avPIPMediaEntryDatum->videoFormats_ID == -1)
					$avPIPMediaEntryDatum->mediaName = "Poster Image";
				else if ($avPIPMediaEntryDatum->videoFormats_ID == -2)
					$avPIPMediaEntryDatum->mediaName = "Thumbnail";
				else
				{
					$aVideoFormats = _vPIP_GetTableRows(TBL_VIDEOFORMATS, "id = " . $avPIPMediaEntryDatum->videoFormats_ID);
					if ($aVideoFormats  && count($aVideoFormats) > 0)
					{
						$avPIPMediaEntryDatum->mediaName = $aVideoFormats[0]->mediaName;
						if ($aVideoFormats[0]->mimeType == null ||
						    strlen($aVideoFormats[0]->mimeType) == 0)
						{
							$avPIPMediaEntryDatum->mediaType = _vPIP_getMediaType($avPIPMediaEntryDatum->url);
						}
						else
						{
							$avPIPMediaEntryDatum->mediaType = $aVideoFormats[0]->mimeType;
						}
					}
				}

				//MediaFor entry for mediaName & post_ID
				$avPIPMediaEntryDatum->mediaFor = _vPIP_GetMediaFor($avPIPMediaEntryDatum->mediaName, $avPIPMediaEntryDatum->post_ID);


				unset($avPIPMediaEntryDatum->videoFormats_ID);
				unset($avPIPMediaEntryDatum->extendedFlds);
				unset($avPIPMediaEntryDatum->objects);
				unset($avPIPMediaEntryDatum->id);

				$aReturn[$j] = $avPIPMediaEntryDatum;
				$j++;
			}
		}

		return $aReturn;
	}

	//===================== End of utilities =================================================

// F I L T E R S ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    // the_posts
    //

    function _vPIP__the_posts($the_posts)
    {
        global $wpdb;

        //
        // Get all the post_ids.
        //
            $post_ids = array();
            foreach ($the_posts AS $k => $v) {

                $post_ids[] = $the_posts[$k]->ID;

            } // foreach


            // SHORT CIRCUIT
            if (  empty($post_ids)  ) {
                return $the_posts;
            }



        //
        // Modify the $the_posts variable... to add the VideoPress Video API stuff.
        //
            foreach ($the_posts AS $k => $v) {



                    $vpip_video_raw = _vPIP_GetData($v->ID);

                    if (  isset($vpip_video_raw) && FALSE !== $vpip_video_raw && is_array($vpip_video_raw)  ) {

                        foreach ($vpip_video_raw AS $r) {


                            if (  'Poster Image' == $r->mediaName  ) {

                                    $poster_href   = $r->url;
                                    $poster_width  = $r->width;
                                    $poster_height = $r->height;

                                    if (  !isset($poster_href) || FALSE === $poster_href || !is_string($poster_href) || '' == trim($poster_href)  ) {
                        /////////////// CONTINUE
                                        continue;
                                    }

                                    if (  !isset($the_posts[$k]->video->poster) || FALSE === $the_posts[$k]->video->poster || !is_object($the_posts[$k]->video->poster)  ) {
                                        $the_posts[$k]->video->poster = ((object)NULL);
                                    }


                                    $the_posts[$k]->video->poster->href = $poster_href;

                                    if (  isset($poster_width) && FALSE !== $poster_width && is_numeric($poster_width)  ) {
                                        $the_posts[$k]->video->poster->width = $poster_width;
                                    }

                                    if (  isset($poster_height) && FALSE !== $poster_height && is_numeric($poster_height)  ) {
                                        $the_posts[$k]->video->poster->height = $poster_height;
                                    }




                            } else if (  'Thumbnail' == $r->mediaName  ) {

                                    $thumbnail_href   = $r->url;
                                    $thumbnail_width  = $r->width;
                                    $thumbnail_height = $r->height;

                                    if (  !isset($thumbnail_href) || FALSE === $thumbnail_href || !is_string($thumbnail_href) || '' == trim($thumbnail_href)  ) {
                        /////////////// CONTINUE
                                        continue;
                                    }

                                    if (  !isset($the_posts[$k]->video->thumbnail) || FALSE === $the_posts[$k]->video->thumbnail || !is_object($the_posts[$k]->video->thumbnail)  ) {
                                        $the_posts[$k]->video->thumbnail = ((object)NULL);
                                    }


                                    $the_posts[$k]->video->thumbnail->href = $thumbnail_href;

                                    if (  isset($thumbnail_width) && FALSE !== $thumbnail_width && is_numeric($thumbnail_width)  ) {
                                        $the_posts[$k]->video->thumbnail->width = $thumbnail_width;
                                    }

                                    if (  isset($thumbnail_height) && FALSE !== $thumbnail_height && is_numeric($thumbnail_height)  ) {
                                        $the_posts[$k]->video->thumbnail->height = $thumbnail_height;
                                    }




                            } else {

                                    if (  '1' != $r->isActive  ) {
                        /////////////// CONTINUE
                                        continue;
                                    }

                                    if ( '1' == $r->isDefault && isset($r->url) && FALSE !== $r->url && !empty($r->url)  ) {

                                        $video_href   = $r->url;
                                        $video_width  = $r->width;
                                        $video_height = $r->height;
                                        $video_type   = $r->mediaType;

                                        if (  !isset($the_posts[$k]->video) || FALSE === $the_posts[$k]->video || !is_object($the_posts[$k]->video)  ) {
                                            $the_posts[$k]->video = ((object)NULL);
                                        }

                                        if (  !isset($the_posts[$k]->video->playlist) || FALSE === $the_posts[$k]->video->playlist || !is_array($the_posts[$k]->video->playlist)  ) {
                                            $the_posts[$k]->video->playlist = array();
                                        }

                                        $the_posts[$k]->video->playlist[0] = ((object)NULL);

                                        $the_posts[$k]->video->playlist[0]->href = $video_href;

                                        if (  isset($video_width) && FALSE !== $video_width && is_numeric($video_width)  ) {
                                            $the_posts[$k]->video->playlist[0]->width = $video_width;
                                        }

                                        if (  isset($video_height) && FALSE !== $video_height && is_numeric($video_height)  ) {
                                            $the_posts[$k]->video->playlist[0]->height = $video_height;
                                        }

                                        if (  isset($video_type) && FALSE !== $video_type && is_string($video_type) && '' != trim($video_type)  ) {
                                            $the_posts[$k]->video->playlist[0]->type = $video_type;
                                        }
                                    }


                                    if (  isset($r->mediaFor) && FALSE !== $r->mediaFor && is_string($r->mediaFor) && !empty($r->mediaFor)  ) {

                                        $video_for    = $r->mediaFor;
                                        $video_href   = $r->url;
                                        $video_width  = $r->width;
                                        $video_height = $r->height;
                                        $video_type   = $r->mediaType;

                                        if (  !isset($video_for) || FALSE === $video_for || !is_string($video_for) || '' == trim($video_for)  ) {
                        /////////////////// CONTINUE
                                            continue;
                                        }

                                        if (  !isset($video_href) || FALSE === $video_href || !is_string($video_href) || '' == trim($video_href)  ) {
                        /////////////////// CONTINUE
                                            continue;
                                        }

                                        if (  !isset($the_posts[$k]->video) || FALSE === $the_posts[$k]->video || !is_object($the_posts[$k]->video)  ) {
                                            $the_posts[$k]->video = ((object)NULL);
                                        }

                                        if (  !isset($the_posts[$k]->video->for) || FALSE === $the_posts[$k]->video->for || !is_array($the_posts[$k]->video->for)  ) {
                                            $the_posts[$k]->video->for = array();
                                        }

                                        if (  !isset($the_posts[$k]->video->for[$video_for]) || FALSE === $the_posts[$k]->video->for[$video_for] || !is_object($the_posts[$k]->for[$video_for])  ) {
                                            $the_posts[$k]->video->for[$video_for] = ((object)NULL);
                                        }

                                        if (  !isset($the_posts[$k]->video->for[$video_for]->playlist) || FALSE === $the_posts[$k]->video->for[$video_for]->playlist || !is_object($the_posts[$k]->for[$video_for]->playlist)  ) {
                                            $the_posts[$k]->video->for[$video_for]->playlist = array();
                                        }

                                        $the_posts[$k]->video->for[$video_for]->playlist[0]->href = $video_href;

                                        if (  isset($video_width) && FALSE !== $video_width && is_numeric($video_width)  ) {
                                            $the_posts[$k]->video->for[$video_for]->playlist[0]->width = $video_width;
                                        }

                                        if (  isset($video_height) && FALSE !== $video_height && is_numeric($video_height)  ) {
                                            $the_posts[$k]->video->for[$video_for]->playlist[0]->height = $video_height;
                                        }

                                        if (  isset($video_type) && FALSE !== $video_type && is_string($video_type) && '' != trim($video_type)  ) {
                                            $the_posts[$k]->video->for[$video_for]->playlist[0]->type = $video_type;
                                        }
                                    }




                            }


                        } // foreach

                    }


            } // foreach


        //
        // Return.
        //
            return $the_posts;
    }

	//Process the content prior to display filter
	function _vPIP_Content_Process($content = '') {

		//If debugging, inform vPIPPlayer
		$vpipState = $_GET["vpipstate"];
		$byDebug = false;
		if (isset($vpipState))
		{
			if ($vpipState == "debug")
				$byDebug = true;
		}

		$sInterface = get_option("vPIP_Interface");

		if (!(get_option("vPIP_BracketedCode") === false) && get_option("vPIP_BracketedCode") == 'on')
		{
			 $sNewContent = $content;
		     if (strlen(trim($sNewContent)) > 0) {
			     //Replace all [div hVlog ...] occurances
				$pattern = '/(\[div)(\s*)(hvlog)(\s*)([^\]]*)([\]])/i'; // ([a-zA-Z\ \"\'-\:]*)(]) //(!\])*(\])
				$sNewContent = preg_replace_callback($pattern, "_vPIP_hVlog_Callback", $sNewContent);

			     //Replace all <a href>[vPIP ...] occurances
				$pattern = '/(\<a(\s*)href([^\>]*)\>)(\s*)(\[vpip)([^\]]*)(\])/i'; //'(\<a\s*href(\.*)\>)(\s*)(\[vPIP)(.*)(\])';
				$sNewContent = preg_replace_callback($pattern, "_vPIP_Callback", $sNewContent);
			     //Replace all [/div] occurances
				$pattern = '/(\[\/div\])/i';
				$sNewContent = preg_replace_callback($pattern, "_vPIP_divEnd_Callback", $sNewContent);

		     }

		     //If debugging, inform vPIP
			if ($byDebug)
			{
				$sNewContent = preg_replace('/vpipplay\(/i', 'vPIPPlayer.isDebugging(true); vPIPPlay(', $sNewContent);

			}

		     return $sNewContent;
		}
		else if ($sInterface === false || $sInterface == 'vPIPMediaEntry' || $sInterface == 'vlogsplosion')
		{

			//If VideoFormats table doesn't exist, assume Media Entry tables do not
			//  yet created, and create.
			//$table_name = TBL_VIDEOFORMATS;
			//Check if table already exists
			/* May be causing problems (Media drops on irenemcgee.com
			 *
			 if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
			{
				_vPIP_DBCreateTables();
			}
			*/

			$table_name = TBL_VIDEOFORMATS;
			$byUpdatingTables = false;
			if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0)
			{
				_vPIP_DBCreateTables();
				$byUpdatingTables = true;
			}
			else if (get_option("vpip_db_version") != "1.21") // Update to 1.21
			{
				$result = _vPIP_DBUpdateTo(get_option("vpip_db_version"), "1.21");
				$byUpdatingTables = true;
			}

			if (!$byUpdatingTables)
			{
				$sPrepend = _vPIP_GetVlgSpnEntry($GLOBALS['post']->ID);
			}
			else
			{
				$sPrepend = "<p><div style=\"text-align: center; color: red;\" >vPIP tables updated, please refresh page...</div></p>
	";
			}

		     //If debugging, inform vPIP
			if ($byDebug)
			{
				$sPrepend = preg_replace('/vpipplay\(/i', 'vPIPPlayer.isDebugging(true); vPIPPlay(', $sPrepend);

			}
			return $sPrepend . $content;

		}
		else
		{
			if ($byDebug)
			{
				$content = preg_replace('/vpipplay\(/i', 'vPIPPlayer.isDebugging(true); vPIPPlay(', $content);

			}
			return $content;
		}
	}

    //---------------------------------------------------------------------------------------------------------------------//


	//Helper function to _vPIP_Content_Process filter to get and display
	//the Media Entry data for this post.
	function _vPIP_GetVlgSpnEntry($post_id)
	{
		//Holds the HTML entries for the media formats
		$aFormats = array();
		$avPIPMediaEntryData = _vPIP_GetMediaEntryData($post_id, null, null);
		$sImage = "";
		$nFlashID = -1;
		$offerEmbedCode = -1;
		//Number of unique media formats
		if ($avPIPMediaEntryData) {

			$iHighestOrder = -1;
			for ($i=0; $i<count($avPIPMediaEntryData); $i++)
			{
				$vPIPMediaEntryData = $avPIPMediaEntryData[$i];
				//Poster Image
				if ($vPIPMediaEntryData->videoFormats_ID == -1)
				{
					if (strlen(trim($vPIPMediaEntryData->url)) > 0)
						$sImage = "<img src=\"" . $vPIPMediaEntryData->url . "\" alt=\"" . $GLOBALS['post']->post_title . "\" />";
				}
				else {
					$oFormat = _vPIP_GetMediaFormat($vPIPMediaEntryData->videoFormats_ID);
					if (strtolower($oFormat->mediaName) == "flash")
						$nFlashID = $vPIPMediaEntryData->videoFormats_ID;
					$mediaCall = $oFormat->mediaCall;
					// Insert URL
					$iPos = strpos($mediaCall, "~url~");
					//Styled for the whole entry available on the Flash MediaEntry column
					if ($nFlashID > -1 &&
						$vPIPMediaEntryData->videoFormats_ID == $nFlashID)
					{
						$embedCodeStyled = $vPIPMediaEntryData->embedCodeStyled;
					}

					//If a URL exists
					if (strlen(trim($vPIPMediaEntryData->url)) > 0 ||
						($vPIPMediaEntryData->useExtFlashPlayer == "1" &&
						strlen(trim($vPIPMediaEntryData->extFlashPlayer)) > 0))
					{
						//See if external flash player chosen and process as such:
						if (strtolower($oFormat->mediaName) == "flash" && $vPIPMediaEntryData->useExtFlashPlayer == 1 &&
							strlen($vPIPMediaEntryData->extFlashPlayer) > 0)
						{
							$extFlashPlayer = $vPIPMediaEntryData->extFlashPlayer;
							$extFlashPlayerFlashVars = trim($vPIPMediaEntryData->extFlashPlayerFlashVars);
							//If calling FLV
							if (strlen($vPIPMediaEntryData->movieParam) > 0)
							{
								$movieParam = $vPIPMediaEntryData->movieParam;
								$movieParamLoc = $vPIPMediaEntryData->movieParamLoc;

								if ($movieParamLoc == "URL")
								{
									$iLen = strlen($extFlashPlayer);
									if (strtolower(substr($extFlashPlayer, $iLen-3, 3)) == "swf")
									{
										$mediaCall = substr($mediaCall, 0, $iPos) . $extFlashPlayer .
													"?" . $movieParam . "=" . $vPIPMediaEntryData->url .
													substr($mediaCall, $iPos + 5);
									}
									else
									{
										$mediaCall = substr($mediaCall, 0, $iPos) . $extFlashPlayer .
														"&" . $movieParam . "=" . $vPIPMediaEntryData->url .
													substr($mediaCall, $iPos + 5);
									}
								}
								else
								{
									$mediaCall = substr($mediaCall, 0, $iPos) . $extFlashPlayer . substr($mediaCall, $iPos + 5);
								}
								// Insert mimetype
								$iPos = strpos($mediaCall, "~mimetype~");
								$mediaCall = substr($mediaCall, 0, $iPos) . "application/x-shockwave-flash" . substr($mediaCall, $iPos + 10);
								if ($movieParamLoc = "FlashVars")
								{

									//movieParam in a structure if it contains <mediaURL>
									$mediaURLStart = strpos(strtolower($movieParam), "<mediaurl>");
									if ($mediaURLStart !== false)
									{
										//Put url in structure
										$mediaURLEnd = strpos($movieParam, ">", $mediaURLStart);
										$movieParam = substr($movieParam, 0, $mediaURLStart) .
													  $vPIPMediaEntryData->url .
													  substr($movieParam, $mediaURLEnd+1);
									}
									else
									{
										$movieParam .= "=" . $vPIPMediaEntryData->url;
									}
									$movieParam = addslashes($movieParam);

									//Insert the FlashVars
									$iStart = _vPIP_findIterate($mediaCall, "'", 3);
									$iEnd = strpos(substr($mediaCall, $iStart+1), "'")+$iStart+1;
									$sFlashVars = substr($mediaCall, $iStart+1, $iEnd-$iStart-1);
									//If FlashVars already setup, append
									if (strlen($sFlashVars) > 0)
									{
										if (strlen($extFlashPlayerFlashVars) > 0)
										{
											$mediaCall = substr($mediaCall, 0, $iEnd) . "&" . $movieParam .
														"&" . $extFlashPlayerFlashVars .
														substr($mediaCall, $iEnd);
										}
										else
										{
											$mediaCall = substr($mediaCall, 0, $iEnd) . "&" . $movieParam .
														substr($mediaCall, $iEnd);
										}
									}
									//New FlashVars
									else
									{
										if (strlen($extFlashPlayerFlashVars) > 0)
										{
											$mediaCall = substr($mediaCall, 0, $iEnd) . $movieParam .
														"&" . $extFlashPlayerFlashVars .
														substr($mediaCall, $iEnd + 1);
										}
										else
										{
											$mediaCall = substr($mediaCall, 0, $iEnd) . $movieParam .
														substr($mediaCall, $iEnd + 1);
										}
									}
								}

							}
							//If network call to FLV
							else
							{
								$mediaCall = substr($mediaCall, 0, $iPos) . $extFlashPlayer . substr($mediaCall, $iPos + 5);
								// Insert mimetype
								$iPos = strpos($mediaCall, "~mimetype~");
								$mediaCall = substr($mediaCall, 0, $iPos) . "application/x-shockwave-flash" . substr($mediaCall, $iPos + 10);

								//Insert to not add the control height, and scale to "noScale"
								$iPos = strpos($mediaCall, "',");
								$mediaCall = substr($mediaCall, 0, $iPos) . ",AddControlHeight=false,scale=noScale" . substr($mediaCall, $iPos);
								//Insert the FlashVars
								$iStart = _vPIP_findIterate($mediaCall, "'", 3);
								$iEnd = strpos(substr($mediaCall, $iStart+1), "'")+$iStart+1;
								$sFlashVars = substr($mediaCall, $iStart+1, $iEnd-$iStart-1);
								//If FlashVars already setup, append
								if (strlen($sFlashVars) > 0)
								{
									if (strlen($extFlashPlayerFlashVars) > 0)
									{
										$mediaCall = substr($mediaCall, 0, $iEnd) . "&" . $extFlashPlayerFlashVars .
													substr($mediaCall, $iEnd);
									}
								}
								//New FlashVars
								else
								{
									if (strlen($extFlashPlayerFlashVars) > 0)
									{
										$mediaCall = substr($mediaCall, 0, $iEnd) . $extFlashPlayerFlashVars .
													substr($mediaCall, $iEnd + 1);
									}
								}

							}
							//Set flv=false
							$iPos = strpos(strtolower($mediaCall), "flv=true");
							if ($iPos > 0)
							{
								$mediaCall = substr($mediaCall, 0, $iPos) . "flv=false" .
											substr($mediaCall, $iPos + 8);
							}

						}
						else
						{
							$mediaCall = substr($mediaCall, 0, $iPos) . $vPIPMediaEntryData->url . substr($mediaCall, $iPos + 5);
							// Insert mimetype
							$iPos = strpos($mediaCall, "~mimetype~");
							$mediaCall = substr($mediaCall, 0, $iPos) . $oFormat->mimeType . substr($mediaCall, $iPos + 10);
						}

						if ($vPIPMediaEntryData->offerEmbedCode == 1 && $nFlashID > -1 &&
							$vPIPMediaEntryData->videoFormats_ID == $nFlashID)
						{
							$offerEmbedCode = $vPIPMediaEntryData->offerEmbedCode;
							$embedCodeBtnTitle = $vPIPMediaEntryData->embedCodeBtnTitle;
							$embedCodeDefault = $vPIPMediaEntryData->embedCodeDefault;
							$embedCodeInThickBox = $vPIPMediaEntryData->embedCodeInThickBox;
							$embedCodeThickBoxTitle = $vPIPMediaEntryData->embedCodeThickBoxTitle;
						}

						// Insert the width
						$iPos = strpos($mediaCall, "~width~");
						$mediaCall = substr($mediaCall, 0, $iPos) . $vPIPMediaEntryData->width . substr($mediaCall, $iPos + 7);
						// Insert the height
						$iPos = strpos($mediaCall, "~height~");
						$mediaCall = substr($mediaCall, 0, $iPos) . $vPIPMediaEntryData->height . substr($mediaCall, $iPos + 8);

						//If this is default media format use it on the image
						if ($vPIPMediaEntryData->isDefault == 1)
						{
							// Insert class="hvlogtarget"
							$iPos = strpos($mediaCall, ">");
							$imgMediaCall = substr($mediaCall, 0, $iPos) . " class=\"hvlogtarget\" " . substr($mediaCall, $iPos);

							if (strlen($sImage) > 0)
							{
								$aFormats[0] = $imgMediaCall . "\n" . $sImage . "</a>";
							}
							//If img tag not setup yet, set marker to defer for loop end
							else
							{
								$aFormats[0] = $imgMediaCall . "\n~image~</a>";
							}
							if ($vPIPMediaEntryData->isVisible != 1)
								$aFormats[0] = "<!-- " . $aFormats[0] . " -->";
						}

						if ($vPIPMediaEntryData->isActive == 1)
						{
							// Insert class="vpip-vs-mediatitle"
							$iPos = strpos($mediaCall, ">");
							$mediaCall = substr($mediaCall, 0, $iPos) . " class=\"vpip-vs-mediatitle\" " . substr($mediaCall, $iPos);

							if ($vPIPMediaEntryData->isVisible == 1)
							{
								$aFormats[$vPIPMediaEntryData->displayOrder] = $mediaCall . $oFormat->mediaName . "</a>";
							}
							else {
								$aFormats[$vPIPMediaEntryData->displayOrder] = "<!--" . $mediaCall . $oFormat->mediaName . "</a>-->";
							}
							if ($iHighestOrder < $vPIPMediaEntryData->displayOrder)
								$iHighestOrder = $vPIPMediaEntryData->displayOrder;
						}

					}
					else if ($vPIPMediaEntryData->offerEmbedCode == 1 && $nFlashID > -1 &&
							$vPIPMediaEntryData->videoFormats_ID == $nFlashID)
					{
						//Embed Code data
						$offerEmbedCode = $vPIPMediaEntryData->offerEmbedCode;
						$embedCodeBtnTitle = $vPIPMediaEntryData->embedCodeBtnTitle;
						$embedCodeDefault = $vPIPMediaEntryData->embedCodeDefault;
						$embedCodeInThickBox = $vPIPMediaEntryData->embedCodeInThickBox;
						$embedCodeThickBoxTitle = $vPIPMediaEntryData->embedCodeThickBoxTitle;
					}
				}
			} // foreach($avPIPMediaEntryData as $vPIPMediaEntryData)

			//If img tag not set yet, do it
			if ($iPos = strpos($aFormats[0], "~image~"))
			{
				if (strlen($sImage) > 0)
					$aFormats[0] = substr($aFormats[0], 0, $iPos-1) . $sImage . substr($aFormats[0], $iPos+7);
				else
					$aFormats[0] = "";
			}
		}

		$hvlogStyle = "";
		$formatsULStyle = "";
		$formatLIStyle = "";
		$formatsSep = "";
		//FIXME:  $embedCodeStyled needs to be renamed and to refer to styling the entire Media Entry area:
		if ($embedCodeStyled)
		{
			$hvlogStyle = "style=\"text-align: center\"";
			$formatsULStyle = "style=\"display: inline; margin: 0; padding: 0;\"";
			$formatLIStyle = "style=\"display: inline; background: none; margin: 0; padding: 0;\"";
			$formatsSep = " | ";
		}

		$sReturn = "<div class=\"hVlog\" " . $hvlogStyle . ">\n";
		$byFirstLink = TRUE;

		// Media Format is indexed $aFormats to displayOrder -- can be anywhere in $avPIPMediaEntryData list
		for($i=0; $i <= $iHighestOrder; $i++)
		{
			$format = $aFormats[$i];
			if (isset($format))
			{
				//If on poster image (1st format)
				if ($i == 0 && strlen($format) > 0)
				{
					$sReturn .= $format . "<br />\n<ul class=\"vpip-formatslist\" " . $formatsULStyle . " >";
				}
				else if (strlen($format) > 0) {
					if ($byFirstLink || substr($format,0,4) == "<!--")
					{
						$sReturn .= "<li  class=\"vpip-formatslistitem\" " . $formatLIStyle . " >" . $format . "</li>";
						$byFirstLink = FALSE;
					}
					else
					{
						$sReturn .= "<li class=\"vpip-formatslistitem\" " . $formatLIStyle . " >" . $formatsSep . $format . "</li>";
					}
				}
			}
		}
		if ($iHighestOrder > 0 && count($aFormats) > 1)
		{
			$sReturn .= "</ul>\n";
		}

		if ($offerEmbedCode == 1 && count($aFormats) > 0)
		{

			//If embedding all choices
			//$sEmbed = str_replace("\n", "", $sReturn);

			//$vPIP_scripts = "	<script src=\"" . $GLOBALS['vPIPLocation'] . "/vpip.js\" type=\"text/javascript\"></script>";
			/* Not Yet Implemented:
			if ($embedCodeInThickBox)
			{
				$vPIP_scripts .= "	<style type=\"text/css\" media=\"all\">@import \"" . $GLOBALS['vPIPLocation'] . "/vPIPBox.css\";</style>
						<script src=\"" . $GLOBALS['vPIPLocation'] . "/jquery.js\" type=\"text/javascript\"></script>\n";
			}*/
			//$sReturn .= "<br />\n";
			//$sReturn .= "<script type=\"text/javascript\">\n";
			//$sReturn .= "sScripts" . $post_id . " = \"" . rawurlencode($vPIP_scripts) . "\";\n";
			//$sReturn .= " sEmbed" . $post_id . " = \"" . addslashes($sEmbed) . "</div>\";\n";

			//$sReturn .= "</script>\n";
			if ($embedCodeStyled)
			{
				$embedCodeShareBtnStyle = "style=\"margin-top: 3px; margin-left: auto; margin-right: auto; background: #DDDDDD; width: 40px; border: 1px solid #999999;\"";
				$embedCodeAreaStyle = "style=\"padding: 0; margin-top: 3px; border: 2px solid #999999; background: #DDDDDD; font-size: 12px; \"";
				$embedCodeCloseBtn = "style=\"margin: 0; font-size: 11px; \"";
				$embedCodeTableStyle = "style=\"width: 96%; margin-left: 2%; \"";
				$embedCodeByStyled = "style=\"margin: 0; font-size: 8px; text-align: left; \"";
				$embedCodeTextAreaStyle = "style=\"font-size: 9px; margin-bottom: 5px; overflow: scroll; overflow-x: hidden; overflow-y: scroll; overflow:-moz-scrollbars-vertical; width: 100%; padding: 0; margin-left: 0; \"";
			}
			else
			{
				$embedCodeShareBtnStyle = "";
				$embedCodeAreaStyle = "";
				$embedCodeCloseBtn = "";
				$embedCodeTableStyle = "";
				$embedCodeByStyled = "";
				$embedCodeTextAreaStyle = "";
			}


			$sReturn .= "<div class=\"EmbedCodeShareBtn\" id=\"divEmbedCodeShare" . $post_id . "\" " . $embedCodeShareBtnStyle . "  >\n";
			$sReturn .= "	<a href=\"#\" onclick=\"vPIP_setVisible('divEmbedCode" . $post_id . "', true); vPIP_setEmbed(this, 'vPIP_embedCode" . $post_id . "'); vPIP_setVisible('divEmbedCodeShare" . $post_id . "', false); return false;\" >" . $embedCodeBtnTitle . "</a><br />";
			$sReturn .= "</div>\n";
			$sReturn .= "<div class=\"EmbedCodeArea\" id=\"divEmbedCode" . $post_id . "\" " . $embedCodeAreaStyle. ">\n";
			$sReturn .= "<table class=\"EmbedCodeTable\" border=\"0\" " . $embedCodeTableStyle . ">\n";
			$sReturn .= "	<tr>\n";
			$sReturn .= "   	<td width=\"15%\" ><div class=\"EmbedCodeBy\" " . $embedCodeByStyled . "> by: <a href=\"http://vpip.org/\" target=\"_blank\" >vPIP</a></div></td>\n";
			$sReturn .= "		<td>Embed (copy & paste):</td>\n";
			$sReturn .= "   	<td width=\"20%\" ><div class=\"EmbedCodeCloseBtn\" " . $embedCodeCloseBtn . ">	<a href=\"#\" onclick=\"vPIP_setVisible('divEmbedCode" . $post_id . "', false); vPIP_setVisible('divEmbedCodeShare" . $post_id . "', true); return false;\" >close</a><br /></div></td>\n";
			$sReturn .= "	</tr>\n";
			$sReturn .= "	<tr>\n";
			$sReturn .= "		<td colspan=\"3\"><textarea class=\"EmbedCodeTextarea\" rows=\"5\" name=\"vPIP_embedCode" . $post_id . "\" id=\"vPIP_embedCode" . $post_id . "\" " . $embedCodeTextAreaStyle . " onfocus=\"if(window.vPIP_copyToClipBrd){window.vPIP_copyToClipBrd(this)};\" readonly=\"readonly\" >";
			$sReturn .= "</textarea></td>\n";
			$sReturn .= "	</tr>\n";
			$sReturn .= "</table>\n";
			$sReturn .= "</div>";
			$sReturn .= "<script type=\"text/javascript\">";
			$sReturn .= "	vPIP_setVisible(\"divEmbedCode" . $post_id . "\", false);";
			$sReturn .= "</script>";

		}

		$sReturn .= "\n</div>\n";

		return $sReturn;
	}

	function _vPIP_getMediaType($sMovie)
	{
		$mediaType = null;
		if ($sMovie != null) {

			$sFileExt = strtolower(substr($sMovie, strpos($sMovie, '.')));
			switch ($sFileExt) {
				case ".mov":
				case ".mp4":
				case ".m4v":
				case ".mp3":
				case ".3gp":
					$mediaType = "quicktime/video";
					break;

				case ".smi":
				case ".smil":
					$mediaType = "application/smil";
					break;

				case ".avi":
				case ".wmv":
				case ".asf":
				case ".wma":
					$mediaType = "video/x-ms-wmv";
					break;

				case ".swf":
					$mediaType = "application/flash";
					break;
				case ".flv":
					$mediaType = "video/x-flv";
					break;
               case ".ogg":
               case ".ogv":
               case ".oga":
	               $mediaType = "application/ogg";
	               break;

			}

		}

		return $mediaType;
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////// F I L T E R S //
	function _vPIP_hVlog_Callback($matches) {
		$matches[5] = _vPIP_untexturize($matches[5]);
		return '<div class="hVlog" ' .  $matches[5] . ' >';
	}

	function _vPIP_Callback($matches) {
		$matches[3] = _vPIP_untexturize($matches[3]);
		$matches[6] = _vPIP_untexturize($matches[6]);
		return '<a href' .  $matches[3] . ' ' . $matches[6] . ' >';
	}

	function _vPIP_divEnd_Callback($matches) {
		return '</div>';
	}

	function _vPIP_untexturize($sString) {
		//removed texturized double quote
		$sString = preg_replace("/&#8220;/","\"",$sString);
		$sString = preg_replace("/&#8221;/","\"",$sString);
		//removed texturized single quote
		$sString = preg_replace("/&#8216;/","'",$sString);
		$sString = preg_replace("/&#8217;/","'",$sString);
		$sString = preg_replace("/&#8242;/","'",$sString);
		return $sString;
	}

	function _vPIP_findIterate($sString, $sFind, $iIterate)
	{
		$sEval = $sString;
		$iPos = 0;
		for ($i=0; $i<$iIterate; $i++)
		{
			$iLoc = strpos($sEval, $sFind);
			if ($iLoc)
			{
				if ($i == 0)
					$iPos += $iLoc;
				else
					$iPos += $iLoc+1;

				$sEval = substr($sEval, $iLoc+1);
			}
			else
			{
				break;
			}
		}

		return $iPos;
	}
?>
