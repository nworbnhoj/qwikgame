
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
				if(d>0){
//					alert('dragging left');
				} else {
//					alert('dragging right');
				}
				$(this).data('p0', p1);
			}
		}

	})
	.mouseup(function(e){
		dragging = false;
	}).mouseout(function(e){
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
        $(this).nextAll("input.venue").attr("list", "venue-"+$(this).val());
    });

	$("input.venue").ready(function(){
		$(this).attr("list", "venue-squash");
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

	$('#invite-familiars').click(function(){
		$(this).hide();
		$('#familiar-invites').show();	
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
			id += $('#venue-suburb').val() + '|';
			id += $('#venue-state').val() + '|';
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

	$('#tz').focus(function(){
		var tzSelect = $('#tz');
		var tzVal = tzSelect.val();
        jsonTZoptions($('select#venue-country').val(), tzSelect);
//alert("tzVal");
		tzSelect.val(tzVal).change();
	});



    $('select#venue-country').change(function(){
        jsonTZoptions($(this).val(), $('#tz'));
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

	$('.repost').change(function(){
		$(this).parent().submit();
	});

    $('.geocode').blur(function(){
		initMap();
    });

});


var MSqC = {lat: -36.4497857, lng: 146.43003739999995};

function initMap() {

    var lat =  document.getElementById('venue-lat').value;
    var lng =  document.getElementById('venue-lng').value;
	var latlngOK = isNumeric(lat) && isNumeric(lng);

	var resultsMap = new google.maps.Map(document.getElementById('map'), {
		zoom: 14,
		center: latlngOK ? {lat: Number(lat), lng: Number(lng)} : MSqC
	});

	var latInput = document.getElementById('venue-lat');
	var lngInput = document.getElementById('venue-lng');
	var marker;

	if (latlngOK){
		marker = new google.maps.Marker({
                map: resultsMap,
                position: {lat: Number(lat), lng: Number(lng)},
				draggable: true
        });
	} else {
		var geocoder = new google.maps.Geocoder();

		var point = {address: document.getElementById('venue-name').value
	        + ", " + document.getElementById('venue-address').value
	        + ", " + document.getElementById('venue-suburb').value
	        + ", " + document.getElementById('venue-state').value
	        + ", " + document.getElementById('venue-country').value };

		geocoder.geocode(point, function(results, status) {
			if (status === 'OK') {
				var site = results[0].geometry.location;
				resultsMap.setCenter(site);
				marker = new google.maps.Marker({
					map: resultsMap,
					position: site,
					draggable: true
				});

                latInput.value = site.lat();
                lngInput.value = site.lng();
			} else {
				alert('Geocode was not successful for the following reason: ' + status);
			}
	
		});
	}
	google.maps.event.addListener(marker, 'dragend', function(evt){
		latInput.value = evt.latLng.lat();
		lngInput.value = evt.latLng.lng();
	});
}


function venuesMap() {
    var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 7,
        center: MSqC
    });
	jsonVenueMarkers(document.getElementById('game').value, map);
}


// https://stackoverflow.com/questions/18082/validate-decimal-numbers-in-javascript-isnumeric
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}


// https://stackoverflow.com/questions/6921827/best-way-to-populate-a-select-box-with-timezones
function jsonTZoptions(country, selectElement){
	$.getJSON("json/timezone.php",{country_iso: country}, function(j){
		var selected = (j.length === 1) ? '' : 'selected';
    	var options = '<option disabled ' + selected + ' value>timezone</option>';
		selected = (j.length === 1) ? 'selected' : '';
		for (var i = 0; i < j.length; i++) {
			options += '<option ' + selected + ' value="' + j[i].optionValue + '">';
			options += j[i].optionDisplay + '</option>';
		}
		selectElement.html(options);
	})
}


function jsonVenueMarkers(game, map){
    $.getJSON("json/venue-map-data.php",{game: game}, function(j){
        for (var i = 0; i < j.length; i++) {
			var mark = j[i];
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
					+ "play <a href='player.php?venue="+svid+"&game="+game+"'>now</a> "
					+ "or <a href='player.php?venue="+svid+"&game="+game+"'>later</a>"
					+ "</div>";
				google.maps.event.addListener(
					marker,
					'click', 
					(	function(marker,content,infoWindow){ 
							return function() {
								infoWindow.setContent(content);
								infoWindow.open(map,marker);
							};
						}
					)(marker,content,infoWindow)
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


