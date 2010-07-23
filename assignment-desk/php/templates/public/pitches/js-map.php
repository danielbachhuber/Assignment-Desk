<?php 
    global $assignment_desk;
    $gmaps_key = $assignment_desk->options['google_api_key'];
?>

<script src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=<?php echo $gmaps_key ?>" type="text/javascript"></script>

<div id="map" style="width: 450px; height: 220px; align: right"></div>
    <script type="text/javascript"> 
 
    var map = new GMap(document.getElementById("map"));
    map.setCenter(new GLatLng(40.732672 ,-73.995752), 14);
	var mapControl = new GMapTypeControl();
	map.addControl(mapControl);
	map.addControl(new GLargeMapControl());
	map.setUIToDefault();
 
    GEvent.addListener(map, 'click', function(overlay, point) {
        if (overlay) {
			map.removeOverlay(overlay);
      } else if (point) {
            map.recenterOrPanToLatLng(point);
			map.clearOverlays();
            var marker = new GMarker(point, {draggable:true});
            map.addOverlay(marker);
     }
 
     });
 
// Recenter Map and add Coords by clicking the map
GEvent.addListener(map, 'click', function(overlay, point) {
            document.getElementById("latbox").value=point.y;
            document.getElementById("lonbox").value=point.x;
});

    </script>



