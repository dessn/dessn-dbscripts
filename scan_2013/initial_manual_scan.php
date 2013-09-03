<?php

session_start();

session_register('mysessid');
session_register('locid');
session_register('year');
session_register('trun');
session_register('chip');
session_register('field');
session_register('cc');
session_register('timelim');
session_register('srr');
session_register('magnify');
session_register('show_unscan');
session_register('objId');
session_register('fstCand');
session_register('userId');
session_register('objRa');
session_register('objDecl');
session_register('updateObj');
session_register('numHistObj');
session_register('numHistOld');
session_register('objectsScanned');
session_register('sessid');
session_register('upObjId');
session_register('fake');
session_register('sclims');
session_register('wflags');
session_register('owflags');
session_register('tflags');
session_register('xflags');
session_register('totalObjects');
session_register('nobsvar');
session_register('magcut');
session_register('entd');
session_register('iflag');
session_register('tol');
session_register('ed');
session_register('candRa');
session_register('candDec');
session_register('totalObjectstoScan');
session_register('totalObjectslefttoScan');
session_register('updateCand');
session_register('numsets');
session_register('setnum');
session_register('CType');
session_register('oldoid');
session_register('dlim');
session_register('startcount');
session_register('numobs');
session_register('numepochs');
session_register('fnal_only');
session_register('minid');
session_register('maxid');
session_register('objarray');
session_register('iend');

$_SESSION['mysessid'] = 1;
$_SESSION['tol'] = 1.0/3600.0;
$_SESSION['dlim'] = 35;
$_SESSION['iflag'] = 0;

$_SESSION['objectsScanned'] = 0;
$_SESSION['objId'] = -1;
$_SESSION['sessid'] = session_id();
$_SESSION['upObjId'] = -1;

$_SESSION['sclims']=" ";
$_SESSION['xflags']=" ";
$_SESSION['numsets']=8; // Number of subsets possible
$_SESSION['startcount']=0;
$sdum = $_SESSION['mysessid'];

?>

<html>
<head>
<title>Initialization for Manual Scan</title>
</head>
<BODY>
<P>This is DESSN-SCAN v 0.9.5 June-26-2013</P>
<FORM METHOD = "POST" ACTION="manual_scan.php">



USER <br>
<SELECT NAME="userId">
   <OPTION VALUE = "Nobody" SELECTED>Norman No-one
   <OPTION VALUE = "Barbary">Kyle Barbary <!-- 1  -->
   <OPTION VALUE = "Biswas">Rahul Biswas <!--23-->
   <OPTION VALUE = "Brown">Peter Brown  <!-- 2 -->
   <OPTION VALUE = "Campbell">Heather Campbell  <!-- 3 -->
   <OPTION VALUE = "Cane">Rachel Cane  <!-- 4 -->
   <OPTION VALUE = "Covarrubias">Ricardo Covarrubias <!-- 22 -->
   <OPTION VALUE = "Dandrea">Chris Dandrea  <!-- 5 -->
   <OPTION VALUE = "Desai">Shantanu Desai <!--24-->
   <OPTION VALUE = "Finley">David Finley  <!-- 6 -->
   <OPTION VALUE = "Fischer">John Fischer  <!-- 7 -->
   <OPTION VALUE = "Gilhool">Steve Gilhool <!-- 26 -->
   <OPTION VALUE = "Gupta">Ravi Gupta  <!-- 8 -->
   <OPTION VALUE = "Kessler">Rick Kessler  <!-- 9 -->
   <OPTION VALUE = "Kim">Alex Kim  <!-- 10 Alter-->
   <OPTION VALUE = "Kovacs">Eve Kovacs  <!-- 11 -->
   <OPTION VALUE = "Kuhlmann">Steve Kuhlmann  <!-- 12 -->
   <OPTION VALUE = "March">Marisa March <!-- 13 -->
   <OPTION VALUE = "Mosher">Jennifer Mosher <!-- 14 -->
   <OPTION VALUE = "Nichol">Bob Nichol <!-- 15  -->
   <OPTION VALUE = "Paech">Kerstin Paech <!-- 25 Alter -->
   <OPTION VALUE = "Papadopoulos">Andreas Papadopoulos <!-- 16 -->
   <OPTION VALUE = "Sako">Masao Sako <!-- 17 -->
   <OPTION VALUE = "CSmith">Chris Smith <!-- 18 -->
   <OPTION VALUE = "MSmith">Mat Smith <!-- 25 -->
   <OPTION VALUE = "Schubnell"> Michael Schubnell
   <OPTION VALUE = "Spinka">Hal Spinka <!-- 19 -->
   <OPTION VALUE = "Sullivan">Mark Sullivan <!-- 20 -->
   <OPTION VALUE = "Thomas">Rollin Thomas <!-- 27 -->
   <OPTION VALUE = "Wester">William Wester <!-- 21 -->
   <OPTION VALUE = "Guest">Notonlist <!-- 21 -->

</SELECT>
</p>
<FONT COLOR="RED">Warning:</FONT>
Pushing BACK, RELOAD or STOP on your web browser does not have the effect one would expect, and can cause bogus values to be written to the database. Please refrain from using these buttons while scanning.


