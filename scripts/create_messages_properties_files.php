<?PHP
/* $Id$
 *
 * the purpose of this file is to read all message tag .dat files
 * and write their contents as .properties files that can be
 * read by java programs
 *
 * known problems: the php implementation is able to recursivly replace
 *                 message tags. this is not available in java
 *
 *
 * @author bleek
 * @created 08-june-2005
 *
 */

chdir('..');

echo "starting initialization<br/>\n"; flush();
include_once('functions/text_functions.php');
include_once("classes/cs_translator.php");
echo "initialization complete<br/><br/>\n"; flush();

$translator = NULL;

echo "starting up translator<br/>\n"; flush();
$translator = new cs_translator();
echo "translator started<br/><br/>\n"; flush();

echo "loading all messages<br/>\n"; flush();
$translator->_loadAllMessages();
echo "done<br/><br/>\n"; flush();

echo "saving all message files<br/>\n"; flush();
$translator->saveMessageBundles(); 
echo "done<br/><br/>\n"; flush();
?>