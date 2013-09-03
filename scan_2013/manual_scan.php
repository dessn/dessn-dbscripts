<?php

session_start();

$SQLCandTable   = "SNCAND";
PutEnv("ORACLE_HOME=/oracle/11.1.0");
PutEnv("ORACLE_BASE=/oracle");
require '/home/marriner/Web/db.php';
$server = 'leovip148.ncsa.uiuc.edu/desoper';
$user = 'snreader';
$passwd = 'sn4rede';
$db=oci_connect($user,$passwd,$server) or
  die("Could not connect to server.");
$upd='.updateCand2.php';

/** iflag = 0 on initialization **/


if ( $_SESSION['iflag'] == 0 ) {
  $_SESSION['field'] = $_POST['field'];
  $_SESSION['chip'] = $_POST['chip'];
  $_SESSION['magcut'] = $_POST['magcut'];
  $magnify = $_POST['magnify'];
  $_SESSION['magnify'] = 38*$magnify;
  $_SESSION['userId'] = $_POST['userId'];
  $_SESSION['setnum'] = $_POST['chip'];
  $_SESSION['fstCand'] = $_POST['fstCand'];  
  $_SESSION['objId'] = $_POST['fstCand']; 
  $_SESSION['ed']    = $_POST['entd']; 
  $_SESSION['tflags'] = "";
  $_SESSION['numobs'] = $_POST['numobs'];
  $numobs = $_SESSION['numobs'];
  $_SESSION['numepochs'] = $_POST['numepochs'];
  $numepochs = $_SESSION['numepochs'];
  $debug = $_POST['debug'];

  if ($_POST['updatecand']==1)
    $_SESSION['updateCand'] = 1;
  else
    $_SESSION['updateCand'] = 0;
  
  if ($_POST['show_unscan']==1)
    $_SESSION['show_unscan'] = 1;
  else
    $_SESSION['show_unscan'] = 0;
  if ($_POST['fnal_only']==1)
     $_SESSION['fnal_only']=1;
  else
     $_SESSION['fnal_only']=0;

  if ($_POST['flags']) {
     $_SESSION['wflags'] = "";
     $_SESSION['owflags'] = "";
      }
  else {
  $_SESSION['wflags'] = $_SESSION['xflags'];
  $_SESSION['owflags'] = $_SESSION['xflags'];
  }
  if ($_SESSION['show_unscan']) {
     $_SESSION['wflags'] .= " AND c.NUM_UNSCANNED>=$numobs";
     $_SESSION['owflags'] = " AND (OBJECT_TYPE=0 or OBJECT_TYPE=32 or OBJECT_TYPE is null) ";
      }
  if ($objId) {
     $_SESSION['wflags'] .= " AND c.SNID>=$objId";
     }
 
  $trun = $_SESSION['trun'];
  $fstCand = $_SESSION['fstCand'];

 
  }//end initialization
$uid = $_SESSION['userId'];
$setnum = $_SESSION['setnum'];
$numsets = $_SESSION['numsets'];
$setnumold = $_SESSION['setnum'];
  if ($setnum==0) $numsets=1;
  if ($numsets==1) $setnum=1;
$ed = $_SESSION['ed'];
$objId = $_SESSION['objId'];
$tol = $_SESSION['tol'];
$dlim = $_SESSION['dlim'];
$magcut = $_SESSION['magcut'];
$numobs = $_SESSION['numobs'];
$numepochs = $_SESSION['numepochs'];
$reallim = 2;
 
if($_SESSION['userId'] == 'Nobody') {
  $huser = $_SESSION['userId'];
  echo "<a href=\"initial_manual_scan.php\">return to init page</a>";
  echo "<br>";
  die("DIE: Invalid user $huser.\n");
 }

// Common code for all entries
// Restore session variables  

$magnify = $_SESSION['magnify'];
$userId = $_SESSION['userId'];
$tol = $_SESSION['tol'];


if ( $_SESSION['iflag'] == 0 ) {
  $_SESSION['iflag'] = 1;
 $upquery = "INSERT INTO SNCAND_LOG VALUES(sncand_log_seq.nextval, '$userId', CURRENT_TIMESTAMP, to_date('$ed', 'YYYYMMDD'), $setnumold, $numobs, $numepochs, $magcut)";
$usera = 'snwriter';
$passwda = 'sn4rite';
$dba=oci_connect($usera,$passwda,$server) or
  die("Could not connect to server.");

  oci_free_statement($stmt);
     $stmt = oci_parse($dba, $upquery) or
     die("Parsing error.");
  oci_execute($stmt) or
     die("Insertion into SNCAND_LOG did not execute. <br> $upquery <br>  <a href=\"initial_manual_scan.php\">return to init page</a>");
      oci_free_statement($stmt);
   oci_free_statement($stmt);   
   oci_close($dba);

}


