$("document").ready(function() {
	if ($.cookie('loc_longitude') && $.cookie('loc_latitude')) {
		getWeather();
	} else {
		$.getJSON("http://www.geoplugin.net/json.gp?callback=?", function(data) {
			eval(data);
		});
	}
});

function geoPlugin(data) {
	$.cookie('loc_latitude', data.geoplugin_latitude, {expires: 7});	
	$.cookie('loc_longitude', data.geoplugin_longitude, {expires: 7});
	$.cookie('loc_country', data.geoplugin_countryName, {expires: 7});
	$.cookie('loc_region', data.geoplugin_region, {expires: 7});
	$.cookie('loc_city', data.geoplugin_city, {expires: 7});
	$.cookie('loc_country_code', data.geoplugin_countryCode, {expires: 7});
	getWeather();
}

function getWeather() {
	var latitude = $.cookie('loc_latitude');
	var longitude = $.cookie('loc_longitude');
	
	var loc_conditions = $.cookie('loc_conditions');
	var loc_conditions_img = $.cookie('loc_conditions_img');
	var loc_temp = $.cookie('loc_temp');
	var loc_humidity = $.cookie('loc_humidity');
	
	if (loc_conditions && loc_conditions_img) {
		setConditions(loc_conditions, loc_conditions_img, loc_temp, loc_humidity);
	} else {
		var url = "http://ws.geonames.org/findNearByWeatherJSON?lat=" + latitude + "&lng=" + longitude + "&callback=?";
		$.getJSON(url, function(data) {
			var clouds = data.weatherObservation.clouds;
			var weather = data.weatherObservation.weatherCondition;
			var temp = data.weatherObservation.temperature;
			var humidity = data.weatherObservation.humidity;
			
			var conditions_img = getConditions(clouds, weather);
			
			var conditions = '';
			if (weather == 'n/a') {
				if (clouds == 'n/a') {
					conditions = 'fine';
				} else {
					conditions = clouds;
				}
			} else {
				conditions = weather;
			}
			
			$.cookie('loc_conditions', conditions);	
			$.cookie('loc_conditions_img', conditions_img);	
			$.cookie('loc_temp', temp);	
			$.cookie('loc_humidity', humidity);	
			setConditions(conditions, conditions_img, temp, humidity);
		});
	}
}

function getConditions(clouds, weather) {
	if (weather == 'n/a') {
		switch (clouds) {
			case 'n/a':
				return 'sunny.gif';
			case 'clear sky':
				return 'sunny.gif';
			case 'few clouds':
				return 'partly_cloudy.gif';
			case 'scattered clouds':
				return 'partly_cloudy.gif';
			case 'broken clouds':
				return 'partly_cloudy.gif';
			default:
				return 'cloudy.gif';
		}
	} else {
		weather = weather.replace('light ', '').replace('heavy ', '').replace(' in vicinity', '');
		switch(weather) {
			case 'drizzle':
				return 'rain.gif';
			case 'rain':
				return 'rain.gif';
			case 'snow':
				return 'snow.gif';
			case 'snow grains':
				return 'sleet.gif';
			case 'ice crystals':
				return 'icy.gif';
			case 'ice pellets':
				return 'icy.gif';
			case 'hail':
				return 'sleet.gif';
			case 'small hail':
				return 'sleet.gif';
			case 'snow pellets':
				return 'sleet.gif';
			case 'unknown precipitation':
				return 'rain.gif';
			case 'mist':
				return 'mist.gif';
			case 'fog':
				return 'fog.gif';
			case 'smoke':
				return 'smoke.gif';
			case 'volcanic ash':
				return 'smoke.gif';
			case 'sand':
				return 'dust.gif';
			case 'haze':
				return 'haze.gif';
			case 'spray':
				return 'rain.gif';
			case 'widespread dust':
				return 'dust.gif';
			case 'squall':
				return 'flurries.gif';
			case 'sandstorm':
				return 'dust.gif';
			case 'duststorm':
				return 'dust.gif';
			case 'well developed dust':
				return 'dust.gif';
			case 'sand whirls':
				return 'dust.gif';
			case 'funnel cloud':
				return 'flurries.gif';
			case 'tornado':
				return 'storm.gif';
			case 'waterspout':
				return 'storm.gif';
			case 'showers':
				return 'storm.gif';
			case 'thunderstorm':
				return 'thunderstorm.gif';
			default:
				if (weather.indexOf("rain")) {
					return 'rain.gif';
				} else if (weather.indexOf("snow")) {
					return 'snow.gif';
				} else if (weather.indexOf("thunder")) {
					return 'thunderstorm.gif';
				} else if (weather.indexOf("dust")) {
					return 'dust.gif';
				} else {
					return 'sunny.gif';
				}
		}
	}
}

function setConditions(conditions, conditions_img, temp, humidity) {
	var country = $.cookie('loc_country');
	var region = $.cookie('loc_region');
	var city = $.cookie('loc_city');
	var loc_country_code = $.cookie('loc_country_code');
	if (loc_country_code == 'US') {
		temp = parseInt(temp) + 32;
		temp_type = "F";
	} else {
		temp_type = "C";
	}

	$("#weather_widget").append("<img id='weather_img' src='http://www.google.com/images/weather/" + conditions_img + "' />");
	$("#weather_widget").append("<div id='weather_conditions'><p id='weather_country'>" + country + "</p><p id='weather_city'>" + city + ", " + region + "</p><p id='weather_temp'>Temp: " + temp + "&deg; " + temp_type + "</p><p id='weather_hum'>Humidity: " + humidity + "%</p><p id='weather_cond'>" + conditions.substr(0, 1).toUpperCase() + conditions.substr(1) + "</p></div>");
}