docReady(event => {
    initPage();
});

winReady(event => {});


function addMoreListeners(){}


function initPage(){}



function venuesMap() {
    var mapElement = document.getElementById('map');
    var game = document.getElementById('game').value;
    var map = new google.maps.Map(mapElement, {zoom: 7, center: MSqC});
    jsonVenueMarkers(game, map);
}




function jsonVenueMarkers(game, map){
    $.getJSON("json/venue-map-data.php",{game: game}, function(json){
        for (var i = 0; i < json.length; i++) {
            var mark = json[i];
            var lat = mark.lat;
            var lng = mark.lng;
            var vid = mark.vid;
            var svid = mark.svid;
            var game = mark.game;
            var name = mark.name;
            if (isNumeric(lat) && isNumeric(lng)){
                var marker = new google.maps.Marker({
                    position: {lat: Number(lat), lng: Number(lng)},
                    map: map,
                    label: mark.playerCount 
                });
                var infoWindow = new google.maps.InfoWindow();
                var content = "<div class='infowindow'>"  
                    + "<b><a href='venue.php?vid="+vid+"'>"+name+"</a></b><br>"
                    + "<a href='match.php?venue="+svid+"&game="+game+"#keen-form'>match</a> "
                    + ": <a href='favorite.php?venue="+svid+"&game="+game+"#favorite-form'>favorite</a>"
                    + "</div>";
                google.maps.event.addListener(
                    marker,
                    'click', 
                    (function(marker,content,infoWindow){ 
                        return function() {
                            infoWindow.setContent(content);
                            infoWindow.open(map,marker);
                        };
                    })(marker,content,infoWindow)
                ); 
            }
        }
    })
}



