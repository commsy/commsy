function initNavi(){	
	var gesamtbreite = 855;
	var margin = 5;
	var padding = 0;
	var gesamtbreiteMenuepunkte = 0;
	
	var navUlElement = document.getElementById("mainnavigationUl");
	var anzahlChildNodes = document.getElementById("mainnavigationUl").childNodes.length;
	var menuePunkte = new Array();
	
	
	for (var i=0; i<anzahlChildNodes; i++){
		var childNode = navUlElement.childNodes[i];
		if (childNode.nodeName == "LI")
				menuePunkte.push(childNode);
	}
	
 
  // Gesamtbreite aller Menüpunikte
  for (var i=0; i<menuePunkte.length; i++){
  	gesamtbreiteMenuepunkte = gesamtbreiteMenuepunkte + menuePunkte[i].offsetWidth;
  	if(navigator.appName == "Netscape" || navigator.appName == "Opera")
  		gesamtbreiteMenuepunkte = gesamtbreiteMenuepunkte+1;
  }
	
	
		
	// Benoetigtes Padding
	padding = parseInt((gesamtbreite - (gesamtbreiteMenuepunkte + menuePunkte.length*margin*2))/(menuePunkte.length*2));
	paddingRest = (gesamtbreite - (gesamtbreiteMenuepunkte + menuePunkte.length*margin*2))%(menuePunkte.length*2)
	
	
	// Padding setzen
	for (var i=0; i<menuePunkte.length; i++){
			var paddingLeft = padding;
			var paddingRight = padding;
			
			if(paddingRest>0){
				paddingLeft++;
				paddingRest --;
			}
			
			if(paddingRest>0){
				paddingRight++;
				paddingRest --;
			}
				
			// document.all.menuePunkte[i].style.backgroundColor = "yellow";	
			menuePunkte[i].style.padding="0px "+paddingRight+"px 5px "+paddingLeft+"px";
	}
}

/// Tab-Navigation auf Home-Seite
function showtab(arg) {
	for (i=1;i<=3;i++) {
		if (i == arg) {
			show = 'block';
			color = '#000000';
			fontWeight = 'bold';
		} else {
			show = 'none';
			color = '#4179a1';
			fontWeight = 'normal';
		}
		document.getElementById('tabbox'+i).style.display = show;
		document.getElementById('tablink'+i).style.color = color;
		document.getElementById('tablink'+i).style.fontWeight = fontWeight;
	}
}