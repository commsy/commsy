/*
SCORM 2004

Initialize( “” ) : bool
Terminate( “” ) : bool
GetValue( element : CMIElement ) : string
SetValue( element : CMIElement, value : string) : string
Commit( “” ) : bool
GetLastError() : CMIErrorCode
GetErrorString( errorCode : CMIErrorCode ) : string
GetDiagnostic( errocCode : CMIErrorCode ) : string

SCORM 1.1 / SCORM 1.2

LMSInitialize( “” ) : bool
LMSFinish( “” ) : bool
LMSGetValue( element : CMIElement ) : string
LMSSetValue( element : CMIElement, value : string) : string
LMSCommit( “” ) : bool
LMSGetLastError() : CMIErrorCode
LMSGetErrorString( errorCode : CMIErrorCode ) : string
LMSGetDiagnostic( errocCode : CMIErrorCode ) : string

The method names vary slightly between SCORM versions, but conceptually the methods are identical.

Notes:

    * The bool type is a SCORM boolean, which is actually a string having the value “true” or “false”.
    * The “” parameter is required by all SCORM methods that don’t accept any other arguments. SCOs are simply required to pass an empty string parameter to these methods.
    * The CMIElement data type is a string corresponding to the SCORM data model elements described below.
    * The CMIErrorCode data type is a three digit number, represented a string, that corresponds to one of the SCORM Run-Time error codes.
*/

/* SCORM 2004 */
API_1484_11 = new Object();

// methods
API_1484_11.Initialize = function(string) {
  return "true";
}

API_1484_11.Terminate = function() {
  // return can indicate whether SCO data was succesfully persisted to the server
}

API_1484_11.GetValue = function(element) {
  
}

API_1484_11.SetValue = function(element, value) {
  console.log('setting element "' + element + '" to "' + value + '"');
}

API_1484_11.Commit = function() {
  
}

API_1484_11.GetLastError = function() {
  
}

API_1484_11.GetErrorString = function(errorCode) {
  
}

API_1484_11.GetDiagnostic = function(errorCode) {
  
}

/* copy method behavior for SCORM 1.1 / 1.2 */
API = new Object();
API.LMSInitialize = API_1484_11.Initialize;
API.LMSFinish = API_1484_11.Terminate;
API.LMSGetValue = API_1484_11.GetValue;
API.LMSSetValue = API_1484_11.SetValue;
API.LMSCommit = API_1484_11.Commit;
API.LMSGetLastError = API_1484_11.GetlastError;
API.LMSGetErrorString = API_1484_11.GetErrorString;
API.LMSGetDiagnostic = API_1484_11.Getdiagnostic;