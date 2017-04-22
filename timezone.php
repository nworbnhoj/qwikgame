<?php
// https://stackoverflow.com/questions/6921827/best-way-to-populate-a-select-box-with-timezones

	$country_iso = $_GET['country_iso'];
	$citylist = '';
	if(isset($country_iso))
	{
		$tz = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_iso); 
		//php 5.3 needed to use DateTimeZone::PER_COUNTRY !
	
		foreach($tz as $city)   
		    $citylist .= "{\"optionValue\": \"$city\", \"optionDisplay\": \"$city\"}, ";   
	}
	
	$citylist = preg_replace('/, $/im', '', $citylist);
	$citylist = "[".$citylist."]";
		
	echo $citylist;
?>
