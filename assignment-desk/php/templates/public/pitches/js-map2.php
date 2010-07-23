<?php 
    global $assignment_desk;
    $gmaps_key = $assignment_desk->options['google_api_key'];
?>

<script src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=<?php echo $gmaps_key ?>" type="text/javascript"></script>


    <script type="text/javascript"> 
 
   	function initialize() {

      var map = new GMap2(document.getElementById("map_canvas"));
      map.setCenter(new GLatLng(40.732672 ,-73.995752), 15);
      map.setUIToDefault();

	  // Add 10 markers to the map at random locations
	  var bounds = map.getBounds();
	  var southWest = bounds.getSouthWest();
	  var northEast = bounds.getNorthEast();
	  var lngSpan = northEast.lng() - southWest.lng();
	  var latSpan = northEast.lat() - southWest.lat();
	  for (var i = 0; i < 10; i++) {
	    var point = new GLatLng(southWest.lat() + latSpan * Math.random(),
	        southWest.lng() + lngSpan * Math.random());
	    map.addOverlay(new GMarker(point));
	  }

    }
</script>

<div id="map_canvas" style="float: center; width:720px; height: 200px; border: solid 1px #ddd; margin: 3px; padding:10px; -moz-border-radius:8px"></div>