if ($_SESSION['startcount']==0) 
{
  $queryCount = "SELECT count(c.SNID) ccount, min(c.SNID) mid, max(c.SNID) maxid FROM $SQLCandTable c";

  $queryCount .= " WHERE to_char(c.LATEST_NITE)='$ed' and peak_mag<$magcut and numobs>=$numobs and numepochs>=$numepochs and cand_type>=0  and entry_date>'22-MAR-13' ";
  //if ($_SESSION['fnal_only']==1) $queryCount .= " and cand_type=1";
 
  $stmt = oci_parse($db, $queryCount) or
     die("Parsing error.");
  oci_execute($stmt) or
     die("SQL Query to count objects did not execute. <br> $queryCount ");
  $row = oci_fetch_assoc($stmt);

     $ccount = $row['CCOUNT']; 
     if ($row['MID']>0)$minsn = $row['MID'];
     else $minsn=0;
    $maxsn = $row['MAXID'];
    
     $bin = round(($ccount)/$numsets + 0.5);
     $lowlim = ($setnum-1)*$bin+1;
    
     $queryMin = "select max(SNID) minid from ";
     $queryMin .= " (select SNID from (SELECT SNID FROM $SQLCandTable ";
     $queryMin .= " where SNID>=$minsn and to_char(LATEST_NITE)='$ed' ";
     $queryMin .= " and peak_mag<$magcut and numobs>=$numobs ";
     $queryMin .= " and numepochs>=$numepochs  and cand_type>=0 and entry_date>'22-MAR-13'  ";
     if ($_SESSION['fnal_only']==1) $queryMin .= " and cand_type=1";
     $queryMin .= " order by SNID asc";
     $queryMin .= ") a where rownum<=$lowlim) b";
     oci_free_statement($stmt);
     //if ($_SESSION['fnal_only']==1) $query .= " and cand_type=1";
     $stmt = oci_parse($db, $queryMin) or
     die("Parsing error.");
  oci_execute($stmt) or
     die("SQL Query to min objects did not execute. <br> $queryMin <br>  <a href=\"initial_manual_scan.php\">return to init page</a>");
  $row = oci_fetch_assoc($stmt);
  $minid = $row['MINID'];
  

     $queryMax = "select max(SNID) maxid from ";
     $queryMax .= " (SELECT SNID FROM (SELECT SNID FROM $SQLCandTable ";
     $queryMax .= " where SNID>=$minid and to_char(LATEST_NITE)='$ed' ";
     $queryMax .= " and peak_mag<$magcut and numobs>=$numobs ";
     $queryMax .= " and numepochs>=$numepochs  and cand_type>=0 and entry_date>'22-MAR-13' ";
     if ($_SESSION['fnal_only']==1) $queryMax .= " and cand_type=1";
     $queryMax .= " order by SNID asc";
     $queryMax .= ") a where rownum<=$bin) b";
     oci_free_statement($stmt);
     $stmt = oci_parse($db, $queryMax) or
     die("Parsing error.");
  oci_execute($stmt) or
     die("SQL Query to max objects did not execute. <br> $queryMax <br> $queryMin<br> <a href=\"initial_manual_scan.php\">return to init page</a> ");
  $row = oci_fetch_assoc($stmt);
  $maxid = $row['MAXID'];
  $_SESSION['totalObjects']=$ccount;


////////////////////////////////////////////////////////////////////

$qcount = "SELECT count(*) RSC";
$qcount .= " from $SQLCandTable c";
$qcount .= " {$_SESSION['tflags']}";
$qcount .= " WHERE to_char(LATEST_NITE)='$ed' and c.SNID>=$minid and c.SNID<=$maxid and ENTRY_DATE>'22-MAR-13' ";
$qcount .= " and peak_mag<$magcut and num_unscanned>=$numobs and numepochs>=$numepochs  and cand_type>=0 and num_real<$reallim and numobs>1";
oci_free_statement($stmt);
if ($_SESSION['fnal_only']==1) $query .= " and cand_type=1";
$qcount .= " {$_SESSION['wflags']}";


$stmt = oci_parse($db, $qcount) or
      die("Parsing error.");
oci_execute($stmt) or
die("Could not execute query to count unscanned objects <br> $qcount");
	   if ($row = oci_fetch_assoc($stmt)) {
	   $scancount = $row['RSC'];
	   $_SESSION['totalObjectslefttoScan'] = $scancount;
	   $_SESSION['totalObjectstoScan'] = $scancount;
	   }

    $_SESSION['startcount']=1;
    $_SESSION['minid']=$minid;
    $_SESSION['maxid']=$maxid;
   }

   $minid=$_SESSION['minid'];
   $maxid=$_SESSION['maxid'];

