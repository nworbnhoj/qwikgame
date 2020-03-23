// https://stackoverflow.com/questions/6348494/addeventlistener-vs-onclick
function ready(callbackFunction){
  if(document.readyState != 'loading')
    callbackFunction(event)
  else
    document.addEventListener("DOMContentLoaded", callbackFunction)
}


// https://stackoverflow.com/questions/6348494/addeventlistener-vs-onclick
function addEvent(element, evnt, funct){
  if (element.attachEvent)
   return element.attachEvent('on'+evnt, funct);
  else
   return element.addEventListener(evnt, funct, false);
}


// Registering Service Worker
if('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js');
    });
};


$(document).ready(function(){

    var currentTime = new Date();
    var hour = currentTime.getHours();

    $('#hrs_trunc').children('td').each(function(){
        var hr = $(this).attr('hr');
        if(hr <= hour){
            $(this).addClass('past').removeClass('toggle').unbind().css('color','DimGrey');
        }
    });


    $('#later').click(function(){
        $('table.time td').each(function(){
            alert('jjj');
        });
    });


    $('span.anon').each(function(){
        $(this).css('color',getRandomColor());
    });


    var dragging = false;
    $('table.time')
        .mousedown(function(e){
            dragging = true;
            $(this).data('p0', { x: e.pageX, y: e.pageY });
        })
        .mousemove(function(e){
            if (dragging){
            var p0 = $(this).data('p0');
                var p1 = { x: e.pageX, y: e.pageY };
//        d = Math.sqrt(Math.pow(p1.x - p0.x, 2) + Math.pow(p1.y - p0.y, 2));
                var d = p1.x - p0.x;
                if(Math.abs(d)>10){
                    $('hrs_trunc').children('td').each(function(){
                        if($(this).prop('hidden')){
                            alert($(this).attr('hr'));
                        }                    
                    });
                    if(d>0){    // alert('dragging left');
                    } else {    // alert('dragging right');
                    }
                    $(this).data('p0', p1);
                }
            }
        })
        .mouseup(function(e){
            dragging = false;
        })
        .mouseout(function(e){
            dragging = false;
        });
    

    $(".thumb").click(function(){
        var thumb = $(this);
        if(thumb.hasClass('fa-thumbs-o-up')){
            thumb.removeClass('fa-thumbs-o-up');
            thumb.addClass('fa-thumbs-o-down');
            thumb.removeClass('green');
            thumb.addClass('red');
        } else {
            thumb.removeClass('fa-thumbs-o-down');
            thumb.addClass('fa-thumbs-o-up');
            thumb.removeClass('red');
            thumb.addClass('green');
        }
    });


    $("#rep-thumb").click(function(){
        var thumb = $(this);
        var rep = $('#rep');
        if(thumb.hasClass('fa-thumbs-o-up')){
            rep.val('+1');
        } else {
            rep.val('-1');
        }
    });


    $("select.game").change( function(){
        var game = $(this).val();
        var form = $(this).parents('form:first');
        var json = form.find(".json");
        json.each(function(){
            var target = $(this);
            switch(target.prop('nodeName')){
                case 'INPUT':
                    var id = target.attr('list');
                    if (!id){ return false; }
                    var datalist = $(":root").find("datalist#"+id);
                    jsonOptions(datalist, id, game);
                break;
                case 'SELECT':
                    var id = target.attr('id');
                    if (!id){ return false; }
                    jsonOptions(target, id, game);
                break;
            }
        });
    });


    $("button.help").click(function(){
        $(this).nextAll('span.help').toggle();
    });


    $("button.cross").click(function(){
        $(this).parent().find('select').removeAttr('required');
    });


    $(".email-alert").click(function(){
        alert("You should receive a confirmation email shortly.");
    });


    $('td.toggle').click(function(){
        var td = $(this);
        var input = $('input:first-child', td.parents('tr'));
        var val = parseInt(input.val());
        var bit = parseInt(td.attr('bit'));
        if (td.attr('on') == 1){
            td.css('background-color', 'LightGrey');
            td.attr('on', '0');
            input.val(val - bit);
        } else {
            td.css('background-color', 'DarkOrange');
            td.attr('on', '1');
            input.val(val + bit);
        }
    });


    $('button.detail').click(function(){
        var value = $(this).prev('input').val();
        var url = "venue.php?description=" + value;
        window.location = url;
    });


    $('#invite-friends').click(function(){
        $(this).hide();
        $('#friend-invites').show();    
    });


    $('.show-edit-venue').click(function(){
        $('#display-venue-div').hide();
        $('#similar-venue-div').hide();
        $('#edit-venue-div').show();
    });


    $('#venue-submit').click(function(){
        if ($('#venue-id').value == null){
            var id = $('#venue-name').val() + '|';
            id += $('#venue-address').val() + '|';
            id += $('#venue-country').val();
            $('#venue-id').val(id);
        }
    });


    $('#venue-cancel').click(function(){
        $('#similar-venue-div').show();
        $('#edit-venue-div').hide();
        $('#display-venue-div').show();
    });


    $('.revert').click(function(){
        var id = $(this).attr('id');
        var val = $(this).attr('val');
        $(id).val(val);
        $('#edit-venue-form').show();
    });


    $('.back').click(function(){
        parent.history.back();
        return false;
    });


    $('.phrase').click(function(){
        $(this).nextAll('.edit-phrase').toggle();
    });

    
    $('.pending').click(function(){
        $(this).nextAll('.edit-phrase').toggle();
    });


    $('#hr-any').on('click', function(){
        if ($(this).is(':checked')){
            $('#hr').hide();
        } else {
            $('#hr').show();
        }
    });


    $('.hr-toggle').click(function(){
        $('#hr-check').toggle();
        $('#hr-grid').toggle();
    });


    $('#lang-icon').click(function(){
        $(this).toggle();
        $('#lang-select').toggle().select();
    });


    $('#lang-select').change(function(){
        $('#lang-form').submit();
    });


    $('#login-toggle').click(function(){
        $('#register-form').toggle();
        $('#login-form').toggle().select();
    });


    $('.repost').change(function(){
        $(this).parent().submit();
    });





    $('.geocode').blur(function(){
        initMap();
    });


    $('input.guess').keydown(function(){
        var name     = $('input#venue-name').val();
        var locality = $('input#venue-locality').val();
        var admin1   = $('input#venue-admin1').val();
        var country  = $('input#venue-country').val();
        var div      = $('div#venue-guess');
        guessPlace(name, locality, admin1, country, div);
    });


    $('input#venue-country').keydown(function(){
        this.value = this.value.toLocaleUpperCase('en-US');
    });


    $('div.base').each(function(){
        var parentNode = $(this).parent();
        var base = this.cloneNode(true);
        var id = base.getAttribute('id');
        var baseHtml = base.outerHTML;
        var url = 'json/'+id+'.listing.php';
        $.getJSON(url, {html: baseHtml}, function(json, stat){
            json = !json ? '' : json ;
            var length = nFormatter(json.length, 1) ;
            console.log("json reply from "+url+" ("+length+")");
            parentNode.html(json);
            parentNode.find("button.help").click(function(){
                $(this).nextAll('span.help').toggle();
            });
        }).fail(function(jqxhr, textStatus, error){
            var err = url+" : "+textStatus + ", " + error;
            console.log(err);
        });
    });


    $('select.json').each(function(){
        var select = $(this);
        var id = select.attr('id');
        if (!id){ return false; }
        var url = 'json/'+id+'.options.php';
        $.getJSON(url, {}, function(json, stat){
            json = !json ? '' : json ;
            var length = nFormatter(json.length, 1) ;
            console.log("json reply from "+url+" ("+length+")");
            select.html(json);
        }).fail(function(jqxhr, textStatus, error){
            var err = url+" : "+textStatus + ", " + error;
            console.log(err);
        });
    });


    $('datalist').each(function(){
        var datalist = $(this);
        var id = datalist.attr('id');
        if (!id){ return false; }
        var input = $(":root").find("[list='"+id+"']");
        var form = input.parents('form:first');
        var game = form.find("select.game option:selected").val();
        jsonOptions(datalist, id, game);
    });


});


