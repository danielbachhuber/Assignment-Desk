<?php 
    global $assignment_desk;
    $gmaps_key = $assignment_desk->options['google_api_key'];
?>

<script src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=<?php echo $gmaps_key ?>" type="text/javascript"></script>
<script type="text/javascript"> 
//<![CDATA[

	var iconBlue = new GIcon(); 
    iconBlue.image = 'http://labs.google.com/ridefinder/images/mm_20_blue.png';
    iconBlue.shadow = 'http://labs.google.com/ridefinder/images/mm_20_shadow.png';
    iconBlue.iconSize = new GSize(12, 20);
    iconBlue.shadowSize = new GSize(22, 20);
    iconBlue.iconAnchor = new GPoint(6, 20);
    iconBlue.infoWindowAnchor = new GPoint(5, 1);

    var iconRed = new GIcon(); 
    iconRed.image = 'http://www.google.com/mapfiles/turkey.png';
    iconRed.iconSize = new GSize(40, 50);
    iconRed.shadowSize = new GSize(22, 20);
    iconRed.iconAnchor = new GPoint(6, 20);
    iconRed.infoWindowAnchor = new GPoint(3, 1);

    var customIcons = [];
    customIcons["entertainment"] = iconBlue;
    customIcons["sports"] = iconRed;

    function load() {
      if (GBrowserIsCompatible()) {
       	  var map = new GMap2(document.getElementById("map"));
	        map.addControl(new GSmallMapControl());
	        map.addControl(new GMapTypeControl());
	        map.setCenter(new GLatLng(40.732672 ,-73.995752), 13);
			//map.setUIToDefault();
			
		GDownloadUrl("xml.php", function(data, responseCode) {
		  // To ensure against HTTP errors that result in null or bad data,
		  // always check status code is equal to 200 before processing the data
		  if(responseCode == 200) {
		    var xml = GXml.parse(data);
		    var markers = xml.documentElement.getElementsByTagName("marker");
			for (var i = 0; i < markers.length; i++) {
			  var category = markers[i].getAttribute("category");
			  var headline = markers[i].getAttribute("headline");
		      var point = new GLatLng(parseFloat(markers[i].getAttribute("latitude")),
		                              parseFloat(markers[i].getAttribute("longitude")));
			  var marker = createMarker(point, category, headline);
		      map.addOverlay(marker);
		    }
		  } else if(responseCode == -1) {
		    alert("please try later.");
		  } else { 
		    alert("Request resulted in error. XML file is retrievable.");
		  }
		});

      }
    }

    function createMarker(point, category, headline) {
      var marker = new GMarker(point, customIcons[category]);
	  var html = "<b><a href = http://s20wpmu.com/qa target=_blank>" + headline + "</a></b>";
	  GEvent.addListener(marker, 'click', function() {
	        marker.openInfoWindowHtml(html);
	      });
	  return marker;
    }
    //]]>
</script>

<div id="map" style="float: left; width: 716px; height: 300px;"></div>