$querya = "SELECT d.SNID, d.CAND_TYPE, d.RA, d.DEC, d.SNFAKE_ID";
$querya .= " from (SELECT c.SNID, c.CAND_TYPE, c.RA, c.DEC, c.SNFAKE_ID";
$querya .= " from $SQLCandTable c";
$querya .= "{$_SESSION['tflags']}";
$querya .= " WHERE to_char(LATEST_NITE)='$ed' and c.SNID>=$minid and c.SNID<=$maxid and c.SNID>=$objId and ENTRY_DATE>'22-MAR-13' ";
if ($_SESSION['oldoid']==$_SESSION['objId']) $querya .= " AND numepochs>=$numepochs and peak_mag<$magcut   ";
else {
$querya .= " AND numobs>=$numobs and numepochs>=$numepochs and peak_mag<$magcut and cand_type>=0 and num_real<$reallim";
$querya .= " {$_SESSION['wflags']} ";
}
if ($_SESSION['fnal_only']==1) $querya .= " and cand_type=1";
$querya .= " order by c.SNID asc) d where rownum<=1";


$stmt = oci_parse($db, $querya) or
     die("Parsing error.");
oci_execute($stmt) or
die("Could not execute query <BR>$querya");


if ($debug==1){
echo "<p>"; 

echo "SCANNING PAGE CURRENTLY UNDER MAINTENANCE as of 2:15 CT <BR>";

echo "count of unscanned candidates from Entry Date $ed is $ccount ($minsn to $maxsn), looking at $bin starting at $minid ending at $maxid \n";
echo "</p>" ;
echo "<p>";
echo "Entry Date = $ed \n <br>";
echo "User = $uid \n <br>";
echo "set number = $setnum out of $numsets <br>";
echo "First Candidate = $fstCand <br>";
echo "Objects to Scan $scancount";
echo "</p>";
echo "queryCount <br>";
echo "$queryCount \n <br>";
echo "$ccount <br>";
echo "queryMin <br>";
echo "$queryMin \n <br>";
echo "$minid $setnum $bin<br>";
echo "queryMax <br>";
echo "$queryMax \n <br>";
echo "query <br>";
echo "$query <br>";
echo "queryDo <br>";
echo "$queryDO <br>";
echo "qcount <br>";
echo "$qcount <br>";



     echo "<P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">
	Return to initializing page.</A></P>\n";
 }


echo "<FORM METHOD = \"POST\" ACTION=\"$upd\" TARGET = \"_top\">";
echo " <INPUT TYPE=\"HIDDEN\" NAME=\"update\" VALUE=1>";