function jsonOptions(parent, id, game){
    var url = "json/"+id+".options.php"+"?game="+game;
    console.log("json call to "+url);
    $.getJSON(url, {}, function(json, stat){
        json = !json ? '' : json;
        var length = nFormatter(json.length, 1);
        console.log("json reply from "+url+" ("+length+")");
        parent.html(json);
    }).fail(function(jqxhr, textStatus, error){
        var err = url+" : "+textStatus + ", " + error;
        console.log(err);
    });
}


function guessPlace(name, locality, admin1, country, div){
    var url = "json/address-autocomplete.php";
    var input = name+', '+locality+', '+admin1+', '+country;
    $.getJSON(url, {input: input}, function(json){
        div.empty();
        div.append($("<hr>"));
        if (json.status == 'OK'){
            var predictions = json.predictions;
            for (i = 0; i < predictions.length; i++) {
                var prediction = predictions[i];
                div.append($('<button/>')
                    .text(prediction.description)
                    .attr('type', 'submit')
                    .attr('class', 'venue guess')
                    .attr('name', 'placeid')
                    .attr('value', prediction.place_id)
                    .click(function(){
                        $('input.guess').removeAttr('required');
                    })
                );
            }
            div.append($("<br><img src='img/powered-by-google.png'>"));
        }
    });
}




