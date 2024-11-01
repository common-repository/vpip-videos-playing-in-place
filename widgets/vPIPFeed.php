<?php
/*
 * Created on Apr 25, 2007 by Enric Teller for Show In A Box system
 * Description: vPIPFeed Creates feeds for specified media types (i.e., mov, flv, wmv, etc.).
 * Author: Enric Teller
 * Version: 0.21c
 * Author URI: http://www.vpip.org
 * 
 * New:
 * - functions prepended with _vPIPFeed_
 * - _vPIPFeed_GetExt(...) returns the anchor url extension
 * - Get and insert default anchor content for selected media at top of first hVlog div
 * - Change to X11 license compatible with GPL
 *
 * Next:  title= parameter option to change the <channel><title>...</title>
 * 
 * TODO:  On "blogURL", check for ending "?".  If it doesn't exist, add or use "&" to append 
 * 			"feed=rss2".  Unless "/feed" is in url.  Then don't append.
 *
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
	//Inject the RSS XML header into document.
	header('Content-type: application/rss+xml');
	//$startTimeTop = microtime(true);

	$ChannelTitle = NULL;
	$vsTitle = "";
	$aMedia = array();
	$aSearchPattern = array();
	$aExt = array();
	$aType = array();

	$vsTitleClass = "vpip-vs-mediatitle";

	_vPIPFeed_startup();
	
	function _vPIPFeed_startup() 
	{
		global $ChannelTitle, $vsTitle, $aMedia, $aSearchPattern, $aExt, $aType;
		
		// --- read GET parameters ---
		// The URL to where to read the RSS2 from
		$blogURL = $_GET['blogURL'];
		// The media extension to have unique in the RSS2 output
		$media = $_GET['media'];
		$type  = $_GET['type'];
		$ChannelTitle  = $_GET['title'];
		//$resync  = $_GET['resync'];
		//Add removebrkbarline=true/*false (removes first line if single words separated by brkbar, |, -- video links)
		//Add viedeoonly=true/*false (includes only video entries)
		
		if ($blogURL == NULL)
			exit("Missing the blogURL GET parameter.");
		//The vlogsplosion title
		$iType = -1;
		if ($type !== false && $type != NULL)
			$aType = explode($type);
		if ($media == NULL)
			exit("Missing the media GET parameter.");
		else {
			$media = strtolower($media);
			$aMedia = explode(",", $media);
			foreach ($aMedia as $media )
			{
				if (substr(trim($media),0,9) == "vs-title:")
				{
					array_push($aSearchPattern, "/.*" . $GLOBALS['vsTitleClass'] . "/i");
					array_push($aExt, trim($media));
					//The vlogsplosion title to match to
					$vsTitle = strtolower(trim(substr($media, 10)));
				}
				else if (($iExtPos = strpos($media, "."))) {
					array_push($aSearchPattern, _vPIPFeed_wildcardToRegex($media));
					array_push($aExt, substr($media, $iExtPos+1));
				}
				else {
					array_push($aSearchPattern, "/.*\." . $media . "/i");
					array_push($aExt, $media);
				}
				$i = count($aExt)-1;
				if (count($aType) < $i+1)
					array_push($aType, _vPIPFeed_SetType($aExt[$i]));
				
			}
		}
	
		
		$blogContent = NULL;	
	
		$feedExt = "?feed=rss2";
		if (strpos($blogURL, "?"))
			$feedExt = "&feed=rss2";
		_vPIPFeed_ffl_HttpGet($blogURL . $feedExt, _vPIPFeed_processFeed);
	}

	function _vPIPFeed_processFeed($sContent, $sType)
	{
		global $ChannelTitle, $vsTitle, $aMedia, $aSearchPattern, $aExt, $aType;
		
	    // Throw out the header
		if ($sType == "header")
		{
			return;
		}
		
		if (ob_get_level() == 0) ob_start();
		$blogContent = $sContent;
		//$startTimeAfterWP = microtime(true);
			
		if ($blogContent == NULL)
			exit("Missing RSS2 data.\n");
		$blogContentLower = strtolower($blogContent);
		
		//If processing header and new Channel Title, modify
		if ($ChannelTitle != NULL)
		{
			//See if Channel container tag is present
			$iChannelStart = strpos($blogContentLower, "<channel>");
			if ($iChannelStart !== false)
			{
				$iTitleStart = strpos($blogContentLower, "<title");
				if ($iTitleStart !== false)
				{
					
					$iTitleEnd = strpos($blogContentLower, "</title>")+8;
					if ($iTitleEnd !== false)
					{
						$sTitle = substr($blogContentLower, $iTitleStart, $iTitleEnd-$iTitleStart);
						$iTitleStart2 = strpos($sTitle, ">")+1 ;
						$iTitleEnd2 = strpos($sTitle, "</title>");
						$sTitleRepl = substr($sTitle, 0, $iTitleStart2) . $ChannelTitle . substr($sTitle, $iTitleEnd2);
						$blogContent = substr($blogContent, 0, $iTitleStart) . $sTitleRepl . substr($blogContent, $iTitleEnd);
						$blogContentLower = strtolower($blogContent);
					}
				}
				
			}
		}
		
		// The item section to check
		$iStartItem = strpos($blogContentLower, "<item>");
		
		// <item>...</item> loop
		while ($iStartItem !== false)
		{
			$mediaURL = NULL;
			$iEndItem = strpos($blogContentLower, "</item>", $iStartItem);
			$itemArea = substr($blogContent, $iStartItem, $iEndItem+7-$iStartItem);
			$anchorDefault = _vPIPFeed_GetAnchorDefault($itemArea);
			$itemArea = _vPIPFeed_striphVlog($itemArea);
			$itemArea = _vPIPFeed_removeGunk($itemArea, "<![cdata[", "vpip_setvisible");
			$icontentEncodedStart = strpos(strtolower($itemArea), "<content:encoded>");
	
			if ($icontentEncodedStart !== false) 
			{
		
				$icontentEncodedEnd = strpos(strtolower($itemArea), "</content:encoded>");
				$contentEncodedArea = substr($itemArea, $icontentEncodedStart, $icontentEncodedEnd+18-$icontentEncodedStart);
				//Search item area for media
				$iStartA = strpos(strtolower($contentEncodedArea), "<a ");
				//<content:encoded>...</content:encoded> loop
				while ($iStartA !== false)
				{
					$iEndA = strpos(strtolower($contentEncodedArea), "</a", $iStartA);
					//If ending </a> missing, find the end of the tag (">")
					if ($iEndA === false)
						$iEndA = strpos(strtolower($contentEncodedArea), ">", $iStartA);
					if ($iEndA === false)
						$iEndA = $iStartA+1;
					$anchor = substr($contentEncodedArea, $iStartA, $iEndA-$iStartA);
					$iMediaFound = 0;
					$iMediaPos = FALSE;
					for ($i=0; $i < count($aSearchPattern); $i++)
					{
						$searchPattern = $aSearchPattern[$i];
						$ext = $aExt[$i];
						//If 'vs-title: ', search for "vpip-vs-mediatitle" and get media extension location
						if (substr($ext,0,9) == "vs-title:" && strpos(strtolower($anchor), $GLOBALS['vsTitleClass']) !== false)
						{
							//See if title matches to vlogspoltion title:
							$iPosTitleStart = strpos($anchor, ">")+1; 
							//$iPosTitleEnd = strpos($anchor, "<", $iPosTitleStart)+1; 
							
							if ($iPosTitleStart > 1)
								$title = trim(substr($anchor, $iPosTitleStart));
							else
								$title = "";
							if (strtolower($title) == $vsTitle)
							{
								$iMediaFound = preg_match($searchPattern, $anchor);
								if ($iMediaFound > 0)
								{
									$oExt = _vPIPFeed_GetExt($anchor);
									$ext = $oExt["ext"];
									$iMediaPos = $oExt["iMediaPos"];
									
									if ($ext !== false)
									{
										$aType[$i] = _vPIPFeed_SetType($ext);
									}
									else
									{
										$iMediaPos = false;
									}
									
								}
								
							}
							
						}
						else 
						{
							$iMediaFound = preg_match($searchPattern, $anchor);
							$iMediaPos = strpos(strtolower($anchor), "." . $ext);
						}
						if ($iMediaFound > 0) 
						{
							$iType = $i;
							break;
						}
					}
					
					//media extension found!
					if ($iMediaFound > 0)
					{
						$iMediaStart = strpos(strtolower($anchor), "http://");
						if ($iMediaStart !== false && $iMediaPos != false) 
						{
							$mediaURL = substr($anchor, $iMediaStart, $iMediaPos-$iMediaStart-1) . "." . $ext;
						}
						
						//If calling external flash player, look for flv file
						if (strtolower(substr($mediaURL,strlen($mediaURL)-4)) == ".swf")
						{
							$swfURL = $mediaURL;
							$mediaURL = "";
							$iFlvEndPos = strpos(strtolower($anchor), ".flv");
							if ($iFlvEndPos !== false)
							{
								$subanchor = substr($anchor, $iMediaPos, $iFlvEndPos-$iMediaPos+4);
								$iFlvStartPos = stringrpos(strtolower($subanchor), "http://");
								if ($iFlvEndPos !== false)
								{
									$mediaURL = substr($subanchor, $iFlvStartPos);
									
									//replace reference in content Encoded to swf to flv for aggregators that 
									//use the <a href="..." entry to create the enclosure.
									$iSWFStart = strpos($contentEncodedArea, $swfURL);
									$contentEncodedArea = substr($contentEncodedArea, 0, $iSWFStart) . $mediaURL . 
														  substr($contentEncodedArea, $iSWFStart + strlen($swfURL));

									$iEndA = strpos(strtolower($contentEncodedArea), "</a", $iStartA);
									//If ending </a> missing, find the end of the tag (">")
									if ($iEndA === false)
										$iEndA = strpos(strtolower($contentEncodedArea), ">", $iStartA);
									if ($iEndA === false)
										$iEndA = $iStartA+1;
								}
							}
						}
					}  // if ($iMediaPos)
					//Strip out anchor so sites like feedburner won't use them to generate an enclosure
					else 
					{
						//  Find end of <a
						if ($iEndA !== false)
						{
							$iEndA2 = strpos(strtolower($contentEncodedArea), ">", $iEndA)+1;
							$anchor = substr($contentEncodedArea, $iStartA, $iEndA-$iStartA);
							$oExt = _vPIPFeed_GetExt($anchor);
							$theExt = $oExt["ext"];
							if ($theExt !== false)
							{
								$hasType = _vPIPFeed_SetType($theExt);
								if ($hasType !== false && $hasType != null)
									$contentEncodedArea = substr($contentEncodedArea, 0, $iStartA) . substr($contentEncodedArea, $iEndA2);
								else
									$iStartA = $iEndA2;
							}
						}
						$iEndA = $iStartA;
					}
					
					$iStartA = strpos(strtolower($contentEncodedArea), "<a ", $iEndA);
				} // while ($iStartA !== false)
				//Insert the new content Encoded Area
				$itemArea = substr($itemArea, 0, $icontentEncodedStart) . $contentEncodedArea . substr($itemArea, $icontentEncodedEnd+18);
				
				//Remove enclosures put in
				$iEnclosureStart =  strpos(strtolower($itemArea), "<enclosure");
				$iLastEnclosureStart = $iEnclosureStart;
				$fileSize = 0;
				while ($iEnclosureStart !== false)
				{
					$iEnclosureEnd = strpos(strtolower($itemArea), "/>", $iEnclosureStart);
					//See if this enclosure is for the media
					$enclosure = substr($itemArea, $iEnclosureStart, $iEnclosureEnd-$iEnclosureStart);
					$iMediaFound = 0;
					for ($i=0; $i < count($aSearchPattern); $i++)
					{
						
						//If 'vs-title: ', then search for the prior extracted media url
						if (substr($aExt[$i],0,9) == "vs-title:")
						{
							$searchPattern = "/.*" . _vPIPFeed_regexpEscape($mediaURL) . "/i";
							$iMediaFound = preg_match($searchPattern, $enclosure);
						}
						else 
						{
							$searchPattern = $aSearchPattern[$i];
							$iMediaFound = preg_match($searchPattern, $enclosure);
						}
						if ($iMediaFound > 0) break;
					}
					if ($iMediaFound > 0) 
					{
						$iLengthPos = strpos(strtolower($enclosure), "length=");
						if ($iLengthPos > 0)
						{
							$iLengthEnd = strpos(strtolower($enclosure), "\"", $iLengthPos+9);
							if ($iLengthEnd === false) 
								$iLengthEnd = strpos(strtolower($enclosure), "'", $iLengthPos+9);
							$fileSize = substr($enclosure, $iLengthPos+9, $iLengthEnd-($iLengthPos+9));
						}
					}
					
					$itemArea = substr($itemArea, 0, $iEnclosureStart-1) . substr($itemArea, $iEnclosureEnd+2);
					$iEnclosureStart =  strpos(strtolower($itemArea), "<enclosure");
					$iLastEnclosureStart = $iEnclosureStart;
				}
				$iItemEnd = strpos(strtolower($itemArea), "</item>");
				
				//If media URL found, add <enclosure ..> just for this media url
				if ($mediaURL !== false && $mediaURL != null)  
				{
					//Insert enclosure for specified media
					//Can be used to get the file size from the remote server
					/* Need to get the new URL, redirect and get the length */
					//if ($fileSize == 0)
					//{
					//	$fileSize = _vPIPFeed_remote_file_size($mediaURL);
					//}

					if ($fileSize === false)
						$fileSize = 0;
					$type = false;
					if (count($aType) > $iType)	
						$type = $aType[$iType];
					if ($type !== false && $type != null)
						$enclosure = "<enclosure url=\"" . $mediaURL . "\" length=\"" . $fileSize . "\" type=\"" . $type . "\" />";
					else
						$enclosure = "<enclosure url=\"" . $mediaURL . "\" length=\"" . $fileSize . "\" />";
					
					if ($iLastEnclosureStart > 0)
						$itemArea = substr($itemArea, 0, $iLastEnclosureStart-1) . $enclosure . substr($itemArea, $iLastEnclosureStart-1);
					else	
						$itemArea = substr($itemArea, 0, $iItemEnd) . $enclosure . "\n" . substr($itemArea, $iItemEnd);
						
					//Insert default anchor data at top of hvlog div	
					if ($anchorDefault !== false)
					{
						$itemArea = _vPIPFeed_InsertDefault($itemArea, $mediaURL, $anchorDefault);
						
					}
				} // if ($mediaURL !== false)
				$blogContent = substr($blogContent, 0, $iStartItem) . $itemArea . substr($blogContent, $iEndItem+7);
				$blogContentLower = strtolower($blogContent);
				$iEndItem = strpos($blogContentLower, "</item>", $iStartItem);
				
			} // if ($icontentEncodedStart !== false)
			// Remove any enclosure entries
			else 
			{
				//Remove enclosures rss writer put in
				$iEnclosureStart =  strpos(strtolower($itemArea), "<enclosure");
				while ($iEnclosureStart !== false)
				{
					$iEnclosureEnd = strpos(strtolower($itemArea), "/>", $iEnclosureStart);
					$itemArea = substr($itemArea, 0, $iEnclosureStart-1) . substr($itemArea, $iEnclosureEnd+2);
					$iEnclosureStart =  strpos(strtolower($itemArea), "<enclosure");
				}
				$blogContent = substr($blogContent, 0, $iStartItem) . $itemArea . substr($blogContent, $iEndItem+7);
				$blogContentLower = strtolower($blogContent);
				$iEndItem = strpos($blogContentLower, "</item>", $iStartItem);
				
			} // else - ($icontentEncodedStart)
			
			$iStartItem = strpos($blogContentLower, "<item>", $iEndItem+1);
		} // while ($iStartItem !== false)
		
		//$endTime = microtime(true);
		//$took = "<!-- took " . ($endTime - $startTimeTop) . " seconds from top, startTimeTop: " . $startTimeTop . ", endTime: " . $endTime . ".  Took " .  ($endTime - $startTimeAfterWP) . " seconds from after wordpress generated feed -->";
		//$blogContent .= $took;

		echo $blogContent;
		@ob_flush();
		flush();
		/*if ($FileHandleCache != -1)
		{
			fwrite($FileHandleCache, $blogContent);
			fclose($FileHandleCache);
		}*/
	}
	
	//--------------------------------- End ---------------------------------
	
	// Get the anchor default content
	function _vPIPFeed_GetAnchorDefault($content)
	{
		$anchorDefault = false;
		
		//If class hvlogtarget anchor entry, return it's content as the default
		$iDefaultStart = strpos($content, "hvlogtarget");
		if ($iDefaultStart !== false)
		{
			$iDefaultStart = strpos($content, ">", $iDefaultStart)+1;
			$iDefaultEnd = strpos($content, "</a", $iDefaultStart);
			if ($iDefaultEnd !== false)
			{
				$anchorDefault = substr($content, $iDefaultStart, $iDefaultEnd-$iDefaultStart);
			}
			
		}
		
		return $anchorDefault;
	}
	
	//Gets the anchor href media extension and position if they're there or 
	// return false
	function _vPIPFeed_GetExt($anchor)
	{
		$oReturn = array("ext"=>false, "iMediaPos"=>false);
		
		$iPosAHref = strpos(strtolower($anchor), "href");
		$char = "";
		for ($j = $iPosAHref; $j < strlen($anchor); $j++) 
		{
			$char = substr($anchor, $j, 1);
			if ($char == "'" || $char == "\"") 
			{
				break;
			} 
		}
		if ($char == "'" || $char == "\"") 
		{
			$iPosQuoteStart = strpos($anchor, $char);
			$iPosQuoteEnd = strpos($anchor, $char, $iPosQuoteStart+1);
			$anchorExtSearch = substr($anchor, $iPosQuoteStart, $iPosQuoteEnd-$iPosQuoteStart+1);
			$oReturn["iMediaPos"] = stringrpos($anchorExtSearch, ".")+$iPosQuoteStart+1;
			if ($oReturn["iMediaPos"] > 0)
				$oReturn["ext"] = substr($anchor, $oReturn["iMediaPos"], $iPosQuoteEnd-$oReturn["iMediaPos"]);
			else
				$oReturn["iMediaPos"] = false;
		}
		
		return $oReturn;
	
	}
	
	//** Strips out any non "<a href" entries from the hVlog class div **
	function _vPIPFeed_striphVlog($content)
	{
		$iStarthVlog = strpos(strtolower($content), "hvlog", 0);
		while  ($iStarthVlog !== false)
		{
			$iStarthVlog = strpos($content, ">", $iStarthVlog)+1;
			$iEndhVlog = _vPIPFeed_findNextEndDIV($content, $iStarthVlog);
			$sNewhVlog = "";
			//** Get all anchors ("<a ")
			$iStartAnchor = strpos($content, "<a ", $iStarthVlog);
			while ($iStartAnchor !== false && $iStartAnchor < $iEndhVlog)
			{
				$iEndAnchor = strpos(strtolower($content), "</a", $iStartAnchor);
				//If ending </a> missing, find the end of the tag (">")
				if ($iEndAnchor === false)
					$iEndAnchor = strpos(strtolower($content), ">", $iStartAnchor);
				if ($iEndAnchor === false)
					$iEndAnchor = $iStartAnchor+1;
				
				$anchor = substr($content, $iStartAnchor, ($iEndAnchor-$iStartAnchor+4));
				$iMediaStart = strpos(strtolower($anchor), "http://");
				if ($iMediaStart !== false)
				{
					if (strpos(strtolower($anchor), "<img "))
						$sNewhVlog .= " " . $anchor . "<br />";
					else
						$sNewhVlog .= " " . $anchor;
				}
				$iStartAnchor = strpos($content, "<a ", $iEndAnchor);
			}
			$contentNew = substr($content, 0, $iStarthVlog) . $sNewhVlog . 
					   substr($content, $iEndhVlog);
			$iEndhVlog -= strlen($content) - strlen($contentNew);
			$content = $contentNew;

			$iStarthVlog = strpos(strtolower($content), "hvlog", $iEndhVlog);
		}
		
		return $content;
	}
	
	function _vPIPFeed_findNextEndDIV($content, $iStart)
	{
		//Count DIV's to 0
		$iDIVs = 1;
		$iStartDIV = $iStart;
		while ($iDIVs > 0)
		{
			$iEndDIV = strpos(strtolower($content), "</div", $iStartDIV);
			$iStartDIV = strpos(strtolower($content), "<div", $iStartDIV);
			if ($iStartDIV !== false && $iStartDIV < $iEndDIV)
			{
				$iDIVs++;
				$iStartDIV = $iStartDIV+4;
			}
			else if ($iEndDIV !== false && ($iStartDIV === false || $iEndDIV < $iStartDIV))
			{
				$iDIVs--;
				$iStartDIV = $iEndDIV+5;
			}
			
			if ($iStartDIV === false && $iEndDIV === false)
				break;
		}
		
		return $iEndDIV;
		
	}
	
	function _vPIPFeed_removeGunk($content, $sStart, $sEnd)
	{
		$iOpenEnd = strpos(strtolower($content), $sEnd, 0);
		while  ($iOpenEnd !== false)
		{
			$iCloseEnd = strpos($content, ";", $iOpenEnd)+1;
			
			$sSearchStart = substr($content, 0, $iCloseEnd);
			$iOpenStart = strrpos(strtolower($sSearchStart), $sStart);
			if ($iOpenStart !== false)
			{
				$content = substr($content, 0, $iOpenStart + strlen($sStart)) .  
					   substr($content, $iCloseEnd);
			}
	
			$iOpenEnd = strpos(strtolower($content), $sEnd, $iCloseEnd);
		}
		
		return $content;
	}
	
	function _vPIPFeed_InsertDefault($content, $mediaURL, $anchorDefault)
	{
		$iStarthVlog = strpos(strtolower($content), "hvlog", 0);
		if  ($iStarthVlog !== false)
		{
			$iStarthVlog = strpos($content, ">", $iStarthVlog)+1;
			$content = substr($content, 0 , $iStarthVlog) . 
					   "<a href=\"" . $mediaURL . "\">" . $anchorDefault . "</a>" .
					   substr($content, $iStarthVlog);
		}
		
		return $content;
	}
	
	function _vPIPFeed_SetType($media) {
		$type = false;
		switch ($media) {
			case "mov":
				$type = "video/quicktime";
				break;
			case "mp4":
				$type = "video/mp4";
				break;
			case "m4v":
				$type = "video/x-m4v";
				break;
			case "mp3":
				$type = "video/mp3";
				break;
			case "mpg":
			case "mpeg":
				$type = "video/mpeg";
				break;
			case "3gp":
			case "3gpp":
				$type = "video/3gpp";
				break;
			case "avi":
				$type = "video/x-msvideo";
				break;
			case "wmv":
				$type = "video/x-ms-wmv";
				break;
			case "asf": 
				$type = "video/x-ms-asf";
				break;
			case "wma":
				$type = "audio/x-ms-wma";
				break;
			case "swf":
				$type = "application/x-shockwave-flash";
				break;
			case "flv":
				$type = "video/x-flv";
				break;
			case "ogg":
			case "ogv":
            case "oga":
				$type = "application/ogg";
				break;
		}
		return $type;
	}
	
	function _vPIPFeed_remote_file_size ($url)
	{ 
		$head = ""; 
		$url_p = parse_url($url); 
		$host = $url_p["host"]; 
		if(!preg_match("/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/",$host)){
			// a domain name was given, not an IP
			$ip=gethostbyname($host);
			if(!preg_match("/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/",$ip)){
				//domain could not be resolved
				return -1;
			}
		}
		$port = intval($url_p["port"]); 
		if(!$port) $port=80;
		$path = $url_p["path"]; 
		//echo "Getting " . $host . ":" . $port . $path . " ...";
	
		$fp = fsockopen($host, $port, $errno, $errstr, 20); 
		if(!$fp) { 
			return false; 
			} else { 
			fputs($fp, "HEAD "  . $url  . " HTTP/1.1\r\n"); 
			fputs($fp, "HOST: " . $host . "\r\n"); 
			fputs($fp, "User-Agent: http://www.example.com/my_application\r\n");
			fputs($fp, "Connection: close\r\n\r\n"); 
			$headers = ""; 
			while (!feof($fp)) { 
				$headers .= fgets ($fp, 128); 
				} 
			} 
		fclose ($fp); 
		//echo $errno .": " . $errstr . "<br />";
		$return = -2; 
		$arr_headers = explode("\n", $headers); 
		// echo "HTTP headers for <a href='" . $url . "'>..." . substr($url,strlen($url)-20). "</a>:";
		// echo "<div class='http_headers'>";
		foreach($arr_headers as $header) { 
			// if (trim($header)) echo trim($header) . "<br />";
			$s1 = "HTTP/1.1"; 
			$s2 = "Content-Length: "; 
			$s3 = "Location: "; 
			if(substr(strtolower ($header), 0, strlen($s1)) == strtolower($s1)) $status = substr($header, strlen($s1)); 
			if(substr(strtolower ($header), 0, strlen($s2)) == strtolower($s2)) $size   = substr($header, strlen($s2));  
			if(substr(strtolower ($header), 0, strlen($s3)) == strtolower($s3)) $newurl = substr($header, strlen($s3));  
			} 
		// echo "</div>";
		if(intval($size) > 0) {
			$return=intval($size);
		} else {
			$return=$status;
		}
		// echo intval($status) .": [" . $newurl . "]<br />";
		if (intval($status)==302 && strlen($newurl) > 0) {
			// 302 redirect: get HTTP HEAD of new URL
			$return=_vPIPFeed_remote_file_size($newurl);
		}
		return $return; 
	} 
	
	/**
	 FUNCTION: _vPIPFeed_ffl_HttpGet()
	 * Perform a HTTP Get Request.
	 *
	 * _vPIPFeed_ffl_HttpGet uses fsockopen() to request a given URL via HTTP
	 * 1.0 GET and returns a three element array.  On success, array
	 * key 'body' contains the body of the request's reply and key
	 * 'header' contains the reply's headers.  On error, the keys
	 * returned are 'errornumber' and 'errorstring' from
	 * fsockopen()'s third and fourth arguments.  In either case,
	 * key 'url' contains an array such as returned from parse_url()
	 * after the input url has been massaged a bit.
	 *
	 * {@source }
	 *
	 * @param string $url URL to fetch.
	 * @param boolean $followRedirects Optionally follow 
	 * 'location:' in header, default true.
	 * @return array 'header', 'body', 'url' OR 'errorstring',
	 * 'errornumber', 'url'.
	 */
	function _vPIPFeed_ffl_HttpGet( $url, $callBack, $sTermTag = "</item>", $followRedirects=true ) {
	    $sTermTag = strtolower($sTermTag);
	    
	    $url_parsed = parse_url($url);
	    if ( empty($url_parsed['scheme']) ) {
	        $url_parsed = parse_url('http://'.$url);
	    }
	    $rtn['url'] = $url_parsed;
	
	    $port = $url_parsed["port"];
	    if ( !$port ) {
	        $port = 80;
	    }
	    $rtn['url']['port'] = $port;
	    
	    $path = $url_parsed["path"];
	    if ( empty($path) ) {
	            $path="/";
	    }
	    if ( !empty($url_parsed["query"]) ) {
	        $path .= "?".$url_parsed["query"];
	    }
	    $rtn['url']['path'] = $path;
	
	    $host = $url_parsed["host"];
	    $foundBody = false;
	
	    $out = "GET $path HTTP/1.0\r\n";
	    $out .= "Host: $host\r\n";
	    $out .= "Connection: Close\r\n\r\n";
	
	    if ( !$fp = @fsockopen($host, $port, $errno, $errstr, 30) ) {
	        $rtn['errornumber'] = $errno;
	        $rtn['errorstring'] = $errstr;
	        return $rtn;
	    }
	    $header = "";
	    $body = "";
	    fwrite($fp, $out);
	    while (!feof($fp)) {
	        $s = fgets($fp);
	        if ( $s == "\r\n" ) {
	            $foundBody = true;
	            call_user_func($callBack, $header, "header");
	            continue;
	        }
	        
	        if ( $foundBody ) {
	            $body .= $s;
	            //If at the termination tag, send content to callback and clear.
	            if (strpos(strtolower($body), $sTermTag) !== FALSE)
	            {
	            	call_user_func($callBack, $body, "body");
	            	$body = "";
	            }
	           
	        } else {
	            if ( ($followRedirects) && (stristr($s, "location:") != false) ) {
	                $redirect = preg_replace("/location:/i", "", $s);
	                return _vPIPFeed_ffl_HttpGet( trim($redirect), $callBack, $sTermTag, $followRedirects );
	            }
	            $header .= $s;
	        }
	    }
	    fclose($fp);
	    if (strlen(trim($header)) > 0)
	    	call_user_func($callBack, $body, "body");
	
	    /* TBR:
		$rtn['header'] = trim($header);
	    $rtn['body'] = trim($body);
	    return $rtn;
	     * 
	     */
	}
	
	/**
	* Converts a windows wildcard pattern to a regex pattern
	*
	* @param wildcard - Wildcard pattern containing * and ?
	*
	* @return - a regex pattern that is equivalent to the windows wildcard pattern
	*/
	function _vPIPFeed_wildcardToRegex($wildcard)
	{
		if ($wildcard == NULL) return NULL;
		
		$buffer = "";
		
		for ($i = 0; $i < strlen($wildcard); ++$i) {
			$char = substr($wildcard, $i, 1);
		  if ($char == '*')
		    $buffer .= "." . $char;
		  else if ($char == '?')
		    $buffer .= ".";
		  else if (preg_match("/[+()^$.{}[]|\\]/", $char) > 0) 
		    $buffer .= "\\" . $char; // prefix all metacharacters with backslash
		  else
		    $buffer .= $char;
		}
	
	return strtolower("/" . $buffer . "/i");
	} 
	
	function _vPIPFeed_regexpEscape($expression)
	{
		$return ="";
		for ($i=0; $i<strlen($expression); $i++)
		{
			$char = substr($expression, $i, 1);
			if ($char == "/") 
			{
				$return .= "\/";
			}
			else 
			{
				$return .= $char;
			}
			
		}
		
		return $return;
	}
	
	function stringrpos($haystack,$needle,$offset=NULL)
	{
	    return strlen($haystack)
	           - strpos( strrev($haystack) , strrev($needle) , $offset)
	           - strlen($needle);
	}
	
?>