if ($row = oci_fetch_assoc($stmt)) 
  {
 
    $objId = $row['SNID'];
    $_SESSION['objId'] = $objId;  
    $CAND_TYPE = $row['CAND_TYPE'];
    $RA = $row['RA'];
    $DEC = $row['DEC'];    
    $fake = $row[SNFAKE_ID];
    $_SESSION['candRa'] = $RA;
    $_SESSION['candDec'] = $DEC;
    $_SESSION['fake'] = $fake;
    $_SESSION['CType'] = $CAND_TYPE;
     



  //////////////////////////////////////////////////////////   
    // Now build web page for normal case = object found 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Manual Scan</title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">

</head>
<body  class="yui3-skin-sam" >

<?
   
 echo "<FORM METHOD = \"POST\" ACTION=\"$upd\" TARGET = \"_top\">";
    echo " <INPUT TYPE=\"HIDDEN\" NAME=\"update\" VALUE=1>"; 

$PIindeg = 3.14159265/180.0;  
$RAMIN = $RA-$tol/cos($DEC*$PIindeg);
$RAMAX = $RA+$tol/cos($DEC*$PIindeg);
$DECMIN = $DEC-$tol;
$DECMAX = $DEC+$tol;
$ed = $_SESSION['ed'];
$dlim = $_SESSION['dlim'];
$objId = $_SESSION['objId'];
$magnify = $_SESSION['magnify'];
$CType = $_SESSION['CType'];
$totalObjects = $_SESSION['totalObjectstoScan'];
$ObjectsLeft = $_SESSION['totalObjectslefttoScan'];
$ra = $_SESSION['candRa'];
$dec = $_SESSION['candDec'];
$ndec = $dec*(-1);
$rashow = round($ra, 3);
$decshow = round($dec, 3);
$setnum = $_SESSION['setnum'];

$j=0;
echo "<table border = \"1\">";
echo "<TR><TH>Scanner</TH><TD>";
echo $_SESSION['userId'];
echo "</TD><TH>";
if ($_SESSION['updateCand']) echo "<FONT COLOR=\"GREEN\">Updating</FONT>";
else echo "<FONT COLOR=\"RED\">No updates</FONT>";
echo "</TH><TD ALIGN=\"CENTER\">";
if ($_SESSION['show_unscan']) echo "Unscanned";
else echo "All";
echo "</TD></TR>";
echo "<tr><th>SNID</th> <td>";
echo "<A HREF=\"../examineCand.php?SNId=$objId\" TARGET=\"_BLANK\">$objId</A>";
echo "</td><th>Entry Date</th>";
echo "<td>$ed</td></tr>";

$topdate="20121231";


$query = "SELECT  distinct o.FLUX, o.FLUX_ERR, o.SNOBJID, e.BAND, e.NITE, to_date(substr(e.date_obs,1,10), 'yyyy-mm-dd') DOBS, substr(e.DATE_OBS,1, 19) DATE_OBS, o.EXPOSUREID, o.COADDID, e.NITE, e.MJD_OBS,  o.MAG, substr(e.OBJECT, 22, 2) TFLD, i.ccd";
$query .= ", (o.RA-$ra)*3600 DRA, (o.Dec+$ndec)*3600 DDEC, e.NITE, e.MJD_OBS ";
$query .= ", i.FWHM ";
$query .= " from SNOBS o JOIN EXPOSURE e on o.EXPOSUREID=e.EXPNUM JOIN IMAGE i on o.COADDID=i.ID";
//$query .= " WHERE o.ra>$RAMIN and o.ra<$RAMAX and o.dec>$DECMIN and o.dec<$DECMAX and to_number(e.NITE)<=$topdate";
$query .= " WHERE o.ra>$RAMIN and o.ra<$RAMAX and o.dec>$DECMIN and o.dec<$DECMAX";
if ($_SESSION['oldoid']!=$_SESSION['objId']) $query .= " {$_SESSION['owflags']} ";
$query .= " AND  o.STATUS>=0  ";
$query .= " order by e.MJD_OBS desc, e.BAND";

$query = " with obs as ( select /*+index(snobs, SNOBS_RADEC_IDX) materialize */ FLUX, FLUX_ERR, SNOBJID, EXPOSUREID, COADDID, MAG, RA, DEC from SNOBS where ra>$RAMIN and ra<$RAMAX and dec>$DECMIN and dec<$DECMAX";
$query .= $_SESSION['owflags'];
//$query .= " AND (OBJECT_TYPE=0 or OBJECT_TYPE=32 or OBJECT_TYPE is null) ";
$query .= " AND STATUS>=0 ) ";
$query .= " select distinct o.FLUX, o.FLUX_ERR, o.SNOBJID, e.BAND, e.NITE, to_date(substr(e.date_obs,1,10), 'yyyy-mm-dd') DOBS, substr(e.DATE_OBS,1, 19) DATE_OBS, o.EXPOSUREID, o.COADDID, e.NITE, e.MJD_OBS, o.MAG, substr(e.OBJECT, 22, 2) TFLD, i.ccd, (o.RA-$ra)*3600 DRA, (o.Dec+$ndec)*3600 DDEC, e.NITE, e.MJD_OBS , i.FWHM from obs o JOIN EXPOSURE e on o.EXPOSUREID=e.EXPNUM JOIN IMAGE i on o.COADDID=i.ID   order by e.MJD_OBS desc, e.BAND ";


$stmt = oci_parse($db, $query) or
      die("Parsing error.");

oci_define_by_name($stmt, 'EXPOSUREID', $expid);
oci_define_by_name($stmt, 'BAND', $band);
oci_define_by_name($stmt, 'SNOBJID', $oid1);
oci_define_by_name($stmt, 'DOBS', $dobs);
oci_define_by_name($stmt, 'BAND', $filter);
oci_define_by_name($stmt, 'EXPOSUREID', $ExposureId);
oci_define_by_name($stmt, 'COADDID', $CoaddId);
oci_define_by_name($stmt, 'FLUX', $flux);
oci_define_by_name($stmt, 'FLUX_ERR', $fluxerr);
oci_define_by_name($stmt, 'DATE_OBS', $nite);
oci_define_by_name($stmt, 'MAG', $mag1);
oci_define_by_name($stmt, 'TFLD', $tfld);
oci_define_by_name($stmt, 'DRA', $ora1);
oci_define_by_name($stmt, 'DDEC', $odec1);
oci_define_by_name($stmt, 'FWHM', $psf1);
oci_define_by_name($stmt, 'NITE', $nitec);
oci_define_by_name($stmt, 'CCD', $chip);

oci_execute($stmt) or
die("Could not execute query <br>$query");
$i=0;
$m=0;

while (oci_fetch($stmt)){
      $m=1;
      $oid[$i]=$oid1;
      $filter=$band;
      if ($i==0){


      echo "<tr><th>Ttl Objects</th> <td> $totalObjects </td><th>Objects Left</th><td> $ObjectsLeft </td></tr><tr><th># Scanned</th> <td>  {$_SESSION['objectsScanned']} </td><th>Subset</th><td>  $setnum </td></tr><tr><th>Chip</th> <td> $chip</td> <th>Field</th><td>$tfld</td></tr><tr><th>ra</th> <td> $rashow</td> <th>decl</th> <td> $decshow</td></tr></table></td></tr>";

      echo "<table cellpadding=\"4\", border = \"1\">\n";
      echo "<TR ALIGN=\"CENTER\"><th>Obs. Date</th><th>Filter</th><th>Object ID</TH>\n";
      echo "<TH>Search</TH><TH>Template</TH><TH>Subtracted</TH>";
      echo "<TH><button name=\"rad1\" id=\"ArtifactAll\" type=\"button\" value=\"Y\">  Artifact</TH><TH><button name=\"rad1\" id=\"SNAll\" type=\"button\" value=\"Y\">  Not Artif.</TH><TH><button name=\"rad1\" id=\"MissingAll\" type=\"button\" value=\"Y\">  Missing</TH>";
      //echo "<TH></TH><TH></TH><TH></TH>";
      echo "<TH>Mag.</TH><TH>dRA</TH><TH>dDec</TH><TH>FWHM_IMG</TH>\n";
      $j=1;
	}	 


  $mag = round($mag1, 2);
  $SNR = round($flux/$fluxerr, 2);
  $ora = round($ora1, 4);
  $odec = round($odec1, 4);
  $otheta = round(sqrt(pow($ora,2) + pow($odec, 2)),4);
  $psf = round($psf1,2);

 
 	if ($CType==1) $prefix = "../stamps/Exp$ExposureId";
	//else $prefix = "../../../../des008/cluster_scratch/users/ricardoc/2012/RC1/$nitec/$CoaddId";
	else $prefix = "../2012/RC1/$nitec/$CoaddId";
	$namesearch = sprintf("$prefix/srch%d.gif",$oid[$i]);
	$fitnamesearch = sprintf("$prefix/srch%d.fits",$oid[$i]);
	$nametemp = sprintf("$prefix/temp%d.gif",$oid[$i]);
	$fitnametemp = sprintf("$prefix/srch%d.fits",$oid[$i]);
	$namediff = sprintf("$prefix/diff%d.gif",$oid[$i]);
	$fitnamediff = sprintf("$prefix/srch%d.fits",$oid[$i]);
	if(file_exists($namediff))
	{
   	echo "<TR ALIGN=\"CENTER\"><TD>$nite</TD><TD>$filter</TD><TD>$oid[$i]</TD>\n";
	echo "<TD><a href=\"$fitnamesearch\"><IMG src=\"$namesearch\" width=\"$magnify\" height=\"$magnify\"></td>\n";
	echo "<TD><a href=\"$fitnametemp\"><IMG src=\"$nametemp\" width=\"$magnify\" height=\"$magnify\"></td>\n";
	echo "<TD><a href=\"$fitnamediff\"><IMG src=\"$namediff\" width=\"$magnify\" height=\"$magnify\"></TD>\n";
	echo "<td> <INPUT TYPE=\"RADIO\" NAME=\"candidate[$i]\" VALUE=\"artifact\" class=\"artifactc\" CHECKED> </td>";
	echo "<td> <INPUT TYPE=\"RADIO\" NAME=\"candidate[$i]\" VALUE=\"SN\" class=\"SNC\"> </td>";
	echo "<td> <INPUT TYPE=\"RADIO\" NAME=\"candidate[$i]\" VALUE=\"none\" class=\"missingc\" > </td>";
	echo "<td>$mag</td><td>$ora</td><td>$odec</td><td>$psf</td>\n";
	echo "</TR>\n";
	}
	else
	{
	echo "<TR ALIGN=\"CENTER\"><TD>$nite</TD><TD>$filter</TD><TD>$oid[$i]</TD>\n";
	echo "<TD><a href=\"$fitnamesearch\"><IMG src=\"$namesearch\" width=\"$magnify\" height=\"$magnify\"></td>\n";
	echo "<TD><a href=\"$fitnametemp\"><IMG src=\"$nametemp\" width=\"$magnify\" height=\"$magnify\"></td>\n";
	echo "<TD><a href=\"$fitnamediff\"><IMG src=\"$namediff\" width=\"$magnify\" height=\"$magnify\"></TD>\n";
	echo "<td> <INPUT TYPE=\"RADIO\" NAME=\"candidate[$i]\" VALUE=\"artifact\" > </td>";
	echo "<td> <INPUT TYPE=\"RADIO\" NAME=\"candidate[$i]\" VALUE=\"SN\"> </td>";
	echo "<td> <INPUT TYPE=\"RADIO\" NAME=\"candidate[$i]\" VALUE=\"none\" class=\"missingc\" CHECKED> </td>";
	echo "<td>$mag</td><td>$ora</td><td>$odec</td><td>$psf</td>\n";
	echo "</TR>\n";
	}

  $i=$i+1;

}

if ($m==1){
      echo "<TR ALIGN=\"CENTER\"><td>Obs. Date</td><td>Filter</td><td>Object ID</TD>\n";
      echo "<TD>Search</TD><TD>Template</TD><TD>Subtracted</TD>";
      echo "<TD><button name=\"rad1\" id=\"ArtifactAlla\" type=\"button\" value=\"Y\">  Artifact</TD><TD><button name=\"rad1\" id=\"SNAlla\" type=\"button\" value=\"Y\">  Not Artif.</TD><TD><button name=\"rad1\" id=\"MissingAlla\" type=\"button\" value=\"Y\">  Missing</TD>";
      echo "<TD>Mag.</TD><TD>dRA</TD><TD>dDec</TD><TD>FWHM_IMG</TD>\n";
}
 $_SESSION['iend']=$i-1;
 $_SESSION['objarray']=$oid;


 echo "</TABLE><BR>\n"; 
 echo "<TABLE>\n";
 echo "<TR>\n";
 echo "<TD>                                                 </TD>";
 echo "<TD><INPUT TYPE=\"submit\" NAME = \"submit\" VALUE=\"UPDATE\">";
 echo "</FORM></TD>";

 echo "<FORM METHOD = \"POST\" ACTION = \"$upd\" TARGET =\"_top\">";
 echo "<INPUT TYPE=\"HIDDEN\" NAME=\"update\" VALUE=0 >";
 echo "<TD><INPUT TYPE=\"submit\" NAME = \"submit\" VALUE=\"SKIP\">";
 echo "</FORM></TD><P>";

 echo "<FORM METHOD = \"POST\" ACTION = \"$upd\" TARGET =\"_top\">";
 echo "<INPUT TYPE=\"HIDDEN\" NAME=\"update\" VALUE=2 >";
 echo "<TD><INPUT TYPE=\"submit\" NAME = \"submit\" VALUE=\"BACK (ONLY USE ONCE!)\">";
 echo "</FORM></TD><P>";

 echo "<TD><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">Back to initializing page.</A>   </TD>";
 echo "<TD><A HREF=\"Scanning.pdf\" TARGET=\"_BLANK\">Manual Scan Guide</A></TD>";
 echo "</P>";
 echo "</TABLE><br>\n";

echo "$query <br><br>";
echo "$querya <br><br>";
oci_free_statement($stmt);


}
 else
   {
     echo "<HTML><HEAD><TITLE>End of Scan</TITLE></HEAD><BODY>";
     echo "<H2>No Objects Found</H2>";
     echo "These queries are for debugging.  If you know what to do with them I guess you can play with them.  HAVE FUN!<br><br>"; 
     echo "$query <br><br>";
     echo "$queryMin <br><br>";
     echo "$queryMax <br><br>";
     echo "$queryCount <br><br>";
     oci_free_statement($stmt);
     if ($_SESSION['totalObjectstoScan']==0)
       {
	$queryCount = "SELECT count(*) fcount FROM SNCAND c ". 
   	"WHERE to_char(LATEST_NITE)='$ed' and c.SNID>=$minid and c.SNID<=$maxid AND SNFAKE_ID>0";
	oci_free_statement($stmt);
	$stmt = oci_parse($db, $queryCount) or
      	   die("Parsing error.");
	oci_execute($stmt) or
	   die("Could not execute query <BR>$queryCount");
	   $row = oci_fetch_assoc($stmt);
	$fakeObjects = $row['FCOUNT'];
  	$_SESSION['fakeObjects']=$fakeObjects;
	$queryCount = "SELECT count(*) ffcount FROM SNCAND c, SNSCAN s ". 
	" WHERE to_char(LATEST_NITE)='$ed' and c.SNID>=$minid and c.SNID<=$maxid AND SNFAKE_ID>0 and c.SNID=s.SNID ".
	" and (CATEGORYTYPE='GOLD' or CATEGORYTYPE='SILVER' or CATEGORYTYPE='BRONZE')";

      	$stmt = oci_parse($db, $queryCount) or
      	   die("Parsing error.");
	oci_execute($stmt) or
	   die("Could not execute query <BR>$queryCount");
	   $row = oci_fetch_assoc($stmt);
	$fakeScan = $row['FFCOUNT'];
  	$_SESSION['fakeScan']=$fakeScan;
	echo "<LI>{$_SESSION['fakeScan']} fakes observed by you out of {$_SESSION['fakeObjects']} fakes detected by the pipeline</LI>\n";
	

	 echo "<P>No objects were found matching your criteria with
	 search LATEST_NITE=$ed, subset=$setnum and FirstCandId=$fstCand. </P>\n";
	 if ($_SESSION['show_unscan']!=0) echo "<P>If you want to see 
         objects that have already been scanned, you will have 
         to uncheck the \"Show unscanned objects only.\" box on 
         the initialization page.</P>\n";
 	 echo "<P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">
	 Return to NEW initializing page.</A></P>\n";


	// if ($_SESSION['wflags']) echo "<P>If you want to see 
        // objects that have been flagged by doObjects (not normally 
        // scanned), you need to check the \"Ignore all flags.\" box 
        // on the initialization page.</P>\n";
       }

     else
       {
      	$queryCount = "SELECT count(*) fcount FROM SNCAND c ". 
   	 "WHERE to_char(LATEST_NITE)='$ed' and c.SNID>=$minid and c.SNID<=$maxid AND SNFAKE_ID>0";
	 oci_free_statement($stmt);
	$stmt = oci_parse($db, $queryCount) or
      	   die("Parsing error.");
	oci_execute($stmt) or
	   die("Could not execute query <BR>$queryCount");
	   $row = oci_fetch_assoc($stmt);
	$fakeObjects = $row['FCOUNT'];
  	$_SESSION['fakeObjects']=$fakeObjects;
  	$queryCount = "SELECT count(*) ffcount FROM SNCAND c, SNSCAN s ". 
	" WHERE to_char(LATEST_NITE)='$ed' and c.SNID>=$minid and c.SNID<=$maxid AND SNFAKE_ID>0 and c.SNID=s.SNID ".
	" and (CATEGORYTYPE='GOLD' or CATEGORYTYPE='SILVER' or CATEGORYTYPE='BRONZE')";
      	
	oci_free_statement($stmt);
	$stmt = oci_parse($db, $queryCount) or
      	   die("Parsing error.");
	oci_execute($stmt) or
	   die("Could not execute query <BR>$queryCount");
	   $row = oci_fetch_assoc($stmt);
	$fakeScan = $row['FFCOUNT'];
  	$_SESSION['fakeScan']=$fakeScan;
	 echo "<P>When you started this session you had</P><LIST>\n";
	 echo "<LI>{$_SESSION['totalObjectstoScan']} objects to be scanned out of </LI>\n";
	 echo "<LI>{$_SESSION['totalObjects']} objects from processing day $ed </LI>\n";
	 echo "<LI>{$_SESSION['objectsScanned']} objects were scanned 
               in this session.</LI></LIST>\n";
	 echo "<LI>{$_SESSION['fakeObjects']} fakes to be scanned.</LI></LIST>\n";
	 echo "<P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">
	 Return to NEW initializing page.</A></P>\n";
	    } 

}