var MSqC = {lat: -36.4497857, lng: 146.43003739999995};

function initMap() {
    var mapElement = document.getElementById('map');
    var latInput = document.getElementById('venue-lat');
    var lngInput = document.getElementById('venue-lng');
    if (!mapElement){return;}
    if (!latInput){return;}
    if (!lngInput){return;}

    var site = MSqC;   // default value
    var lat = latInput.value;
    var lng = lngInput.value;

    if (isNumeric(lat) && isNumeric(lng)){
        site = {lat: Number(lat), lng: Number(lng)};
    } else {
        var geocoder = new google.maps.Geocoder();
        var name    = document.getElementById('venue-name').value;
        var address = document.getElementById('venue-address').value;
        var country = document.getElementById('venue-country').value;
        var point = {address: name+", "+address+", "+country };

        geocoder.geocode(point, function(results, status) {
            if (status === 'OK') {
                site = results[0].geometry.location;
                latInput.value = site.lat();
                lngInput.value = site.lng();
            } else {
                alert('Geocoding failed: ' + status);
            }
        });
    }

    var map = new google.maps.Map(mapElement, {zoom:14, center:site});
    var marker = new google.maps.Marker({map:map, position:site, draggable:true});
    google.maps.event.addListener(marker, 'dragend', function(evt){
        latInput.value = evt.latLng.lat();
        lngInput.value = evt.latLng.lng();
    });
}


function venuesMap() {
    var mapElement = document.getElementById('map');
    var game = document.getElementById('game').value;
    var map = new google.maps.Map(mapElement, {zoom: 7, center: MSqC});
    jsonVenueMarkers(game, map);
}


// https://stackoverflow.com/questions/18082/validate-decimal-numbers-in-javascript-isnumeric
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}


// https://stackoverflow.com/questions/6921827/best-way-to-populate-a-select-box-with-timezones
function jsonTZoptions(country, selectElement){
    $.getJSON("json/timezone.php",{country_iso: country}, function(json){
        var selected = (json.length === 1) ? '' : 'selected';
        var options = '<option disabled '+selected+' value>timezone</option>';
        selected = (json.length === 1) ? 'selected' : '';
        for (var i = 0; i < json.length; i++) {
            var value = json[i].optionValue;
            var display = json[i].optionDisplay;
            options += '<option '+selected+' value="'+value+'">'+display+'</option>';
        }
        selectElement.html(options);
    })
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




// https://stackoverflow.com/questions/13/determine-a-users-timezone/5492192#5492192

function TimezoneDetect(){
    var dtDate = new Date('1/1/' + (new Date()).getUTCFullYear());
    var intOffset = 10000; //set initial offset high so it is adjusted on the first attempt
    var intMonth;
    var intHoursUtc;
    var intHours;
    var intDaysMultiplyBy;

    //go through each month to find the lowest offset to account for DST
    for (intMonth=0;intMonth < 12;intMonth++){
        //go to the next month
        dtDate.setUTCMonth(dtDate.getUTCMonth() + 1);

        //To ignore daylight saving time look for the lowest offset.
        //Since, during DST, the clock moves forward, it'll be a bigger number.
        if (intOffset > (dtDate.getTimezoneOffset() * (-1))){
            intOffset = (dtDate.getTimezoneOffset() * (-1));
        }
    }

    return intOffset;
}


// https://stackoverflow.com/questions/1484506/random-color-generator-in-javascript
function getRandomColor() {
    var letters = '789ABCD';
    var color = '#';
    for (var i = 0; i < 6; i++ ) {
        color += letters[Math.floor(Math.random() * 6)];
    }
    return color;
}


// https://stackoverflow.com/questions/26361649/how-to-handle-right-to-left-text-input-fields-the-right-way?noredirect=1&lq=1
function rtl(element){   
    if(element.setSelectionRange){
        element.setSelectionRange(0,0);
    }
}


// https://stackoverflow.com/questions/9461621/format-a-number-as-2-5k-if-a-thousand-or-more-otherwise-900#9462382
function nFormatter(num, digits) {
  var si = [
    { value: 1, symbol: "" },
    { value: 1E3, symbol: "k" },
    { value: 1E6, symbol: "M" },
    { value: 1E9, symbol: "G" },
    { value: 1E12, symbol: "T" },
    { value: 1E15, symbol: "P" },
    { value: 1E18, symbol: "E" }
  ];
  var rx = /\.0+$|(\.[0-9]*[1-9])0+$/;
  var i;
  for (i = si.length - 1; i > 0; i--) {
    if (num >= si[i].value) {
      break;
    }
  }
  return (num / si[i].value).toFixed(digits).replace(rx, "$1") + si[i].symbol;
}



