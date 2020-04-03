docReady(event => {
    initPage();
});

winReady(event => {
    venuesMap();
});


function addMoreListeners(){}


function initPage(){}



function venuesMap() {
    var game = document.getElementById('game').value;
    var params = {game: game};
    var esc = encodeURIComponent;
    var query = Object.keys(params).map(k => esc(k) + '=' + esc(params[k])).join('&');
    var url = 'json/venue-map-data.php?'+query;

    var mapElement = document.getElementById('map');
    var map = new google.maps.Map(mapElement, {zoom: 7, center: MSqC});

    mapMarkers(map, qwikJSON(url));
}




function mapMarkers(map, json){
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
}