/*
	 if ($_SESSION['objectsScanned']<$_SESSION['totalObjects']) 
	   {
	     echo "<P><B><FONT COLOR=\"RED\">Perhaps some one else 
             scanned the same subset for LATEST_NITE $ed, or there may have been a problem 
             in with this program.  But it is likely that you either 
             skipped some objects or the database suddenly became 
             unavailable.  Please check.</FONT></B></P>\n";
	   }
	 
	 
	 // Check that the database is still there 
	 
	 $queryCount = "SELECT count(*) numcount FROM SNSCAN c ". 
	 " WHERE to_char(LATEST_NITE)='$ed';
	 
	 $resultCount = mysql_query($queryCount)
	   or die("Query to count objects did not execute. <br> $queryCount ");
	 
	 $row = mysql_fetch_row($resultCount);
	 
	 oci_free_statement($stmt);

	$stmt = oci_parse($db, $queryCount) or
      	   die("Parsing error.");
	oci_execute($stmt) or
	   die("Could not execute query <BR>$queryCount");
	   $row = oci_fetch_assoc($stmt);


	 $numnow = $row['NUMCOUNT'];
	 if ($numnow==0) echo "<P><P><B><FONT COLOR=\"RED\">The database
         is currently showing no objects available for this 
         LATEST_NITE .  Probably the database 
         is being updated.  You will need to finish scanning when the update
         is complete (usually just a few minutes).</FONT></B></P>\n";
	 
       }
     echo "<P><B>Query used:</B> $query</P>\n";
     echo "<P><A HREF=\"initial_manual_scan.php\" TARGET=\"_TOP\">
	Return to initializing page.</A></P>\n";
     session_destroy();
   }

*/


