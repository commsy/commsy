jQuery("div[id='fileFinished']").append(
   jQuery("<input/>", {
      "type"		:	"checkbox",
      "checked"	:	"checked",
      "name"		:	"filelist[]",
      "value"		:	file
   }),
   jQuery("<span/>", {
      "style"		:	"font-size: 10pt;",
      "innerHTML"	:	file
   }),
   jQuery("<br/>")
);