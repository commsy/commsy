// Flash Player Version Definition for study.log
// -----------------------------------------------------------------------------
// Globals
// Major version of Flash required
var requiredMajorVersion = 9;
// Minor version of Flash required
var requiredMinorVersion = 0;
// Minor version of Flash required
var requiredRevision = 124;
// -----------------------------------------------------------------------------

function getFlashMovie(movieName) {
   var isIE = navigator.appName.indexOf("Microsoft") != -1;
   return (isIE) ? window[movieName] : document[movieName];
}

function callStudyLogSortChronological() {
   getFlashMovie("study_log").callStudyLogSortChronological("");
}
function callStudyLogSortAlphabetical() {
   getFlashMovie("study_log").callStudyLogSortAlphabetical();
}
function callStudyLogSortDefault() {
   getFlashMovie("study_log").callStudyLogSortDefault();
}
function callStudyLogSortByTag(tag) {
   getFlashMovie("study_log").callStudyLogSortByTag(tag);
}
function callStudyLogSortByTagId(tagId) {
   getFlashMovie("study_log").callStudyLogSortByTagId(tagId);
}