mysql_close($connection);
oci_free_statement($stmt);
oci_close($db);
//oci_close($db1);
?>

 <script type='text/javascript' src='http://code.jquery.com/jquery-1.6.4.js'></script>
  <link rel="stylesheet" type="text/css" href="/css/normalize.css">
  <link rel="stylesheet" type="text/css" href="/css/result-light.css">
  <style type='text/css'>
  </style>
  


<script type='text/javascript'>//<![CDATA[ 
$("#SNAll").click(function() {
    if ('input[name="SNAll"')
        $("input:radio.SNC").attr("checked", "checked");
    else
        $("input:radio.SNC").removeAttr("checked");

});//]]>  


$("#MissingAll").click(function() {
    if ('input[name="MissingAll"')
        $("input:radio.missingc").attr("checked", "checked");
    else
        $("input:radio.missingc").removeAttr("checked");

});//]]>  

$("#ArtifactAll").click(function() {
    if ('input[name="ArtifactAll"')
        $("input:radio.artifactc").attr("checked", "checked");
    else
        $("input:radio.artifactc").removeAttr("checked");

});//]]> 


$("#SNAlla").click(function() {
    if ('input[name="SNAlla"')
        $("input:radio.SNC").attr("checked", "checked");
    else
        $("input:radio.SNC").removeAttr("checked");

});//]]>  


$("#MissingAlla").click(function() {
    if ('input[name="MissingAlla"')
        $("input:radio.missingc").attr("checked", "checked");
    else
        $("input:radio.missingc").removeAttr("checked");

});//]]>  

$("#ArtifactAlla").click(function() {
    if ('input[name="ArtifactAlla"')
        $("input:radio.artifactc").attr("checked", "checked");
    else
        $("input:radio.artifactc").removeAttr("checked");

});//]]>  
 

</script>
</body>

</html>