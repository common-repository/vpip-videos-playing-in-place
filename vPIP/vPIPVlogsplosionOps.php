<?php
/*
 * Created on May 17, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require_once("../../../wp-config.php");

//Tables constants
define("TBL_VIDEOFORMATS", $GLOBALS['wpdb']->prefix . "vPIP_VideoFormats");
define("TBL_VIDEOFMTSDEFAULT", $GLOBALS['wpdb']->prefix . "vPIP_VideoFmtsDefault");
define("TBL_VIDEOFORMAT", $GLOBALS['wpdb']->prefix . "vPIP_VideoFormat");


$op = $_POST['op'];
//For testing: 
//$op = $_GET['op'];

if ($op == "updateToBeta") 
{
	_vPIP_UpdateToBeta();
}
else if ($op == "updateMediaCall") 
{
	_vPIP_UpdateMediaCall();
}
else if ($op == "transferEnclosureFields") 
{
	_vPIP_TransferEnclosures();
}

	function _vPIP_UpdateToBeta() 
	{
		//Table:  VideoFormats
		//        ------------
		//Contains video formats (flash, quicktime, windows media) and 
		// 	associated display code
		$table_name = TBL_VIDEOFORMATS;
		$byNewTable = false;
		$byUpdateTable = false;
		$byAddMedia = false;

		//Check if table already exists
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0) 
		{
			$byNewTable = true;
		}
		else if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " movieParamLoc"))
		{
			$byUpdateTable = true;	
		}
		
		if (! $GLOBALS['wpdb']->get_row("SELECT mediaName FROM " . $table_name . " WHERE mediaName = 'quicktime for iPod'"))
			$byAddMedia = true;
			
		// mediaName = Flash/Quicktime/WindowsMedia
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
				useExtFlashPlayer tinyint,
				extFlashPlayer text,
				movieParam text,
				movieParamLoc tinytext, 
				extFlashPlayerFlashVars text, 
				UNIQUE KEY id (id));";
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
		
		if ($byNewTable)
		{
			$insert = "INSERT INTO " . $table_name . " (mediaName, mediaCall, descript, width, height, mimeType, isDefault, displayOrder, isActive, isVisible) " . "VALUES " .
			          "('flash','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~,flv=true', 'FLVbuffer=10', ''); return false;\" >") . "', 'Flash media (FLV)', 640, 480, 'video/x-flv', 1, 1, 1, 1), " .
			          "('quicktime','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', 'Quicktime media', 640, 480, 'video/quicktime', 0, 2, 1, 1), " .
			          "('quicktime for iPod','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 3, 1, 1), " .
			          "('quicktime for Apple TV','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 4, 1, 1), " .
			          "('quicktime for Mobile','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 5, 1, 1), " .
			          "('windows media','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', 'Windows Media', 640, 480, 'video/x-ms-wmv', 0, 6, 1, 1) ";

			$results = $GLOBALS['wpdb']->query( $insert );
			add_option("vPIP_Interface", "vlogsplosion");
		}
		else if ($byUpdateTable)
		{
			$update = "UPDATE " . $table_name . " SET isActive = 1, isVisible = 1, useExtFlashPlayer = 0, extFlashPlayer = '', movieParam = '', movieParamLoc = ''";
			$results = $GLOBALS['wpdb']->query( $update );
			$update = "UPDATE " . $table_name . " SET isDefault = 1, displayOrder = 1 WHERE mediaName = 'flash'";
			$results = $GLOBALS['wpdb']->query( $update );
			$update = "UPDATE " . $table_name . " SET displayOrder = 2 WHERE mediaName = 'quicktime'";
			$results = $GLOBALS['wpdb']->query( $update );
			$update = "UPDATE " . $table_name . " SET displayOrder = 2 WHERE mediaName = 'windows media'";
			$results = $GLOBALS['wpdb']->query( $update );
		}
		
		if ($byAddMedia)
		{
			$insert = "INSERT INTO " . $table_name . " (mediaName, mediaCall, descript, width, height, mimeType, isDefault, displayOrder, isActive, isVisible) " . "VALUES " .
			          "('quicktime for iPod','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 3, 1, 1), " .
			          "('quicktime for Apple TV','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 4, 1, 1), " .
			          "('quicktime for Mobile','" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "', '', 640, 480, 'video/quicktime', 0, 5, 1, 1) ";
			
			$results = $GLOBALS['wpdb']->query( $insert );
			$update = "UPDATE " . $table_name . " SET displayOrder = 6 WHERE mediaName = 'windows media'";
			$results = $GLOBALS['wpdb']->query( $update );
		}
		
		add_option("vpip_db_version", "1.0");

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
				UNIQUE KEY id (id));";
		dbDelta($sql);
		if ($byNewTable)
		{
			$insert = "INSERT INTO " . $table_name . " (width, height, align) " . "VALUES " .
			          "(640, 480, 'TC') ";
			
			$results = $GLOBALS['wpdb']->query( $insert );
		}

		//Table:  VideoFormat
		//        ------------
		$table_name = TBL_VIDEOFORMAT;
		$byNewTable = false;
		$byUpdateTable = false;
		//Check if table already exists
		if(strcasecmp($GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table_name'"), $table_name) != 0) 
			$byNewTable = true;
		else if (! $GLOBALS['wpdb']->get_row("DESCRIBE " . $table_name . " movieParamLoc"))
		{
			$byUpdateTable = true;	
		}
		
				
		$sql = "CREATE TABLE " . $table_name . " (
				 id mediumint(9) NOT NULL AUTO_INCREMENT, 
				 videoFormats_ID mediumint(9) NOT NULL, 
				 post_ID mediumint(9) NOT NULL, 
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
				 UNIQUE KEY id (id));";
		dbDelta($sql);
		
		if ($byUpdateTable)
		{
			$update = "UPDATE " . $table_name . " SET isActive = 1, isVisible = 1, useExtFlashPlayer = 0, extFlashPlayer = '', movieParam = '', movieParamLoc = ''";
			$results = $GLOBALS['wpdb']->query( $update );
			$update = "UPDATE " . $table_name . ", " . TBL_VIDEOFORMATS . 
				" SET " . TBL_VIDEOFORMAT . ".isDefault = 1, " . TBL_VIDEOFORMAT . ".displayOrder = 1 WHERE " . TBL_VIDEOFORMAT . ".videoFormats_ID = " . 
				TBL_VIDEOFORMATS . ".id AND " . TBL_VIDEOFORMATS . ".mediaName = 'flash'";
			$results = $GLOBALS['wpdb']->query( $update );
			$update = "UPDATE " . $table_name . ", " . TBL_VIDEOFORMATS . 
				" SET " . TBL_VIDEOFORMAT . ".isDefault = 0, " . TBL_VIDEOFORMAT . ".displayOrder = 2 WHERE " . TBL_VIDEOFORMAT . ".videoFormats_ID = " . 
				TBL_VIDEOFORMATS . ".id AND " . TBL_VIDEOFORMATS . ".mediaName = 'quicktime'";
			$results = $GLOBALS['wpdb']->query( $update );
			$update = "UPDATE " . $table_name . ", " . TBL_VIDEOFORMATS . 
				" SET " . TBL_VIDEOFORMAT . ".isDefault = 0, " . TBL_VIDEOFORMAT . ".displayOrder = 3 WHERE " . TBL_VIDEOFORMAT . ".videoFormats_ID = " . 
				TBL_VIDEOFORMATS . ".id AND " . TBL_VIDEOFORMATS . ".mediaName = 'windows media'";
			$results = $GLOBALS['wpdb']->query( $update );
		}
		
		if ($byAddMedia)
		{
			$update = "UPDATE " . $table_name . " SET displayOrder = 6 WHERE displayOrder = 3";
			$results = $GLOBALS['wpdb']->query( $update );
		}
		
		echo "Data tables updated to Beta version.";
	}
	
	function _vPIP_UpdateMediaCall() {
		$table_name = TBL_VIDEOFORMATS;
		//Update Flash mediaCall
		$update = "UPDATE " . $table_name . " SET mediaCall = '" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~,flv=true', 'FLVbuffer=10', ''); return false;\" >") . "' WHERE mediaName = 'flash'";
		$result = $GLOBALS['wpdb']->query( $update );
		
		//Update quicktime mediaCall
		$update = "UPDATE " . $table_name . " SET mediaCall = '" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "' WHERE mediaName = 'quicktime'";
		$result = $GLOBALS['wpdb']->query( $update );

		//Update quicktime for iPod mediaCall
		$update = "UPDATE " . $table_name . " SET mediaCall = '" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "' WHERE mediaName = 'quicktime for iPod'";
		$result = $GLOBALS['wpdb']->query( $update );

		//Update quicktime for Apple TV mediaCall
		$update = "UPDATE " . $table_name . " SET mediaCall = '" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "' WHERE mediaName = 'quicktime for Apple TV'";
		$result = $GLOBALS['wpdb']->query( $update );

		//Update quicktime for Mobile mediaCall
		$update = "UPDATE " . $table_name . " SET mediaCall = '" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "' WHERE mediaName = 'quicktime for Mobile'";
		$result = $GLOBALS['wpdb']->query( $update );

		//Update windows media mediaCall
		$update = "UPDATE " . $table_name . " SET mediaCall = '" . $GLOBALS['wpdb']->escape("<a href=\"~url~\" type=\"~mimetype~\" onclick=\"vPIPPlay(this,'width=~width~,height=~height~', '', ''); return false;\" >") . "' WHERE mediaName = 'windows media'";
		$result = $GLOBALS['wpdb']->query( $update );

		echo "Data table updated to Beta-2 version.";
	}
	
	//TODO:  Find out where the data is stored and transfer it:
	function _vPIP_TransferEnclosures() 
	{
		$processed = 0;
		$sql = "SELECT * FROM " . $GLOBALS['wpdb']->prefix . "postmeta";
		$results = $GLOBALS['wpdb']->query( $sql );
		
		$aData =  $GLOBALS['wpdb']->get_results();
		if ($aData) {
			//Load lowercase videoformat titles to id index
			$sql = "SELECT * FROM " . TBL_VIDEOFORMATS;
			$results = $GLOBALS['wpdb']->query( $sql );
			
			$aData2 =  $GLOBALS['wpdb']->get_results();
			$aFormats = array();
			$aFormatsData = array();
			for ($i=0; $i<count($aData2); $i++)
			{
				$aFormats[$aData2[$i]->id] = strtolower($aData2[$i]->mediaName);
				$aFormatsData[$aData2[$i]->id] = $aData2[$i];
			}
			
			$table_name = TBL_VIDEOFORMAT;
			//foreach($aVlogsplosionData as $vlogsplosionData)
			for ($i=0; $i<count($aData); $i++)
			{
				$data = $aData[$i];
				$post_id = $data->post_id;
				if (trim($data->meta_key) == 'enclosure')
				{
					$url = FALSE;
					$length = FALSE;
					$mimetype = FALSE;
					$mediaName = FALSE;
					$visible = FALSE;
					$width = FALSE;
					$height = FALSE;
					
					$aEntries = split("\r\n", $data->meta_value);
					$url = trim($aEntries[0]);
					$length = trim($aEntries[1]);
					$mimetype = trim($aEntries[2]);
					$mediaName = trim($aEntries[3]);
					if (count($aEntries) > 4)
					{
						$visible = trim($aEntries[4]);
						if (strtolower($visible) == "hide")
							$visible = 0;
						else
							$visible = 1;
					}
					if (count($aEntries) > 5)
						$width = trim($aEntries[5]);
					if (count($aEntries) > 6)
						$height = trim($aEntries[6]);
					
					$id = array_search(strtolower($mediaName), $aFormats);	
					if ($id)
					{
						$oFormat = $aFormatsData[$id];
						if (!$visible)
							$visible = $oFormat->isVisible;
						if (!$width)
							$width = $oFormat->width;
						if (!$height)
							$height = $oFormat->height;
						if ($oFormat->useExtFlashPlayer)
							$useExtFlashPlayer	= $oFormat->useExtFlashPlayer;
						else
							$useExtFlashPlayer =  0;
							
						//See if entry is in table
						$sql = "SELECT * FROM " . $table_name . " WHERE post_ID = " . $post_id .
								" AND videoFormats_ID = " . $id;
						$result = $GLOBALS['wpdb']->query( $sql );
						if (! $result) 
						{
							$insert = "INSERT INTO " . $table_name . 
									  " (videoFormats_ID, post_ID, url, width, height, isDefault, displayOrder, isVisible, isActive, useExtFlashPlayer, extFlashPlayer, movieParam, movieParamLoc, extFlashPlayerFlashVars) " .
							    "VALUES (" . $id . "," . $post_id . ",'" . $url . "'," . $width . "," . $height . "," . 
							    		$oFormat->isDefault . "," . $oFormat->displayOrder . "," . $visible . "," . $oFormat->isActive . "," . $useExtFlashPlayer . ",'" . $oFormat->extFlashPlayer . "','" . $oFormat->movieParam . "','" . $oFormat->movieParamLoc . "','" . $oFormat->extFlashPlayerFlashVars . "')";  
							$result = $GLOBALS['wpdb']->query( $insert );
							if ($result) 
							{
								$update = "UPDATE " . $GLOBALS['wpdb']->prefix . "postmeta SET meta_key='" . trim($data->meta_key) . "-old' WHERE post_id = " . $post_id . " AND meta_value='" . $data->meta_value . "'"; 
								$result = $GLOBALS['wpdb']->query( $update );
							}
							else
							{
								$GLOBALS['wpdb']->print_error();
							}
							$processed++;
						}
						
					}				
				}
				else if (trim($data->meta_key) == 'videoLinkImage' ||
						 trim($data->meta_key) == 'thumbnail')
				{
					//See if poster image is in table
					$sql = "SELECT * FROM " . $table_name . " WHERE post_ID = " . $post_id .
							" AND videoFormats_ID = -1";
					$result = $GLOBALS['wpdb']->query( $sql );
					if (! $result) 
					{
						$url = $data->meta_value;
						$insert = "INSERT INTO " . $table_name . 
								  " (videoFormats_ID, post_ID, url, isDefault, isVisible, isActive, useExtFlashPlayer, extFlashPlayer, movieParam, movieParamLoc) " .
						    	  "VALUES (-1," . $post_id . ",'" . $url . "',0,1,1,0,'','','')";  
						$result = $GLOBALS['wpdb']->query( $insert );
						if ($result)
						{
							$update = "UPDATE " . $GLOBALS['wpdb']->prefix . "postmeta SET meta_key='" . trim($data->meta_key) . "-old' WHERE post_id = " . $post_id . " AND meta_value='" . $data->meta_value . "'"; 
							$result = $GLOBALS['wpdb']->query( $update );
						} 
						else
						{
							$GLOBALS['wpdb']->print_error();
						}
						$processed++;
					}
					
				}
			}
		}
		
		if ($processed == 0)
			echo "No entries found to update into Vlogsplosion.";
		else
			echo $processed . " entires updated to Vlogsplosion";
	}

?>
