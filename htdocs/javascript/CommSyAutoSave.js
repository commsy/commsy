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
    document.f.timerField.value = timeValue;
  }

  timerID = setTimeout("showtime()",1000);
  timerRunning = true;
  if (hours == 0 && minutes == 0 && seconds == 0){
    stopclock();
    fieldfound = false;

    // ### change the following to whatever...                    ###
    // ### the following examples hit 'material' and 'discussion' ###

    if (document.f.title) {
      if (document.f.title.value != "") {
        fieldfound = true;
        document.f.title.value = "tmp_" + document.f.title.value;
      } else {
        document.f.title.value = "tmp_???";
      }
    }
    if (document.f.subject) {
      if (document.f.subject.value != "") {
        fieldfound = true;
        document.f.subject.value = "tmp_" + document.f.subject.value;
      } else {
        document.f.subject.value = "tmp_???";
      }
    }
    if (document.f.description) {
      if (document.f.description.value != "") {
        fieldfound = true;
        document.f.description.value = "tmp_" + document.f.description.value;
      } else {
        document.f.description.value = "tmp_???";
      }
    }
    if (document.f.dayStart) {
      if (document.f.dayStart.value != "") {
        // fieldfound = true;
      } else {
       d = new Date ();
        cday = (d.getDate() < 10 ? '0' + d.getDate() : d.getDate());
        cmonth = ((d.getMonth() + 1) < 10 ? '0' + (d.getMonth() + 1) : (d.getMonth() + 1));
        cyear = d.getFullYear();
        chour = (d.getHours () < 10 ? '0' + d.getHours () : d.getHours ());
        cmin = (d.getMinutes () < 10 ? '0' + d.getMinutes () : d.getMinutes ());
        // cs = (d.getSeconds () < 10 ? '0' + d.getSeconds () : d.getSeconds ());
        document.f.dayStart.value = cday + '.' + cmonth + '.' + cyear;
        document.f.timeStart.value = chour + ':' + cmin;
      }
    }
    if (document.f.dayEnd) {
      if (document.f.dayEnd.value != "") {
        // fieldfound = true;
      } else {
        d = new Date ();
        cday = (d.getDate() < 10 ? '0' + d.getDate() : d.getDate());
        cmonth = ((d.getMonth() + 1) < 10 ? '0' + (d.getMonth() + 1) : (d.getMonth() + 1));
        cyear = d.getFullYear();
        chour = (d.getHours () < 10 ? '0' + d.getHours () : d.getHours ());
        cmin = (d.getMinutes () < 10 ? '0' + d.getMinutes () : d.getMinutes ());
        // cs = (d.getSeconds () < 10 ? '0' + d.getSeconds () : d.getSeconds ());
        document.f.dayEnd.value = cday + '.' + cmonth + '.' + cyear;
        document.f.timeEnd.value = chour + ':' + cmin;
      }
    }
    if (document.f.author) {
      if (document.f.author.value != "") {
        fieldfound = true;
        document.f.author.value = "tmp_" + document.f.author.value;
      } else {
        document.f.author.value = "tmp_???";
      }
    }

    // ##########################################################################
    // ### etc. for all other types of items (necessary fields for submit-action)
    // ### ---> which must be set for input validation
    // ##########################################################################

    if (fieldfound) {
      var x = document.getElementsByName("option");
      for (i = 0; i < x.length; i = i + 1) {
        if (x[i].value == breakCrit) {
          x[i].click();
        }
      }
    }
  }
}