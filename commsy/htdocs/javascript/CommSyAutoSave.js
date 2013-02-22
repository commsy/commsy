function stopclock() {
  if(timerRunning)
    clearTimeout(timerID);
    timerRunning = false;
}

function startclock() {
  //alert(breakCrit);
  startDate = new Date();
  startSecs = (startDate.getHours()*60*60) + (startDate.getMinutes()*60) + startDate.getSeconds();
  startSecs += sessLimit;
  stopclock();
  showtime();
}

function showtime() {
  var now = new Date();
  var nowSecs = (now.getHours()*60*60) + (now.getMinutes()*60) + now.getSeconds();
  var elapsedSecs = startSecs - nowSecs;
  var hours = Math.floor( elapsedSecs / 3600 );
  elapsedSecs = elapsedSecs - (hours*3600);
  var minutes =   Math.floor( elapsedSecs / 60 );
  elapsedSecs = elapsedSecs - (minutes*60);
  var seconds = elapsedSecs;
  var timeValue = "";
  timeValue  += ((hours < 10) ? "0" : ":") + hours;
  timeValue  += ((minutes < 10) ? ":0" : ":") + minutes;
  timeValue  += ((seconds < 10) ? ":0" : ":") + seconds;

  if (dispMode == 2) {
    //document.f.timerField.value = timeValue;
    jQuery('[name=timerField]').val(timeValue);
  }

  timerID = setTimeout("showtime()",1000);
  timerRunning = true;
  if (hours == 0 && minutes == 0 && seconds == 0){
    stopclock();
    fieldfound = false;

    // ### change the following to whatever...                    ###
    // ### the following examples hit 'material' and 'discussion' ###

    //if (document.f.title) {
    if (jQuery('[name=title]')) {
      //if (document.f.title.value != "") {
      if (jQuery('[name=title]').val() != "") {
        fieldfound = true;
        //document.f.title.value = "tmp_" + document.f.title.value;
        jQuery('[name=title]').val("tmp_" + jQuery('[name=title]').val());
      } else {
        //document.f.title.value = "tmp_???";
        jQuery('[name=title]').val("tmp_???");
      }
    }
    //if (document.f.subject) {
    if (jQuery('[name=subject]')) {
      //if (document.f.subject.value != "") {
      if (jQuery('[name=subject]') != "") {
        fieldfound = true;
        //document.f.subject.value = "tmp_" + document.f.subject.value;
        jQuery('[name=subject]').val("tmp_" + jQuery('[name=subject]').val());
      } else {
        //document.f.subject.value = "tmp_???";
        jQuery('[name=subject]').val("tmp_???");
      }
    }
    //if (document.f.description) {
    if (jQuery('[name=description]')) {
      //if (document.f.description.value != "") {
      if (jQuery('[name=description]').val() != "") {
        fieldfound = true;
        //document.f.description.value = "tmp_" + document.f.description.value;
        jQuery('[name=description]').val("tmp_" + jQuery('[name=description]').val());
      } else {
        //document.f.description.value = "tmp_???";
        jQuery('[name=description]').val("tmp_???");
      }
    }
    //if (document.f.dayStart) {
    if (jQuery('[name=dayStart]')) {
      //if (document.f.dayStart.value != "") {
      if (jQuery('[name=dayStart]').val() != "") {
        // fieldfound = true;
      } else {
        d = new Date ();
        cday = (d.getDate() < 10 ? '0' + d.getDate() : d.getDate());
        cmonth = ((d.getMonth() + 1) < 10 ? '0' + (d.getMonth() + 1) : (d.getMonth() + 1));
        cyear = d.getFullYear();
        chour = (d.getHours () < 10 ? '0' + d.getHours () : d.getHours ());
        cmin = (d.getMinutes () < 10 ? '0' + d.getMinutes () : d.getMinutes ());
        // cs = (d.getSeconds () < 10 ? '0' + d.getSeconds () : d.getSeconds ());
        //document.f.dayStart.value = cday + '.' + cmonth + '.' + cyear;
        jQuery('[name=dayStart]').val(cday + '.' + cmonth + '.' + cyear);
        //document.f.timeStart.value = chour + ':' + cmin;
        jQuery('[name=timeStart]').val(chour + ':' + cmin);
      }
    }
    //if (document.f.dayEnd) {
    if (jQuery('[name=dayEnd]')) {
      //if (document.f.dayEnd.value != "") {
      if (jQuery('[name=dayEnd]').val() != "") {
        // fieldfound = true;
      } else {
        d = new Date ();
        cday = (d.getDate() < 10 ? '0' + d.getDate() : d.getDate());
        cmonth = ((d.getMonth() + 1) < 10 ? '0' + (d.getMonth() + 1) : (d.getMonth() + 1));
        cyear = d.getFullYear();
        chour = (d.getHours () < 10 ? '0' + d.getHours () : d.getHours ());
        cmin = (d.getMinutes () < 10 ? '0' + d.getMinutes () : d.getMinutes ());
        // cs = (d.getSeconds () < 10 ? '0' + d.getSeconds () : d.getSeconds ());
        //document.f.dayEnd.value = cday + '.' + cmonth + '.' + cyear;
        jQuery('[name=dayEnd]').val(cday + '.' + cmonth + '.' + cyear);
        //document.f.timeEnd.value = chour + ':' + cmin;
        jQuery('[name=timeEnd]').val(chour + ':' + cmin);
      }
    }
    //if (document.f.author) {
    if (jQuery('[name=author]')) {
      //if (document.f.author.value != "") {
      if (jQuery('[name=author]').val() != "") {
        fieldfound = true;
        //document.f.author.value = "tmp_" + document.f.author.value;
        jQuery('[name=author]').val("tmp_" + jQuery('[name=author]').val());
      } else {
        //document.f.author.value = "tmp_???";
        jQuery('[name=author]').val("tmp_???");
      }
    }

    // ##########################################################################
    // ### etc. for all other types of items (necessary fields for submit-action)
    // ### ---> which must be set for input validation
    // ##########################################################################

    if (fieldfound) {
      //var x = document.getElementsByName("option");
      var x = jQuery.find('[name=option]');
      for (i = 0; i < x.length; i = i + 1) {
         temp_option = jQuery(x[i]);
         //if (x[i].value == breakCrit) {
         if (temp_option.val() == breakCrit) {
          //x[i].click();
          temp_option.click();
        }
      }
    }
  }
}