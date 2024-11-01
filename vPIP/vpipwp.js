/*
 * vpipwp version 0.04 
 * Helper functions for vPIP wordpress plugin
 * 
 * New:
 *   - vPIPWP_SetDisplayOrder as a more reliable display order sorter.
 * 
 * by:  Enric Teller, http://cirne.com, http://vpip.org
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
 
 	function vPIPWP_SetDisplayOrder(sSelect, frmName, sDivID)
 	{
 		//Pull out three section of HTML string from the DIV (before table rows, table rows, after table rows)
 		var oDIV = document.getElementById(sDivID);
 		var sHTML = oDIV.innerHTML;
 		var nTRStart = sHTML.toLowerCase().indexOf("<tr");
 		var nTREnd = sHTML.toLowerCase().lastIndexOf("</tr>")+5;
 		var sBeforeTRs = sHTML.substring(0, nTRStart);
 		var sTRs = sHTML.substring(nTRStart, nTREnd);
 		var sAfterTRs = sHTML.substring(nTREnd);
 		
 		//Order rows into array 
 		var aTRs = new Array();
 		nTRStart = 0;
 		nTREnd = 0;
 		var sTR = "";
 		//Prior and new locations of selected row
 		var nRowPrior = 0;
 		var nRowNew = 0;
  		while (nTRStart > -1)
 		{
 			nTREnd = sTRs.toLowerCase().indexOf("</tr>", nTRStart)+5;
 			sTR = sTRs.substring(nTRStart, nTREnd);
 			if (sTR.toLowerCase().indexOf("_displayorder") > -1)
 			{
 				var j = sTR.indexOf("<select name=\"")+14;
 				var k = sTR.indexOf("\"", j);
 				var sSelectName =  sTR.substring(j,k);
 				aTRs.push({data: sTR, selectName: sSelectName});
 				//On selected row
 				if (sTR.indexOf(sSelect) > -1)
 				{
 					nRowPrior = aTRs.length;
 					nRowNew = document.forms[frmName].elements[sSelect].selectedIndex+1; 
 				}
 			}
 			else
 			{
 				sBeforeTRs += sTR;
 			}
 			nTRStart = sTRs.toLowerCase().indexOf("<tr", nTREnd);
 		}
 		
 		var aMoveTR = new Object();
 		aMoveTR.data = aTRs[nRowPrior-1].data;
 		aMoveTR.selectName = aTRs[nRowPrior-1].selectName;
		var iMove = nRowPrior > nRowNew?-1:1;
		var iStart = nRowPrior-1;
		var iEnd = nRowNew-1;

 		//Sort array by new order
		// Perform rotation sort
		var i = iStart;
		while (i != iEnd)
		{
			aTRs[i].data = aTRs[i+iMove].data;
			aTRs[i].selectName = aTRs[i+iMove].selectName;

			i += iMove;
		}
		aTRs[iEnd].data = aMoveTR.data;
		aTRs[iEnd].selectName = aMoveTR.selectName;
 		sTRs = "";
 		for (i=0; i<aTRs.length; i++)
 		{
 			sTRs += aTRs[i].data;
 		}
 		
  		//Put back into into HTML string and replace in DIV
 		oDIV.innerHTML = sBeforeTRs + sTRs + sAfterTRs;
 		
  		//Renumber order values 
 		for (i=0; i<aTRs.length; i++)
 		{
			var sSelectName = aTRs[i].selectName;
			document.forms[frmName].elements[sSelectName].selectedIndex = i;
  		}

 	}

			function setDisplayOrder(sSelect, frmName, tableID) 
			{
				
				var iSelectedOrder = document.forms[frmName].elements[sSelect].selectedIndex;
				aSelects = jQuery("#" + tableID).find("select");
				
				//Setup sort
				aSorted = sortOrderSelect(aSelects, sSelect);
				iPriorSelected = getMissingIndex(aSorted, aSelects.length);
				//If selecting down, decrement up, if selected up, increment down
				iMove = iSelectedOrder > iPriorSelected?-1:1;
				iStart = iSelectedOrder;
				iEnd = iPriorSelected;

				// Perform rotation sort
				i = iStart;
				while (i != iEnd)
				{
					//Leave selected choice
					//if (aSorted[i].name != sSelect)
					//{
						document.forms[frmName].elements[aSorted[i].name].selectedIndex += iMove;
					//}

					i += iMove;
				}
				sortvPIPMediaEntryTable(jQuery("#" + tableID).find("tr"), frmName, tableID);
			}

			function sortOrderSelect(aSelects, sSelect)
			{
				aSorted = Array();
				for (i=0; i<aSelects.length; i++)
				{
					//Bypass selected choice
					if (aSelects[i].name != sSelect)
					{
						oData = new Object();
						oData.selectedIndex = aSelects[i].selectedIndex;
						oData.name = aSelects[i].name;
						aSorted[aSelects[i].selectedIndex] = oData;
					}
				}
				
				return aSorted;
			}

			function getMissingIndex(aArray, iLength) 
			{
				for (i=0; i<iLength; i++)
				{
					if (typeof(aArray[i]) == "undefined")
						return i;
				}
				
			}

			function sortvPIPMediaEntryTable(aTRs, frmName, tableID) 
			{
				//Change this to recreate and replace the table object
				var oldTable = jQuery("#" + tableID + " >table");
				var newTable = oldTable.clone(false);
				for (i=0; i<oldTable[0].childNodes.length; i++)
				{
					if (oldTable[0].childNodes[i].nodeName == "TBODY")
						break;
					var tableElem = oldTable[0].childNodes[i].cloneNode(true);
					newTable[0].appendChild(tableElem);

				}

				// Create the TBODY.
				var newTBody = document.createElement("TBODY");
				//Add into TBODY up to and including id="tblMediaTitle"
				iOffset = 0;
				while (true)
				{
					newTBody.appendChild(aTRs[iOffset]);
					iOffset++;
					if (aTRs[iOffset-1].id == "tblMediaTitle")
						break;
				}

				aSortedTRs = getSortedTRs(aTRs, iOffset, tableID);

				// TBODY with TR nodes.
				for (i=0; i<aSortedTRs.length; i++)
				{
					newTBody.appendChild(aSortedTRs[i][0]);
				}
 				newTable[0].appendChild(newTBody);

				//Clear old childNodes
				while (oldTable[0].childNodes.length > 0)
				{
					oldTable[0].removeChild(oldTable[0].childNodes[0]);
				}

				// Add new childNodes
				for (i=0; i<newTable[0].childNodes.length; i++)
				{
					oldTable[0].appendChild(newTable[0].childNodes[i].cloneNode(true));
				}

				//Set <SELECT>...</SELECT> in order
				for (i=0; i<aSortedTRs.length; i++)
				{
					document.forms[frmName].elements[aSortedTRs[i][1].name].selectedIndex = aSortedTRs[i][1].selectedIndex;
				}
			}


			function getSortedTRs(aTRs, iOffset, tableID) 
			{
				var oSelects = jQuery("#" + tableID).find("select");

				var aSortedTRs = new Array();
				for (i=iOffset; i<oSelects.length+iOffset; i++)
				{
					var oSelect = oSelects[i-iOffset] //jQuery("#" + sID);
					var oData = new Object();
					oData.name = oSelect.name;
					oData.selectedIndex = oSelect.selectedIndex;

					aSortedTRs[oSelect.selectedIndex] = new Array(aTRs[i].cloneNode(true), oData);
 
				}
				return aSortedTRs;
			}

