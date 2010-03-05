/* Flickr Gallery script
* powered by jQuery (http://www.jquery.com)

* written by Richard Shepherd (http://richardshepherd.com)

* for more info visit
* http://www.richardshepherd.com/how-to-use-jquery-with-a-json-flickr-feed-to-display-photos/ */

function getFlickr(userid, target, numberToDisplay) {

var url = http://api.flickr.com/services/feeds/photos_public.gne?id=
+ userid + "&lang=en-us&format=json&jsoncallback=?";

$.getJSON(url, displayImages);

function displayImages(data) {
var htmlString = "";

var ctr = 0;
$.each(data.items, function(i, item) {

if (ctr < numberToDisplay) {
var sourceSquare =
                    (item.media.m).replace("_m.jpg", "_s.jpg");
var sourceOrig =
                    (item.media.m).replace("_m.jpg", ".jpg");

htmlString += '<a href="' + sourceOrig +
                    '" class="preview" title="' + item.title +
                    '" target="_blank" style="opacity: 1;">';
htmlString += '<img title="' + item.title +
                    '" src="' + sourceSquare + '" ';
htmlString += 'alt="' + item.title +
                    '" style="opacity: 1;" />';
htmlString += '</a>';
ctr = ctr + 1
}
});

$('#' + target).append(htmlString);

//update image preview so we get
//the nice popup mouseovers on the images
//Note: this uses the Image Preview Script,
//if not using it remove the below line.



imagePreview();
}
}