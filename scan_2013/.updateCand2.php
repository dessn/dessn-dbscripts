<?php

session_start();

$iflag  = $_SESSION['iflag'];
$idoit  = $_SESSION['updateCand'];
$ipost  = $_POST['update'];
$ioid   = $_SESSION['ioid'];
$huser  = $_SESSION['userId'];
$year = $_SESSION['year'];
$objId = $_SESSION['objId'];
$locid = $_SESSION['locid'];
$fake = $_SESSION['fake'];
$CType = $_SESSION['CType'];
$oid = $_SESSION['objarray'];

PutEnv("ORACLE_HOME=/oracle/11.1.0");
PutEnv("ORACLE_BASE=/oracle");
require '/home/marriner/Web/db.php';
$server = 'leovip148.ncsa.uiuc.edu/desoper';
$user = 'snwriter';
$passwd = 'sn4rite';
$db=oci_connect($user,$passwd,$server) or
  die("Could not connect to server."); 
//echo "this is now the update page";
if($ipost == 1 && $idoit == 1 && $iflag == 1) 
  {
  $Now = gmdate("Y-m-d H:i:s");
  $userId=$_SESSION['userId'];
  $i=0;
  $numbad=0;
  $numgood=0;
  $nummissing=0;
  $numunscanned=$_SESSION['iend']+1;
  $chtype=$_POST['candidate[$i]'];
  $htype=$_POST['candidate'];
  for ($i=0; $i<=$_SESSION['iend']; $i++){
  echo "chtype = $chtype , i=$i, htype=$htype[$i] <br><br>";
  $numunscanned=0;
      if ($htype[$i]=='artifact'){
	$chtypen=16;
	$numbad++;
      }
      elseif ($htype[$i]=='SN') {
	$chtypen=1;
	$numgood++;	
      }
      elseif ($htype[$i]=='none') {
      $chtypen=32;
      $nummissing++;
      }
      else {
	die ("selected type not on list $htype $i <br> <P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">Return to initializing page.</A></P>\n");
      }
    if ($chtypen>0){
    $updateQuery = "UPDATE SNOBS ";
    $updateQuery .= "SET OBJECT_TYPE=$chtypen where SNOBJID=$oid[$i]";
    $stmt = oci_parse($db, $updateQuery) or
      die("Parsing error.");
    oci_execute($stmt) or
      die("SNOBS update did not execute.<br> $insertQuery <br> <P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">Return to initializing page.</A></P>\n");
    oci_free_statement($stmt);
    echo "$updateQuery<br>";



  $insertQuery = "INSERT INTO SNSCAN";
  $insertQuery .= "(SNSCANID, SNID, SNOBSID, SCANDATE, CATEGORYTYPE, SCANNER) ";
 $insertQuery .= " VALUES(SNSCAN_seq.nextval, $objId, $oid[$i], CURRENT_TIMESTAMP,'$htype[$i]','$userId')";
  $stmt = oci_parse($db, $insertQuery) or
      die("Parsing error.");
  oci_execute($stmt) or
  die("Insert query into scan did not execute.<br> $insertQuery <br> <P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">Return to initializing page.</A></P>\n");
  $newscanid++;
    }
  }
  ++$_SESSION['objectsScanned'];
$candupdateQuery = "UPDATE SNCAND ";
$candupdateQuery .= " SET num_unscanned=$numunscanned, num_real=num_real+$numgood, num_artifact=num_artifact+$numbad, num_unsure=$nummissing ";
$candupdateQuery .= " WHERE SNID=$objId ";
//echo "$candupdateQuery <br>";
//echo " <br> <P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">Return to initializing page.</A></P>\n";
 $stmt = oci_parse($db, $candupdateQuery) or
      die("Parsing error.");
 oci_execute($stmt) or
      die("Insert query into scan did not execute.<br> $candupdateQuery <br> <P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">Return to initializing page.</A></P>\n");
oci_free_statement($stmt);
}//end of update	
if ($ipost == 2){
  $_SESSION['objId']=$_SESSION['oldoid'];
  $_SESSION['totalObjectslefttoScan']++;
  oci_free_statement($stmt);
  oci_close($db);
  header ("Location: manual_scan.php");
 }
else{
$_SESSION['oldoid']=$_SESSION['objId'];
$_SESSION['objId']++;
$_SESSION['totalObjectslefttoScan']--;
oci_free_statement($stmt);
  oci_close($db);
 header ("Location: manual_scan.php");
}
?>