<INPUT TYPE = "HIDDEN" NAME = "mysessid" VALUE=1>
<INPUT TYPE = "HIDDEN" NAME = "update" VALUE=-1>

<p><INPUT TYPE = "CHECKBOX" NAME = "updatecand" 
	 VALUE = 1 CHECKED >Update scan table.</P>
 <P><INPUT TYPE = "CHECKBOX" NAME = "show_unscan" 
	 VALUE = 1 CHECKED >Show unscanned candidates only.</p>
<!-- <P><INPUT TYPE = "CHECKBOX" NAME = "fnal_only" 
	 VALUE = 0 >Show FNAL candidates only.</p>-->


<?php
// <P><INPUT TYPE = "CHECKBOX" NAME = "flags" 
//	 VALUE = 1 >Ignore ALL flags.  Show everything!
?>
</p>


<p>GIF Magnification<br>
<SELECT NAME="magnify">
   <OPTION VALUE = 1>1  
   <OPTION VALUE = 2>2  
   <OPTION VALUE = 3 SELECTED>3  
   <OPTION VALUE = 4>4  
</SELECT></p>
<!--
<p><INPUT TYPE = "RADIO" NAME = "nobsvar" 
         VALUE = 0  >VIEW 1ST EPOCH ONLY - NOT CURRENTLY WORKING</P>
<p><INPUT TYPE = "RADIO" NAME = "nobsvar" 
         VALUE = 1 CHECKED >VIEW 2ND+ EPOCHS ONLY - NOT CURRENTLY WORKING</P>
<p><INPUT TYPE = "RADIO" NAME = "nobsvar" 
         VALUE = 0 >VIEW 2 MOST RECENT EPOCHS - NOT CURRENTLY WORKING</P>
-->


<?php 



?>
<?
//echo "<H1 ALIGN=\"CENTER\">Candidate SN$SNId</H1>";
PutEnv("ORACLE_HOME=/oracle/11.1.0");
PutEnv("ORACLE_BASE=/oracle");
require '/home/marriner/Web/db.php';
$server = 'leovip148.ncsa.uiuc.edu/desoper';
$user = 'snreader';
$passwd = 'sn4rede';
$db=oci_connect($user,$passwd,$server) or
  die("Could not connect to server.");
/////////////////////////////////////////////////////
$query = "SELECT LATEST_NITE ed, count(*) cc ";
$query .= "from SNCAND where ENTRY_DATE>'22-MAR-13' and LATEST_NITE is not null and num_unscanned>=2 and num_real<2 and cand_type>=0 and numepochs>=2 and numobs>1 and LATEST_NITE>20130801 group by LATEST_NITE ORDER BY LATEST_NITE desc";

//echo "$query";
oci_free_statement($stmt);
$stmt = oci_parse($db, $query) or
      die("Parsing error.");
oci_execute($stmt) or
die("Could not execute query <BR>$query");

echo "<p>Latest Nite (count unscanned)<br>";
echo "<SELECT name=\"entd\">\n";
echo "<option value= ></option>\n";
while($row = oci_fetch_assoc($stmt)) {
  $Edtxt = $row['ED'];
  $ccount = $row['CC'];
  echo "$Edtxt";
  echo "<option value=$Edtxt>$Edtxt ($ccount)</option>\n";
 }
echo "</SELECT></p>";
/////////////////////////////////////////////////////
$numsets=$_SESSION['numsets'];
echo "<p>Subset Number (0 means all candidates)<br>";
echo "<SELECT name=\"chip\">\n";
echo "<option value=0 SELECTED>0</option>\n";
$chipid=1;
while($chipid <= $numsets) {
  echo "$chipid";
  echo "<option value=$chipid>$chipid</option>\n";
  $chipid++;
 }
echo "</SELECT></p>";
oci_free_statement($stmt);
oci_close($db);
?>

<br>
<P><A HREF=Scanning.pdf TARGET=_BLANK>Manual Scan Guide</A><P>
 <P><A HREF="https://docs.google.com/spreadsheet/ccc?key=0An2eReQ5ul5XdC16TV9UOGVwWFR4d2lSNTZWaWVxY0E#gid=0" TARGET="_TOP">Scanning Schedule</A></P>

<br>
<INPUT TYPE="TEXT" NAME="numobs" value=2 MAXLENGTH="9">&nbsp Number of Observations<br>
<INPUT TYPE="TEXT" NAME="numepochs" value=2 MAXLENGTH="9">&nbsp Number of Epochs<br>
<INPUT TYPE="TEXT" NAME="fstCand" value=0 MAXLENGTH="9">&nbsp First SNID<br>
<INPUT TYPE="TEXT" NAME="magcut" value=30 MAXLENGTH="9">&nbsp Mag Cut <br>
<p><INPUT TYPE = "CHECKBOX" NAME = "debug" VALUE = 1  >DEBUG</P>

<p><INPUT TYPE="submit" NAME = "submit" VALUE ="ENTER"></p>


</html>
