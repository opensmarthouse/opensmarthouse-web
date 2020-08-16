<?php

class ServerProperties {

// Server parameters

    // debug flag - to save debug info to DEBUG_FILE
    const DEBUG_ENABLED=true;


    const SERVER_TMP_DIR=".";
    const DEBUG_FILE="./export-debug.log";

}

// to print string in browser for debugging purpose
function debug($var){
 if(ServerProperties::DEBUG_ENABLED){
	 $debugFile = ServerProperties::DEBUG_FILE;
	 $dfp = fopen($debugFile,'a') or die("can't open file $debugFile ");
	 fwrite($dfp, $var."\n");
	 fclose($dfp);
 }
}

/*  write to browser  */
function debugHtml($var){
 if(ServerProperties::DEBUG_ENABLED){
 $debugmsg = htmlentities($var, ENT_COMPAT, "UTF-8") ;
 echo " <p> "; echo " $debugmsg " ; echo " <p> ";
 }
}

function printAllParams(){
 if(ServerProperties::DEBUG_ENABLED){
       $post = print_r($_POST, true);
       debug("post params = $post");
       $get = print_r($_GET, true);
       debug("get params = $get");
       // $sess = print_r($_SESSION, true);
       // debug("sess params = $sess");
 }
}

?>
